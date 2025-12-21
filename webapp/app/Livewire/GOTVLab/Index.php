<?php

namespace App\Livewire\GotvLab;

use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\{Election, District, GotvPrior};

class Index extends Component
{
    /** Elections dropdown */
    public array $elections = [];
    public ?int $electionId = null;

    /** Districts list (for table) */
    public array $districts = [];

    /** Current priors by district_id */
    public array $alpha = [];
    public array $beta  = [];

    /** Baselines by district_id */
    public array $baselineAlpha = [];
    public array $baselineBeta  = [];

    /** Immutable baselines loaded at page load */
    public array $originalBaselineAlpha = [];
    public array $originalBaselineBeta  = [];

    public ?array $summary = null;

    /** Bulk inputs */
    public ?float $alphaAll = null;
    public ?float $betaAll  = null;

    protected $rules = [
        'electionId' => ['required', 'exists:elections,id'],
        'alpha.*'    => ['numeric','min:0.5','max:1000'],
        'beta.*'     => ['numeric','min:0.5','max:1000'],
        'alphaAll'   => ['nullable','numeric','min:0.5','max:1000'],
        'betaAll'    => ['nullable','numeric','min:0.5','max:1000'],
    ];

    public function mount(): void
    {
        $this->elections = Election::orderByDesc('election_date')
            ->get(['id','name','election_date','round','type'])
            ->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'election_date' => optional($e->election_date)->format('Y-m-d'),
                'round' => $e->round,
                'type' => $e->type,
            ])->toArray();

        $this->electionId = $this->elections[0]['id'] ?? null;

        $this->loadDistrictsAndPriors();
    }

    public function updatedElectionId(): void
    {
        $this->summary = null;
        $this->loadDistrictsAndPriors();
    }

    private function loadDistrictsAndPriors(): void
    {
        if (!$this->electionId) {
            $this->districts = [];
            return;
        }

        $this->districts = District::orderBy('name')
            ->get(['id','name','code','region'])
            ->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'region' => $d->region,
            ])->toArray();

        // Load existing priors for election
        $priors = GotvPrior::where('election_id', $this->electionId)->get()->keyBy('district_id');

        // Initialize arrays
        $this->alpha = [];
        $this->beta = [];
        $this->baselineAlpha = [];
        $this->baselineBeta = [];
        $this->originalBaselineAlpha = [];
        $this->originalBaselineBeta = [];

        foreach ($this->districts as $d) {
            $id = $d['id'];

            $row = $priors->get($id);

            $a = $row?->alpha ?? 3.0;
            $b = $row?->beta  ?? 2.0;

            $ba = $row?->baseline_alpha ?? $a;
            $bb = $row?->baseline_beta  ?? $b;

            $this->alpha[$id] = (float) $a;
            $this->beta[$id]  = (float) $b;

            $this->baselineAlpha[$id] = (float) $ba;
            $this->baselineBeta[$id]  = (float) $bb;

            $this->originalBaselineAlpha[$id] = (float) $ba;
            $this->originalBaselineBeta[$id]  = (float) $bb;
        }
    }

    public function applyBulk(): void
    {
        $this->validateOnly('alphaAll');
        $this->validateOnly('betaAll');

        foreach ($this->districts as $d) {
            $id = $d['id'];
            if ($this->alphaAll !== null) $this->alpha[$id] = (float) $this->alphaAll;
            if ($this->betaAll !== null)  $this->beta[$id]  = (float) $this->betaAll;
        }
    }

    public function savePriors(): void
    {
        $this->validate();

        foreach ($this->districts as $d) {
            $id = $d['id'];

            $a  = max(0.5, (float)($this->alpha[$id] ?? 3));
            $b  = max(0.5, (float)($this->beta[$id]  ?? 2));
            $a0 = max(0.5, (float)($this->baselineAlpha[$id] ?? $a));
            $b0 = max(0.5, (float)($this->baselineBeta[$id]  ?? $b));

            GotvPrior::updateOrCreate(
                ['election_id' => $this->electionId, 'district_id' => $id],
                [
                    'alpha' => $a,
                    'beta'  => $b,
                    'baseline_alpha' => $a0,
                    'baseline_beta'  => $b0,
                ]
            );
        }

        session()->flash('status', 'GOTV priors saved for this election.');
    }

    // ----- Baseline controls -----
    public function resetBaselinesToCurrent(): void
    {
        foreach ($this->alpha as $id => $val) $this->baselineAlpha[$id] = (float) $val;
        foreach ($this->beta as $id => $val)  $this->baselineBeta[$id]  = (float) $val;
    }

    public function restoreOriginalBaselines(): void
    {
        foreach ($this->originalBaselineAlpha as $id => $val) $this->baselineAlpha[$id] = (float) $val;
        foreach ($this->originalBaselineBeta as $id => $val)  $this->baselineBeta[$id]  = (float) $val;
    }

    public function simulate(): void
    {
        $this->validate();

        $out = [];

        foreach ($this->districts as $d) {
            $id = $d['id'];

            $a  = max(0.5, (float)($this->alpha[$id] ?? 3));
            $b  = max(0.5, (float)($this->beta[$id]  ?? 2));
            $mean = $a / ($a + $b);

            $a0 = max(0.5, (float)($this->baselineAlpha[$id] ?? $a));
            $b0 = max(0.5, (float)($this->baselineBeta[$id]  ?? $b));
            $mean0 = $a0 / ($a0 + $b0);

            $out[$d['name']] = [
                'district_code' => $d['code'],
                'region'        => $d['region'],
                'alpha'         => $a,
                'beta'          => $b,
                'mean_turnout'  => round($mean, 4),
                'baseline_mean' => round($mean0, 4),
                'delta_pts'     => round(($mean - $mean0) * 100, 2),
                'delta_rel_pct' => $mean0 > 0 ? round((($mean / $mean0) - 1) * 100, 2) : null,
            ];
        }

        $this->summary = [
            'election_id' => $this->electionId,
            'districts'   => $out,
            'note'        => 'Turnout mean uses Beta(α,β): mean = α/(α+β). This is the planning layer; later you can connect it to Monte Carlo vote simulations.',
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $this->validate();

        $rows = [];

        foreach ($this->districts as $d) {
            $id = $d['id'];

            $a  = max(0.5, (float)($this->alpha[$id] ?? 3));
            $b  = max(0.5, (float)($this->beta[$id]  ?? 2));
            $m  = $a / ($a + $b);

            $a0 = max(0.5, (float)($this->baselineAlpha[$id] ?? $a));
            $b0 = max(0.5, (float)($this->baselineBeta[$id]  ?? $b));
            $m0 = $a0 / ($a0 + $b0);

            $deltaPts = ($m - $m0) * 100;
            $deltaRel = $m0 > 0 ? (($m / $m0) - 1) * 100 : null;

            $rows[] = [
                'District'      => $d['name'],
                'Code'          => $d['code'],
                'Region'        => $d['region'],
                'Alpha (α)'     => number_format($a, 2, '.', ''),
                'Beta (β)'      => number_format($b, 2, '.', ''),
                'Mean (%)'      => number_format($m * 100, 2, '.', ''),
                'Baseline (%)'  => number_format($m0 * 100, 2, '.', ''),
                'Δ pts'         => number_format($deltaPts, 2, '.', ''),
                'Δ %'           => $deltaRel !== null ? number_format($deltaRel, 2, '.', '') : '',
            ];
        }

        $filename = 'gotv_lab_districts_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_keys($rows[0] ?? [
                'District','Code','Region','Alpha (α)','Beta (β)','Mean (%)','Baseline (%)','Δ pts','Δ %'
            ]));
            foreach ($rows as $r) fputcsv($out, $r);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('livewire.g-o-t-v-lab.index')->layout('layouts.app');
    }
}
