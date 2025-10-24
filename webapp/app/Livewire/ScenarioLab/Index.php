<?php
// app/Livewire/ScenarioLab/Index.php

namespace App\Livewire\ScenarioLab;

use Livewire\Component;
use App\Models\Region;
use App\Models\Party;

class Index extends Component
{
    public array $regions = [];
    public array $parties = [];
    public array $scenario = [
        'name'          => 'My Scenario',
        'turnout_delta' => 0,     // points, e.g. +3
        'swing'         => [],    // party_code => +/- %
        'scope'         => [],    // region_id => true
    ];

    public ?array $result = null;

    public function mount(): void
    {
        $this->regions = Region::orderBy('name')->get(['id','name'])->toArray();
        $this->parties = Party::orderBy('short_code')->get(['id','short_code'])->toArray();

        foreach ($this->parties as $p) {
            $this->scenario['swing'][$p['short_code']] = 0.0;
        }
    }

    public function run(): void
    {
        // TODO: Replace with real scenario engine call.
        $scopedIds  = array_keys(array_filter($this->scenario['scope'] ?? []));
        $scopeCount = count($scopedIds);
        $nRuns      = 5000;

        $regional = [];
        foreach (array_slice($this->regions, 0, 6) as $r) {
            $baseSeats = random_int(8, 25);
            $scenSeats = $baseSeats + random_int(-1, 2);

            // Demo turnout (55–72%), shifted by turnout_delta ± small noise
            $baseTurn = random_int(55, 72) / 100;
            $noise    = random_int(-5, 5) / 1000;
            $scenTurn = max(0, min(1, $baseTurn + ($this->scenario['turnout_delta'] ?? 0) / 100 + $noise));

            $regional[] = [
                'region'            => $r['name'],
                'baseline'          => $baseSeats,
                'scenario'          => $scenSeats,
                'delta'             => $scenSeats - $baseSeats,
                'baseline_turnout'  => $baseTurn,
                'scenario_turnout'  => $scenTurn,
                'delta_turnout_pts' => round(($scenTurn - $baseTurn) * 100, 1),
            ];
        }

        $totBaseline = array_sum(array_column($regional, 'baseline'));
        $totScenario = array_sum(array_column($regional, 'scenario'));
        $totDelta    = $totScenario - $totBaseline;

        $this->result = [
            'name'               => $this->scenario['name'],
            'note'               => 'Demo only. Connect to your Monte Carlo engine to replace these placeholders.',
            'n_runs'             => $nRuns,
            'scope_count'        => $scopeCount,
            'delta_seats'        => ['SLPP'=>+2, 'APC'=>-2, 'NGC'=>0, 'C4C'=>0],
            'regional_seats'     => $regional,
            'total_delta_seats'  => $totDelta,
            'regional_totals'    => [
                'baseline' => $totBaseline,
                'scenario' => $totScenario,
                'delta'    => $totDelta,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.scenario-lab.index')->layout('layouts.app');
    }
}
