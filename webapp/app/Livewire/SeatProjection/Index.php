<?php
// ======================================================================
// File: app/Livewire/SeatProjection/Index.php
// ======================================================================

namespace App\Livewire\SeatProjection;

use Livewire\Component;
use App\Models\Election;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Index extends Component
{
    public array $elections = [];
    public int $electionId;
    public string $method = 'FPTP'; // FPTP | PR_DISTRICT_DHONDT | PR_DISTRICT_SAINTE_LAGUE
    public int $simulations = 2000;
    public float $alphaSmoothing = 1.0;
    public int $defaultSeatsPerDistrict = 1;

    // Pooling + time-decay
    public bool $useAllElections = true;
    public float $decayHalfLifeYears = 4.0;
    public ?string $anchorDate = null; // Y-m-d

    // Compact UI
    public bool $compact = true;

    public ?array $result = null;

    // Coalitions
    public string $coalitionsInput = '';
    public ?int $majorityThreshold = null;
    public ?array $coalitionOutput = null;

    /** @var array<int, array<string,int>> */
    private array $seatSamples = [];

    protected $rules = [
        'electionId'              => ['required','integer'],
        'method'                  => ['required','in:FPTP,PR_DISTRICT_DHONDT,PR_DISTRICT_SAINTE_LAGUE'],
        'simulations'             => ['required','integer','min:200','max:100000'],
        'alphaSmoothing'          => ['required','numeric','min:0.1','max:10'],
        'defaultSeatsPerDistrict' => ['required','integer','min:1','max:1000'],
        'useAllElections'         => ['boolean'],
        'decayHalfLifeYears'      => ['required','numeric','min:0.1','max:50'],
        'anchorDate'              => ['nullable','date'],
        'coalitionsInput'         => ['nullable','string','max:500'],
        'majorityThreshold'       => ['nullable','integer','min:1','max:10000'],
        'compact'                 => ['boolean'],
    ];

    public function mount(): void
    {
        $this->elections = Election::orderByDesc('election_date')
            ->get(['id','name','election_date'])
            ->map(fn($e) => [
                'id'   => $e->id,
                'name' => $e->name,
                'date' => optional($e->election_date)->format('Y-m-d'),
            ])->toArray();

        $this->electionId = $this->elections[0]['id'] ?? 1;
        $this->anchorDate = $this->elections[0]['date'] ?? date('Y-m-d');
    }

    public function run(): void
    {
        $this->validate();
        $this->result = null;
        $this->coalitionOutput = null;
        $this->seatSamples = [];

        $hasSeatsCol   = Schema::hasColumn('districts', 'seats');
        // Postgres-safe string literals
        $partyCodeExpr = "UPPER(COALESCE(NULLIF(parties.short_code, ''), 'UNKNOWN'))";

        // Pull votes with election_date for decay
        $rows = Result::query()
            ->join('parties', 'results.party_id', '=', 'parties.id')
            ->join('districts', 'results.district_id', '=', 'districts.id')
            ->join('elections', 'results.election_id', '=', 'elections.id')
            ->when(!$this->useAllElections, fn($q) => $q->where('results.election_id', $this->electionId))
            ->selectRaw(
                'results.district_id AS district_id,' .
                'districts.name AS district_name,' .
                ($hasSeatsCol ? 'COALESCE(districts.seats, 0) AS district_seats,' : '0 AS district_seats,') .
                "$partyCodeExpr AS code," .
                'SUM(results.votes) AS votes,' .
                "DATE(elections.election_date) AS election_date"
            )
            ->groupBy('results.district_id','districts.name')
            ->when($hasSeatsCol, fn($q) => $q->groupBy('districts.seats'))
            ->groupBy(DB::raw($partyCodeExpr))
            ->groupBy('elections.election_date')
            ->get();

        if ($rows->isEmpty()) {
            $this->addError('electionId', $this->useAllElections
                ? 'No pooled results found.'
                : 'No results found for this election.');
            return;
        }

        // Anchor & half-life
        $anchor = $this->anchorDate && strtotime($this->anchorDate) ? $this->anchorDate : ($rows->max('election_date') ?: date('Y-m-d'));
        $anchorTs = strtotime($anchor);
        $halfLife = max(0.1, (float)$this->decayHalfLifeYears);

        // Weighted district votes
        $districts = []; // did => ['name','seats','votes'=>[code=>wvotes],'total'=>sum]
        $parties   = [];
        foreach ($rows as $r) {
            $did   = (int)$r->district_id;
            $code  = (string)$r->code;
            $votes = max(0.0, (float)$r->votes);
            $parties[$code] = true;

            if (!isset($districts[$did])) {
                $seats = (int)$r->district_seats;
                if ($seats <= 0) $seats = $this->defaultSeatsPerDistrict; // fallback
                $districts[$did] = [
                    'name'  => (string)$r->district_name,
                    'seats' => $seats,
                    'votes' => [],
                    'total' => 0.0,
                ];
            }

            $w = 1.0;
            if ($this->useAllElections) {
                $ed = $r->election_date ? strtotime($r->election_date) : $anchorTs;
                $dy = abs($anchorTs - $ed) / (365.25 * 24 * 3600);
                $w = pow(0.5, $dy / $halfLife); // time-decay
            }

            $districts[$did]['votes'][$code] = ($districts[$did]['votes'][$code] ?? 0.0) + ($votes * $w);
            $districts[$did]['total'] += ($votes * $w);
        }

        $partyList = array_values(array_keys($parties));
        sort($partyList);

        // Total seats
        $seatCount = 0;
        foreach ($districts as $d) {
            $seatCount += ($this->method === 'FPTP') ? 1 : (int)$d['seats'];
        }

        $N  = max(200, (int)$this->simulations);
        $as = max(0.1, (float)$this->alphaSmoothing);

        // Dirichlet alphas per district
        $alphaDistrict = [];
        foreach ($districts as $did => $d) {
            $alph = [];
            foreach ($partyList as $p) {
                $v = (float)($d['votes'][$p] ?? 0.0);
                $alph[$p] = max(0.001, $v * $as); // tighter with more data
            }
            $alphaDistrict[$did] = $alph;
        }

        // Simulate
        $nationalSamples = []; foreach ($partyList as $p) $nationalSamples[$p] = [];
        $districtMeans = []; foreach ($districts as $did => $_) $districtMeans[$did] = array_fill_keys($partyList, 0.0);
        $seatDraws = [];

        for ($i = 0; $i < $N; $i++) {
            $natSeats = array_fill_keys($partyList, 0);

            foreach ($districts as $did => $d) {
                $shares = $this->sampleDirichlet($alphaDistrict[$did]);
                $sum = array_sum($shares);
                if ($sum > 0) foreach ($shares as $p => $s) $shares[$p] = $s / $sum;

                if ($this->method === 'FPTP') {
                    $winner = null; $best = -1.0;
                    foreach ($shares as $p => $s) if ($s > $best) { $best = $s; $winner = $p; }
                    if ($winner !== null) {
                        $natSeats[$winner] += 1;
                        $districtMeans[$did][$winner] += 1.0;
                    }
                } else {
                    $cap  = max(1, (int)$d['seats']);
                    $rule = ($this->method === 'PR_DISTRICT_DHONDT') ? 'DHONDT' : 'SAINTE';
                    $alloc = $this->divisorAllocate($shares, $cap, $rule);
                    foreach ($alloc as $p => $n) {
                        $natSeats[$p] += (int)$n;
                        $districtMeans[$did][$p] += (float)$n;
                    }
                }
            }

            $seatDraws[$i] = $natSeats;
            foreach ($partyList as $p) $nationalSamples[$p][] = (int)($natSeats[$p] ?? 0);
        }

        foreach ($districtMeans as $did => $byParty) {
            foreach ($byParty as $p => $acc) $districtMeans[$did][$p] = $acc / $N;
        }
        $this->seatSamples = $seatDraws;

        // National summaries
        $summary = [];
        foreach ($partyList as $p) {
            $arr = $nationalSamples[$p]; sort($arr);
            $summary[$p] = [
                'mean' => $this->mean($arr),
                'ci95' => [$this->quantile($arr, 0.025), $this->quantile($arr, 0.975)],
            ];
        }

        $threshold = $this->majorityThreshold ?: (int)floor($seatCount / 2) + 1;

        $this->result = [
            'method'           => $this->method,
            'simulations'      => $N,
            'total_seats'      => $seatCount,
            'summary'          => $summary,
            'parties'          => $partyList,
            'threshold'        => $threshold,
            'districts'        => array_map(fn($d) => ['name'=>$d['name'],'seats'=>$this->method==='FPTP'?1:$d['seats']], $districts),
            'district_summary' => $districtMeans,
            'pooled'           => $this->useAllElections,
            'half_life'        => $halfLife,
            'anchor'           => date('Y-m-d', $anchorTs),
        ];

        if ($this->coalitionsInput !== '' || $this->majorityThreshold) {
            $this->computeCoalitions();
        }
    }

    public function computeCoalitions(): void
    {
        if (!$this->result) return;

        $coalitions = $this->parseCoalitions($this->coalitionsInput);
        $threshold  = $this->majorityThreshold ?: (int) floor(($this->result['total_seats'] / 2) + 1);

        $partyProb = [];
        $counts = [];
        foreach ($this->result['parties'] as $p) $counts[$p] = 0;

        $coalProb = array_fill_keys($coalitions, 0);
        $S = max(1, (int)$this->result['simulations']);

        foreach ($this->seatSamples as $seats) {
            foreach ($seats as $p => $num) if ($num >= $threshold) $counts[$p]++;
            foreach ($coalitions as $label) {
                $sum = 0;
                foreach (explode('+', $label) as $code) $sum += (int)($seats[strtoupper(trim($code))] ?? 0);
                if ($sum >= $threshold) $coalProb[$label]++;
            }
        }

        foreach ($counts as $p => $c) $partyProb[$p] = $c / $S;
        foreach ($coalProb as $k => $c) $coalProb[$k] = $c / $S;

        $this->coalitionOutput = [
            'threshold' => $threshold,
            'party'     => $partyProb,
            'coalition' => array_map(fn($k)=>['label'=>$k,'prob'=>$coalProb[$k]], array_keys($coalProb)),
        ];
    }

    public function render()
    {
        return view('livewire.seat-projection.index')->layout('layouts.app');
    }

    /** @return array<int,string> */
    private function parseCoalitions(string $input): array
    {
        $out = [];
        foreach (explode(',', $input) as $chunk) {
            $label = strtoupper(trim($chunk));
            if ($label !== '') $out[] = $label;
        }
        return $out;
    }

    // --------- RNG + math utils ---------

    /** @param array<string,float> $alphas @return array<string,float> */
    private function sampleDirichlet(array $alphas): array
    {
        $g = []; $sum = 0.0;
        foreach ($alphas as $k => $a) {
            $x = $this->sampleGamma(max(0.001, (float)$a));
            $g[$k] = $x; $sum += $x;
        }
        $out = [];
        foreach ($g as $k => $x) $out[$k] = $sum > 0 ? $x / $sum : 0.0;
        return $out;
    }

    private function uniform(): float { return (mt_rand() / mt_getrandmax()) ?: 1e-12; }

    private function sampleNormal(): float
    {
        $u1 = $this->uniform(); $u2 = $this->uniform();
        return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
    }

    private function sampleGamma(float $k): float
    {
        $k = max(1e-6, $k);
        if ($k < 1.0) { $x = $this->sampleGamma($k + 1.0); $u = $this->uniform(); return $x * pow($u, 1.0 / $k); }
        $d = $k - 1.0/3.0; $c = 1.0 / sqrt(9.0*$d);
        while (true) {
            $x = $this->sampleNormal();
            $v = pow(1.0 + $c*$x, 3.0);
            if ($v <= 0) continue;
            $u = $this->uniform();
            if ($u < 1.0 - 0.0331*($x**4)) return $d*$v;
            if (log($u) < 0.5*$x*$x + $d*(1.0 - $v + log($v))) return $d*$v;
        }
    }

    /**
     * @param array<string,float> $votesShares
     * @return array<string,int>
     */
    private function divisorAllocate(array $votesShares, int $seats, string $rule): array
    {
        $rule = strtoupper($rule); // 'DHONDT' | 'SAINTE'
        $divSeq = ($rule === 'SAINTE') ? [1,3,5,7,9,11,13,15,17,19,21] : [1,2,3,4,5,6,7,8,9,10,11];
        while (count($divSeq) < max(1, $seats)) $divSeq[] = end($divSeq) + (($rule === 'SAINTE') ? 2 : 1);

        $quotients = [];
        foreach ($votesShares as $p => $v) {
            $v = max(0.0, (float)$v);
            foreach ($divSeq as $d) $quotients[] = ['p'=>$p, 'q'=> ($d > 0 ? $v / $d : 0.0)];
        }
        usort($quotients, fn($a,$b) => $b['q'] <=> $a['q']);

        $alloc = array_fill_keys(array_keys($votesShares), 0);
        for ($i=0; $i<$seats && $i<count($quotients); $i++) $alloc[$quotients[$i]['p']]++;
        return $alloc;
    }

    /** @param array<int|float> $xs */
    private function mean(array $xs): float
    {
        $n = count($xs);
        return $n ? array_sum($xs)/$n : 0.0;
    }

    /** @param array<int|float> $xs */
    private function quantile(array $xs, float $p): float
    {
        $n = count($xs); if (!$n) return 0.0;
        $p = max(0.0, min(1.0, $p));
        $idx = $p * ($n - 1);
        $lo = (int)floor($idx); $hi = (int)ceil($idx);
        if ($lo === $hi) return (float)$xs[$lo];
        $w = $idx - $lo;
        return (1 - $w) * (float)$xs[$lo] + $w * (float)$xs[$hi];
    }
}
