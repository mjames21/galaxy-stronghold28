<?php
// ======================================================================
// File: app/Livewire/ScenarioLab/Index.php
// ======================================================================

namespace App\Livewire\ScenarioLab;

use Livewire\Component;
use App\Models\Election;
use App\Models\District;
use App\Models\Party;
use App\Models\Result;

class Index extends Component
{
    public array $elections = [];
    public int $electionId;

    public array $districts = [];
    public array $parties = [];

    public array $scenario = [
        'name'  => 'My Scenario',
        // points = percentage points added to a party share in each selected district
        'swing' => [],  // party_short_code => +/- points
        'scope' => [],  // district_id => true
    ];

    public ?array $result = null;

    public function mount(): void
    {
        $this->elections = Election::orderByDesc('election_date')
            ->get(['id','name','election_date'])
            ->map(fn($e) => [
                'id' => (int) $e->id,
                'name' => (string) $e->name,
                'election_date' => $e->election_date?->format('Y-m-d'),
            ])->toArray();

        $this->electionId = $this->elections[0]['id'] ?? 1;

        $this->districts = District::orderBy('name')->get(['id','name'])
            ->map(fn($d) => ['id' => (int)$d->id, 'name' => (string)$d->name])
            ->toArray();

        $this->parties = Party::orderBy('short_code')->get(['id','short_code'])
            ->map(fn($p) => ['id' => (int)$p->id, 'short_code' => strtoupper(trim((string)$p->short_code))])
            ->toArray();

        foreach ($this->parties as $p) {
            $code = $p['short_code'];
            $this->scenario['swing'][$code] = $this->scenario['swing'][$code] ?? 0.0;
        }
    }

    public function run(): void
    {
        $this->result = null;

        // Scope: selected districts; if none selected => all districts
        $scopedIds = array_keys(array_filter($this->scenario['scope'] ?? []));
        $scopedIds = array_map('intval', $scopedIds);

        $useAll = count($scopedIds) === 0;
        $targetDistrictIds = $useAll
            ? array_map(fn($d) => (int)$d['id'], $this->districts)
            : $scopedIds;

        if (count($targetDistrictIds) === 0) {
            $this->addError('scenario.scope', 'Select at least one district.');
            return;
        }

        // Baseline totals: votes per district per party
        $rows = Result::query()
            ->join('parties', 'results.party_id', '=', 'parties.id')
            ->where('results.election_id', $this->electionId)
            ->whereIn('results.district_id', $targetDistrictIds)
            ->selectRaw('results.district_id AS district_id, UPPER(TRIM(parties.short_code)) AS code, SUM(results.votes) AS total_votes')
            ->groupBy('results.district_id')
            ->groupByRaw('UPPER(TRIM(parties.short_code))')
            ->get();

        if ($rows->isEmpty()) {
            $this->addError('electionId', 'No district-level results found for this election. Import results first.');
            return;
        }

        // district => party => votes
        $byDistrict = [];
        foreach ($rows as $r) {
            $did = (int)$r->district_id;
            $code = (string)$r->code;
            $votes = (float)$r->total_votes;
            if (!isset($byDistrict[$did])) $byDistrict[$did] = [];
            $byDistrict[$did][$code] = ($byDistrict[$did][$code] ?? 0.0) + $votes;
        }

        // district id => name
        $districtName = [];
        foreach ($this->districts as $d) $districtName[(int)$d['id']] = (string)$d['name'];

        // swings (points)
        $swings = array_map('floatval', $this->scenario['swing'] ?? []);

        $districtOut = [];

        // NATIONAL totals (votes-based baseline, and scenario built from district-adjusted shares)
        $nationalBaseVotes = []; // code => votes
        $nationalScenVotes = []; // code => votes (synthetic: district total * scen share)

        foreach ($targetDistrictIds as $did) {
            $votesByParty = $byDistrict[$did] ?? [];
            $districtTotal = array_sum($votesByParty);
            if ($districtTotal <= 0) continue;

            // Add baseline votes into national
            foreach ($votesByParty as $code => $v) {
                $nationalBaseVotes[$code] = ($nationalBaseVotes[$code] ?? 0.0) + (float)$v;
            }

            // baseline shares
            $baseShares = [];
            foreach ($votesByParty as $code => $v) $baseShares[$code] = $v / $districtTotal;

            // scenario shares = baseline + points, then clamp and renormalize to 100%
            $scenShares = $baseShares;

            foreach ($this->parties as $p) {
                $code = $p['short_code'];
                if (!array_key_exists($code, $scenShares)) $scenShares[$code] = 0.0;
                $deltaPts = (float)($swings[$code] ?? 0.0);
                $scenShares[$code] += ($deltaPts / 100.0);
            }

            // clamp to [0,1]
            foreach ($scenShares as $code => $s) {
                $scenShares[$code] = max(0.0, min(1.0, (float)$s));
            }

            // renormalize
            $sumScen = array_sum($scenShares);
            if ($sumScen <= 0) {
                $scenShares = $baseShares;
                $sumScen = array_sum($scenShares);
            }
            foreach ($scenShares as $code => $s) {
                $scenShares[$code] = $sumScen > 0 ? $s / $sumScen : 0.0;
            }

            // Build scenario "votes" for national by re-allocating district total using scenario shares
            foreach ($scenShares as $code => $share) {
                $nationalScenVotes[$code] = ($nationalScenVotes[$code] ?? 0.0) + ($districtTotal * (float)$share);
            }

            // District winner cards
            $baseWinner = $this->winner($baseShares);
            $scenWinner = $this->winner($scenShares);

            $districtOut[] = [
                'district' => $districtName[$did] ?? ("District #".$did),

                'baseline_winner' => $baseWinner,
                'baseline_winner_pct' => (int) round(($baseShares[$baseWinner] ?? 0) * 100, 0),

                'scenario_winner' => $scenWinner,
                'scenario_winner_pct' => (int) round(($scenShares[$scenWinner] ?? 0) * 100, 0),

                'changed' => $baseWinner !== $scenWinner,
            ];
        }

        // NATIONAL shares
        $nationalBaseline = $this->toPercentShares($nationalBaseVotes);
        $nationalScenario = $this->toPercentShares($nationalScenVotes);

        $nationalBaseWinner = $this->winner($this->to01Shares($nationalBaseVotes));
        $nationalScenWinner = $this->winner($this->to01Shares($nationalScenVotes));

        // Sort districts: changed first, then name
        usort($districtOut, function ($a, $b) {
            if ($a['changed'] !== $b['changed']) return $a['changed'] ? -1 : 1;
            return strnatcasecmp($a['district'], $b['district']);
        });

        $changedCount = count(array_filter($districtOut, fn($r) => (bool)$r['changed']));

        $this->result = [
            'name' => (string)($this->scenario['name'] ?? 'My Scenario'),
            'election_id' => $this->electionId,
            'scope_count' => count($targetDistrictIds),
            'changed_count' => $changedCount,

            'national' => [
                'baseline_winner' => $nationalBaseWinner,
                'scenario_winner' => $nationalScenWinner,
                'baseline' => $nationalBaseline, // code => int %
                'scenario' => $nationalScenario, // code => int %
            ],

            'note' => 'Points mean “percentage points added to party share” inside scoped districts. National % is computed by summing across the scoped districts.',
            'districts' => $districtOut,
        ];
    }

    private function winner(array $shares01): string
    {
        arsort($shares01);
        return (string) array_key_first($shares01);
    }

    /** votes => shares in 0..1 */
    private function to01Shares(array $votesByParty): array
    {
        $total = array_sum($votesByParty);
        if ($total <= 0) return [];
        $out = [];
        foreach ($votesByParty as $code => $v) {
            $out[(string)$code] = ((float)$v) / $total;
        }
        return $out;
    }

    /** votes => percent integers (0..100) */
    private function toPercentShares(array $votesByParty): array
    {
        $shares01 = $this->to01Shares($votesByParty);
        $out = [];
        foreach ($shares01 as $code => $s) {
            $out[$code] = (int) round($s * 100, 0);
        }
        arsort($out);
        return $out;
    }

    public function render()
    {
        return view('livewire.scenario-lab.index')->layout('layouts.app');
    }
}
