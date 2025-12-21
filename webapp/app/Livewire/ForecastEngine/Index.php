<?php
// ==============================================
// File: app/Livewire/ForecastEngine/Index.php
// District-level results (no polling stations)
// Turnout derived from DistrictPopulation
// Pooled mode pools elections of SAME TYPE as selected election
// ==============================================

namespace App\Livewire\ForecastEngine;

use Livewire\Component;
use App\Models\Election;
use App\Models\Result;
use App\Models\District;
use App\Models\DistrictPopulation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Index extends Component
{
    // UI state
    public array $elections = [];
    public int $electionId;

    public string $scopeMode = 'pooled';      // 'single' | 'pooled'
    public string $breakdown = 'district';    // 'national' | 'district'
    public int $simulations = 1000;
    public float $alphaSmoothing = 1.0;

    // Pooled time-decay
    public float $decayHalfLifeYears = 8.0;
    public ?string $anchorDate = null;

    // Turnout denominator source (DistrictPopulation)
    public ?int $turnoutCensusYear = null;    // e.g. 2015 or 2021
    public string $turnoutField = 'population_18_plus'; // or 'total_population'

    // Sorting (district table)
    public string $sortBy = 'name';           // 'name' | 'slpp_mean' | 'apc_mean' | 'turnout_mean' | 'others'
    public string $sortDir = 'asc';           // 'asc' | 'desc'

    // District picker
    public ?string $selectedDistrictId = '';
    public array $districtOptions = [];

    // Dropdown options
    public array $populationYears = [];

    // Output
    public ?array $output = null;

    public function mount(): void
    {
        $this->elections = Election::orderByDesc('election_date')
            ->get(['id','name','type','round','election_date'])
            ->map(fn($e)=>[
                'id'            => (int)$e->id,
                'name'          => (string)$e->name,
                'type'          => (string)$e->type,
                'round'         => (int)$e->round,
                'election_date' => $e->election_date?->format('Y-m-d'),
            ])->toArray();

        $this->electionId = $this->elections[0]['id'] ?? 1;
        $this->anchorDate = $this->elections[0]['election_date'] ?? Carbon::now()->format('Y-m-d');

        if (Schema::hasTable('districts')) {
            $this->districtOptions = District::query()
                ->orderBy('name')
                ->get(['id','name'])
                ->map(fn($d)=>['id'=>(string)(int)$d->id,'name'=>(string)$d->name])
                ->toArray();
        }

        // Population years
        if (Schema::hasTable('district_populations')) {
            $this->populationYears = DistrictPopulation::select('census_year')
                ->distinct()
                ->orderBy('census_year', 'desc')
                ->pluck('census_year')
                ->map(fn($y)=>(int)$y)
                ->toArray();

            // default to newest year (usually 2021)
            $this->turnoutCensusYear = $this->populationYears[0] ?? null;
        }
    }

    // Reactive handlers
    public function updatedSelectedDistrictId($value): void
    {
        $this->selectedDistrictId = $value === null ? '' : (string)$value;
    }

    public function updatedBreakdown(): void
    {
        $this->selectedDistrictId = '';
        $this->output = null;
    }

    public function updatedScopeMode(): void
    {
        $this->selectedDistrictId = '';
        $this->output = null;
    }

    public function updatedTurnoutCensusYear(): void
    {
        $this->output = null;
    }

    public function updatedTurnoutField(): void
    {
        $this->output = null;
    }

    public function setSort(string $key): void
    {
        $allowed = ['name','slpp_mean','apc_mean','turnout_mean','others'];
        if (!in_array($key, $allowed, true)) return;

        if ($this->sortBy === $key) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $key;
            $this->sortDir = $key === 'name' ? 'asc' : 'desc';
        }

        if ($this->output && ($this->output['breakdown'] ?? null) === 'district') {
            $this->sortDistrictRowsInOutput();
        }
    }

    public function clearDistrict(): void
    {
        $this->selectedDistrictId = '';
    }

    // =========================
    // Core run (district-level)
    // =========================
    public function run(): void
    {
        $this->resetErrorBag();
        $this->output = null;

        $N  = max(1, (int)$this->simulations);
        $as = max(0.1, (float)$this->alphaSmoothing);
        $hl = max(0.25, (float)$this->decayHalfLifeYears);

        $anchor = $this->safeDate($this->anchorDate) ?: Carbon::now();

        // Selected election meta (defines pooling type)
        $electionMetaById = $this->indexElectionsById();
        $selectedElection = $electionMetaById[$this->electionId] ?? null;
        if (!$selectedElection) {
            $this->addError('electionId', 'Invalid election selected.');
            return;
        }
        $poolType = $selectedElection['type'] ?? 'presidential';

        // Ensure census year available
        if (!$this->turnoutCensusYear) {
            $this->addError('turnoutCensusYear', 'Add population data first (2015/2021) to compute turnout.');
            return;
        }
        $turnoutField = $this->turnoutField === 'total_population' ? 'total_population' : 'population_18_plus';

        // 0) Load turnout denominators (district -> denom)
        $denoms = $this->loadDistrictDenominators((int)$this->turnoutCensusYear, $turnoutField);
        if (!$denoms) {
            $this->addError('turnoutCensusYear', "No population rows found for census year {$this->turnoutCensusYear}.");
            return;
        }

        // 1) Get district vote totals (SLPP/APC + grand total for Others)
        //    - single: just selected election
        //    - pooled: all elections of same type as selected election, with decay weights
        $voteRows = $this->fetchDistrictPartyTotals($poolType, $anchor, $hl);

        if ($voteRows->isEmpty()) {
            $this->addError('electionId', 'No district results found. Import results first.');
            return;
        }

        // Build units indexed by district_id
        // units[districtId] = ['name'=>..., 'party_totals'=>['SLPP'=>..., 'APC'=>..., 'OTHERPARTIES'=>...], 'grand_total'=>...]
        $units = [];
        foreach ($voteRows as $row) {
            $districtId = (string)((int)$row->district_id);
            $districtName = (string)($row->district_name ?? ("District {$districtId}"));

            if (!isset($units[$districtId])) {
                $units[$districtId] = [
                    'name' => $districtName,
                    'party_totals' => [],
                    'grand_total' => 0.0,
                ];
            }

            $code = strtoupper((string)$row->code);
            $v = (float)$row->total_votes;

            $units[$districtId]['party_totals'][$code] = ($units[$districtId]['party_totals'][$code] ?? 0.0) + $v;
            $units[$districtId]['grand_total'] += $v;
        }

        // 2) Turnout stats per district using:
        // turnoutRate = total_votes_all_parties_in_district / denom(population_18_plus or total_population)
        // pooled: weighted across elections by denom * decayWeight
        $turnoutStats = $this->computeDistrictTurnoutStats($poolType, $denoms, $anchor, $hl);

        // Fill missing districts with safe defaults
        foreach ($units as $uid => $_) {
            if (!isset($turnoutStats[$uid])) {
                $turnoutStats[$uid] = ['m'=>0.70, 'v'=>0.02];
            }
        }

        // 3) Monte Carlo per district (two-party Dirichlet + turnout Beta)
        $K = max(10.0, 100.0 * $as);
        $resultsPerUnit = [];

        foreach ($units as $uid => $meta) {
            // Respect district filter if set
            if (!empty($this->selectedDistrictId) && $this->selectedDistrictId !== $uid) {
                continue;
            }

            $byParty = $meta['party_totals'];
            $grand   = max(0.0, (float)$meta['grand_total']);

            $slpp = (float)($byParty['SLPP'] ?? 0.0);
            $apc  = (float)($byParty['APC']  ?? 0.0);
            $two  = $slpp + $apc;

            if ($two <= 0.0) continue;

            // Normalize to TWO-PARTY for modeling
            $baseShares = [
                'SLPP' => $slpp / $two,
                'APC'  => $apc  / $two,
            ];

            // Others share shown as context using raw totals
            $othersShareAllParty = $grand > 0 ? max(0.0, 1.0 - ($two / $grand)) : 0.0;

            $alphas = [
                'SLPP' => max(0.001, $baseShares['SLPP'] * $K),
                'APC'  => max(0.001, $baseShares['APC']  * $K),
            ];

            // Turnout Beta from mean/var
            $m = min(0.999, max(0.001, (float)$turnoutStats[$uid]['m']));
            $v = max(1e-6, min((float)$turnoutStats[$uid]['v'], $m*(1-$m) - 1e-6));

            $k = $m*(1-$m)/$v - 1.0;
            if ($k <= 0) {
                $scale = max(10.0, 50.0 * $as);
                $turnA = $m * $scale;
                $turnB = (1 - $m) * $scale;
            } else {
                $turnA = $m * $k;
                $turnB = (1 - $m) * $k;
            }
            $turnA = max(0.001, (float)$turnA);
            $turnB = max(0.001, (float)$turnB);

            $samples = ['SLPP'=>[], 'APC'=>[], 'turnout'=>[]];
            $sumShares = ['SLPP'=>0.0,'APC'=>0.0];
            $sumTurn   = 0.0;

            for ($i=0; $i<$N; $i++) {
                $shares  = $this->sampleDirichlet($alphas);
                $turnout = $this->sampleBeta($turnA, $turnB);

                $samples['SLPP'][] = $shares['SLPP'];
                $samples['APC'][]  = $shares['APC'];
                $samples['turnout'][] = $turnout;

                $sumShares['SLPP'] += $shares['SLPP'];
                $sumShares['APC']  += $shares['APC'];
                $sumTurn           += $turnout;
            }

            $meanShares = [
                'SLPP' => $sumShares['SLPP'] / $N,
                'APC'  => $sumShares['APC']  / $N,
            ];

            $cis = [
                'SLPP'   => $this->formatBand($this->quantiles($samples['SLPP'],   [0.25,0.5,0.75,0.025,0.975])),
                'APC'    => $this->formatBand($this->quantiles($samples['APC'],    [0.25,0.5,0.75,0.025,0.975])),
                'turnout'=> $this->formatBand($this->quantiles($samples['turnout'],[0.25,0.5,0.75,0.025,0.975])),
            ];

            $resultsPerUnit[$uid] = [
                'unit_name'     => $meta['name'],
                'national_mean' => $meanShares,
                'cis'           => $cis,
                'mean_turnout'  => $sumTurn / $N,
                'others_share_all_party' => $othersShareAllParty,
            ];
        }

        if (!$resultsPerUnit) {
            $this->addError('electionId', 'No usable data found after filtering. Ensure SLPP/APC exist in imported results.');
            return;
        }

        // 4) Output assembly
        if ($this->breakdown === 'national') {
            // Build national by aggregating district modeled means weighted by denom
            $national = $this->aggregateNationalFromDistricts($resultsPerUnit, $denoms);
            $this->output = [
                'scope'        => $this->scopeMode,
                'breakdown'    => 'national',
                'simulations'  => $N,
                'alpha_smoothing' => $as,
                'turnout_census_year' => (int)$this->turnoutCensusYear,
                'turnout_field' => $turnoutField,
                'national_mean' => $national['national_mean'],
                'mean_turnout'  => $national['mean_turnout'],
                'notes' => [
                    'two_party' => 'Dirichlet is run on SLPP/APC only. Others is shown as context from raw totals.',
                    'turnout'   => "Turnout = total district votes / {$turnoutField} (census {$this->turnoutCensusYear}).",
                    'pooled'    => "Pooled mode includes elections of type={$poolType} only, time-decayed.",
                ],
            ];
            return;
        }

        // district table
        $rows = [];
        foreach ($resultsPerUnit as $uid => $r) {
            $rows[$uid] = [
                'id'           => (string)$uid,
                'name'         => $r['unit_name'],
                'slpp_mean'    => $r['national_mean']['SLPP'] ?? null,
                'apc_mean'     => $r['national_mean']['APC'] ?? null,
                'slpp_cis'     => $r['cis']['SLPP'] ?? null,
                'apc_cis'      => $r['cis']['APC'] ?? null,
                'turnout_mean' => $r['mean_turnout'] ?? null,
                'turnout_cis'  => $r['cis']['turnout'] ?? null,
                'others'       => $r['others_share_all_party'] ?? 0.0,
            ];
        }

        $this->applyDistrictSort($rows);

        $this->output = [
            'scope'        => $this->scopeMode,
            'breakdown'    => 'district',
            'simulations'  => $N,
            'alpha_smoothing' => $as,
            'turnout_census_year' => (int)$this->turnoutCensusYear,
            'turnout_field' => $turnoutField,
            'districts'    => $rows,
            'notes' => [
                'two_party' => 'Dirichlet is run on SLPP/APC only. Others is shown as context from raw totals.',
                'turnout'   => "Turnout = total district votes / {$turnoutField} (census {$this->turnoutCensusYear}).",
                'pooled'    => "Pooled mode includes elections of type={$poolType} only, time-decayed.",
            ],
        ];
    }

    // =========================
    // Data queries
    // =========================

    private function fetchDistrictPartyTotals(string $poolType, Carbon $anchor, float $halfLifeYears)
    {
        $selectedElection = Election::find($this->electionId);

        if (!$selectedElection) {
            return collect();
        }

        if ($this->scopeMode === 'single') {
            // only selected election
            return Result::query()
                ->join('districts', 'results.district_id', '=', 'districts.id')
                ->join('parties', 'results.party_id', '=', 'parties.id')
                ->where('results.election_id', $this->electionId)
                ->selectRaw("
                    results.district_id AS district_id,
                    MAX(districts.name) AS district_name,
                    UPPER(COALESCE(NULLIF(parties.short_code,''),'UNKNOWN')) AS code,
                    SUM(results.votes) AS total_votes
                ")
                ->groupBy('results.district_id')
                ->groupByRaw("UPPER(COALESCE(NULLIF(parties.short_code,''),'UNKNOWN'))")
                ->get();
        }

        // pooled: elections of SAME TYPE as selected election
        $eids = Election::where('type', $poolType)->pluck('id')->map(fn($x)=>(int)$x)->toArray();
        if (!$eids) return collect();

        // We apply decay by multiplying votes inside SQL is hard; do it in PHP safely.
        $rows = Result::query()
            ->join('districts', 'results.district_id', '=', 'districts.id')
            ->join('parties', 'results.party_id', '=', 'parties.id')
            ->whereIn('results.election_id', $eids)
            ->selectRaw("
                results.election_id AS election_id,
                results.district_id AS district_id,
                MAX(districts.name) AS district_name,
                UPPER(COALESCE(NULLIF(parties.short_code,''),'UNKNOWN')) AS code,
                SUM(results.votes) AS total_votes
            ")
            ->groupBy('results.election_id')
            ->groupBy('results.district_id')
            ->groupByRaw("UPPER(COALESCE(NULLIF(parties.short_code,''),'UNKNOWN'))")
            ->get();

        // Apply decay weighting
        $meta = $this->indexElectionsById();
        return $rows->map(function ($r) use ($meta, $anchor, $halfLifeYears) {
            $eid = (int)$r->election_id;
            $w = $this->decayWeight($meta[$eid]['election_date'] ?? null, $anchor, $halfLifeYears);
            $r->total_votes = (float)$r->total_votes * $w;
            return $r;
        });
    }

    private function computeDistrictTurnoutStats(string $poolType, array $denoms, Carbon $anchor, float $halfLifeYears): array
    {
        // We compute turnoutRate per district per election:
        // turnoutRate = total_votes_all_parties / denom
        // Then compute weighted mean+variance and convert to Beta params later

        $selectedElection = Election::find($this->electionId);
        if (!$selectedElection) return [];

        if ($this->scopeMode === 'single') {
            $totals = Result::query()
                ->where('election_id', $this->electionId)
                ->selectRaw("district_id, SUM(votes) AS total_votes")
                ->groupBy('district_id')
                ->get();

            $acc = [];
            foreach ($totals as $row) {
                $uid = (string)((int)$row->district_id);
                $den = (float)($denoms[$uid] ?? 0);
                if ($den <= 0) continue;

                $votes = (float)$row->total_votes;
                $f = $votes / $den;
                $f = min(1.0, max(0.0, $f)); // clamp

                // weight by denom (bigger districts matter more)
                $w = $den;

                if (!isset($acc[$uid])) $acc[$uid] = ['w_sum'=>0.0,'fw_sum'=>0.0,'w2_sum'=>0.0,'vals'=>[],'weights'=>[]];
                $acc[$uid]['w_sum']  += $w;
                $acc[$uid]['fw_sum'] += $f * $w;
                $acc[$uid]['w2_sum'] += $w * $w;
                $acc[$uid]['vals'][] = $f;
                $acc[$uid]['weights'][] = $w;
            }

            return $this->weightedMeanVarByUnit($acc);
        }

        // pooled: elections of same type
        $eids = Election::where('type', $poolType)->pluck('id')->map(fn($x)=>(int)$x)->toArray();
        if (!$eids) return [];

        $meta = $this->indexElectionsById();

        $totals = Result::query()
            ->whereIn('election_id', $eids)
            ->selectRaw("election_id, district_id, SUM(votes) AS total_votes")
            ->groupBy('election_id','district_id')
            ->get();

        $acc = [];
        foreach ($totals as $row) {
            $uid = (string)((int)$row->district_id);
            $den = (float)($denoms[$uid] ?? 0);
            if ($den <= 0) continue;

            $votes = (float)$row->total_votes;
            $f = $votes / $den;
            $f = min(1.0, max(0.0, $f)); // clamp

            $eid = (int)$row->election_id;
            $decay = $this->decayWeight($meta[$eid]['election_date'] ?? null, $anchor, $halfLifeYears);

            // weight by denom * decay
            $w = $den * $decay;

            if (!isset($acc[$uid])) $acc[$uid] = ['w_sum'=>0.0,'fw_sum'=>0.0,'w2_sum'=>0.0,'vals'=>[],'weights'=>[]];
            $acc[$uid]['w_sum']  += $w;
            $acc[$uid]['fw_sum'] += $f * $w;
            $acc[$uid]['w2_sum'] += $w * $w;
            $acc[$uid]['vals'][] = $f;
            $acc[$uid]['weights'][] = $w;
        }

        return $this->weightedMeanVarByUnit($acc);
    }

    private function weightedMeanVarByUnit(array $acc): array
    {
        $out = [];

        foreach ($acc as $uid => $S) {
            if (($S['w_sum'] ?? 0) <= 0 || count($S['vals'] ?? []) < 1) continue;

            $m = $S['fw_sum'] / $S['w_sum'];

            // variance estimate (weighted)
            if (count($S['vals']) === 1) {
                $v = 0.02; // guard
            } else {
                $c = ($S['w_sum']*$S['w_sum'] - $S['w2_sum']);
                if ($c <= 0) {
                    $v = 0.02;
                } else {
                    $tmp = 0.0;
                    foreach ($S['vals'] as $i => $f) {
                        $tmp += $S['weights'][$i] * ($f - $m) * ($f - $m);
                    }
                    $v = ($S['w_sum'] / $c) * $tmp;
                }
            }

            // clamp v to valid Beta region
            $v = max(1e-6, min($v, max(1e-6, $m*(1-$m) - 1e-6)));

            $out[$uid] = ['m'=>$m,'v'=>$v];
        }

        return $out;
    }

    private function loadDistrictDenominators(int $year, string $field): array
    {
        // returns [districtIdStr => denomNumber]
        $map = [];

        $rows = DistrictPopulation::query()
            ->where('census_year', $year)
            ->get(['district_id', $field]);

        foreach ($rows as $r) {
            $id = (string)((int)$r->district_id);
            $val = (float)($r->{$field} ?? 0);
            $map[$id] = $val;
        }

        return $map;
    }

    private function aggregateNationalFromDistricts(array $resultsPerUnit, array $denoms): array
    {
        $wSum = 0.0;
        $slpp = 0.0;
        $apc  = 0.0;
        $turn = 0.0;

        foreach ($resultsPerUnit as $uid => $r) {
            $w = (float)($denoms[(string)$uid] ?? 0);
            if ($w <= 0) continue;

            $wSum += $w;
            $slpp += $w * (float)($r['national_mean']['SLPP'] ?? 0);
            $apc  += $w * (float)($r['national_mean']['APC'] ?? 0);
            $turn += $w * (float)($r['mean_turnout'] ?? 0);
        }

        if ($wSum <= 0) {
            return [
                'national_mean' => ['SLPP'=>0,'APC'=>0],
                'mean_turnout' => 0.7,
            ];
        }

        return [
            'national_mean' => [
                'SLPP' => $slpp / $wSum,
                'APC'  => $apc  / $wSum,
            ],
            'mean_turnout' => $turn / $wSum,
        ];
    }

    // =========================
    // CSV Export (district view)
    // =========================
    public function exportDistrictCsv(): StreamedResponse
    {
        $rows = [];
        if (($this->output['breakdown'] ?? '') === 'district' && isset($this->output['districts'])) {
            $rows = $this->output['districts'];
        }

        $filename = 'district_forecast_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['District', 'SLPP %', 'SLPP 50%', 'SLPP 95%', 'APC %', 'APC 50%', 'APC 95%', 'Turnout %', 'Turnout 50%', 'Turnout 95%', 'Others %']);

            $pct = fn($x)=>$x===null?null:round($x*100,2);

            foreach ($rows as $r) {
                $slpp = (float)($r['slpp_mean'] ?? 0);
                $apc  = (float)($r['apc_mean'] ?? 0);
                $t    = (float)($r['turnout_mean'] ?? 0);

                $s50  = $r['slpp_cis']['i50'] ?? [null,null];
                $s95  = $r['slpp_cis']['i95'] ?? [null,null];
                $a50  = $r['apc_cis']['i50'] ?? [null,null];
                $a95  = $r['apc_cis']['i95'] ?? [null,null];
                $t50  = $r['turnout_cis']['i50'] ?? [null,null];
                $t95  = $r['turnout_cis']['i95'] ?? [null,null];

                fputcsv($out, [
                    (string)$r['name'],
                    $pct($slpp),
                    ($s50[0]===null||$s50[1]===null) ? null : ($pct($s50[0]).'–'.$pct($s50[1])),
                    ($s95[0]===null||$s95[1]===null) ? null : ($pct($s95[0]).'–'.$pct($s95[1])),
                    $pct($apc),
                    ($a50[0]===null||$a50[1]===null) ? null : ($pct($a50[0]).'–'.$pct($a50[1])),
                    ($a95[0]===null||$a95[1]===null) ? null : ($pct($a95[0]).'–'.$pct($a95[1])),
                    $pct($t),
                    ($t50[0]===null||$t50[1]===null) ? null : ($pct($t50[0]).'–'.$pct($t50[1])),
                    ($t95[0]===null||$t95[1]===null) ? null : ($pct($t95[0]).'–'.$pct($t95[1])),
                    $pct((float)($r['others'] ?? 0.0)),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // =========================
    // Sorting + Livewire render
    // =========================
    private function applyDistrictSort(array &$rows): void
    {
        $key = $this->sortBy;
        $dir = $this->sortDir === 'desc' ? -1 : 1;

        uasort($rows, function($a,$b) use($key,$dir){
            $av = $a[$key] ?? null;
            $bv = $b[$key] ?? null;

            if ($key === 'name') return $dir * strnatcasecmp((string)$av, (string)$bv);

            if ($av === null && $bv === null) return 0;
            if ($av === null) return 1;
            if ($bv === null) return -1;

            // numeric
            return $dir * (($av <=> $bv) * -1);
        });
    }

    private function sortDistrictRowsInOutput(): void
    {
        if (!isset($this->output['districts'])) return;
        $rows = $this->output['districts'];
        $this->applyDistrictSort($rows);
        $this->output['districts'] = $rows;
    }

    public function resetSim(): void { $this->reset('output'); }

    public function render()
    {
        return view('livewire.forecast-engine.index', [
            'districtOptions' => $this->districtOptions,
            'populationYears' => $this->populationYears,
        ])->layout('layouts.app');
    }

    // =========================
    // Utilities
    // =========================
    private function indexElectionsById(): array
    {
        $map = [];
        foreach ($this->elections as $e) $map[(int)$e['id']] = $e;
        return $map;
    }

    private function safeDate(?string $s): ?Carbon
    {
        try { return $s ? Carbon::parse($s) : null; } catch (\Throwable) { return null; }
    }

    private function decayWeight(?string $electionDate, Carbon $anchor, float $halfLifeYears): float
    {
        $ed = $this->safeDate($electionDate) ?: $anchor;
        $diffDays = $anchor->diffInDays($ed, false);
        $years = abs($diffDays) / 365.25;
        return pow(0.5, $years / max(0.0001, $halfLifeYears));
    }

    private function formatBand(array $q): array
    {
        return [
            'p50' => $q['0.5'] ?? null,
            'i50' => [ $q['0.25'] ?? null, $q['0.75'] ?? null ],
            'i95' => [ $q['0.025'] ?? null, $q['0.975'] ?? null ],
        ];
    }

    private function quantiles(array $values, array $ps): array
    {
        if (!$values) return [];
        sort($values);
        $n = count($values);
        $out = [];
        foreach ($ps as $p) {
            $p = max(0.0, min(1.0, $p));
            $idx = $p * ($n - 1);
            $lo = (int)floor($idx);
            $hi = (int)ceil($idx);
            $val = ($lo === $hi) ? $values[$lo]
                : ((1-($idx-$lo)) * $values[$lo] + ($idx-$lo) * $values[$hi]);
            $out[(string)$p] = $val;
        }
        return $out;
    }

    // RNG
    private function uniform(): float { return (mt_rand() / mt_getrandmax()) ?: 1e-12; }

    private function sampleNormal(): float
    {
        $u1 = $this->uniform(); $u2 = $this->uniform();
        return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
    }

    private function sampleGamma(float $k): float
    {
        $k = max(1e-6, $k);
        if ($k < 1.0) {
            $x = $this->sampleGamma($k + 1.0);
            $u = $this->uniform();
            return $x * pow($u, 1.0 / $k);
        }
        $d = $k - 1.0/3.0; $c = 1.0 / sqrt(9.0 * $d);
        while (true) {
            $x = $this->sampleNormal();
            $v = pow(1.0 + $c * $x, 3.0);
            if ($v <= 0) continue;
            $u = $this->uniform();
            if ($u < 1.0 - 0.0331 * ($x ** 4)) return $d * $v;
            if (log($u) < 0.5 * $x * $x + $d * (1.0 - $v + log($v))) return $d * $v;
        }
    }

    private function sampleBeta(float $a, float $b): float
    {
        $g1 = $this->sampleGamma($a); $g2 = $this->sampleGamma($b); $s = $g1 + $g2;
        return $s > 0 ? $g1 / $s : 0.5;
    }

    /** @param array<string,float> $alphas */
    private function sampleDirichlet(array $alphas): array
    {
        $gsum = 0.0; $g = [];
        foreach ($alphas as $k => $a) {
            $val = $this->sampleGamma(max(0.001, (float)$a));
            $g[$k] = $val; $gsum += $val;
        }
        $out = [];
        foreach ($g as $k => $val) {
            $out[$k] = $gsum > 0 ? $val / $gsum : 0.0;
        }
        return $out;
    }
}
