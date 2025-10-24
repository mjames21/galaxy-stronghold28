<?php

namespace App\Livewire\GotvLab;

use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Index extends Component
{
    /** @var array<int,array{id:int,name:string}> */
    public array $regions = [];

    /** @var array<int,float> current priors */
    public array $alpha = [];
    /** @var array<int,float> current priors */
    public array $beta  = [];

    /** @var array<int,float> baselines for comparison (mutable) */
    public array $baselineAlpha = [];
    public array $baselineBeta  = [];

    /** @var array<int,float> immutable original baselines */
    public array $originalBaselineAlpha = [];
    public array $originalBaselineBeta  = [];

    public ?array $summary = null;

    // UI flags
    public bool $showRelative = true; // show/hide Relative Δ %

    // Bulk inputs (optional)
    public ?float $alphaAll = null;
    public ?float $betaAll  = null;

    protected $rules = [
        'alpha.*'   => ['numeric','min:0.5','max:1000'],
        'beta.*'    => ['numeric','min:0.5','max:1000'],
        'alphaAll'  => ['nullable','numeric','min:0.5','max:1000'],
        'betaAll'   => ['nullable','numeric','min:0.5','max:1000'],
    ];

    public function mount(): void
    {
        // Demo regions; replace with DB if needed.
        $this->regions = [
            ['id'=>1,'name'=>'North'],
            ['id'=>2,'name'=>'South'],
            ['id'=>3,'name'=>'East'],
            ['id'=>4,'name'=>'West'],
        ];

        // Initialize priors and baselines
        foreach ($this->regions as $r) {
            $id = $r['id'];
            $this->alpha[$id] = $this->alpha[$id] ?? 3.0;
            $this->beta[$id]  = $this->beta[$id]  ?? 2.0;

            $this->baselineAlpha[$id] = $this->alpha[$id];
            $this->baselineBeta[$id]  = $this->beta[$id];

            $this->originalBaselineAlpha[$id] = $this->baselineAlpha[$id];
            $this->originalBaselineBeta[$id]  = $this->baselineBeta[$id];
        }
    }

    public function simulate(): void
    {
        $this->validate();

        $out = [];
        foreach ($this->regions as $r) {
            $id = $r['id'];
            $a  = max(0.5, (float)($this->alpha[$id] ?? 3));
            $b  = max(0.5, (float)($this->beta[$id]  ?? 2));
            $mean = $a / ($a + $b);

            $a0 = max(0.5, (float)($this->baselineAlpha[$id] ?? $a));
            $b0 = max(0.5, (float)($this->baselineBeta[$id]  ?? $b));
            $mean0 = $a0 / ($a0 + $b0);

            $out[$r['name']] = [
                'alpha'         => $a,
                'beta'          => $b,
                'mean_turnout'  => round($mean, 4),
                'baseline_mean' => round($mean0, 4),
                'delta_pts'     => round(($mean - $mean0) * 100, 2),
                'delta_rel_pct' => $mean0 > 0 ? round((($mean / $mean0) - 1) * 100, 2) : null,
            ];
        }

        $this->summary = [
            'regions' => $out,
            'note'    => 'Demo output — plug in your GOTV Monte Carlo here.',
        ];
    }

    // ----- Baseline controls -----
    public function resetBaselinesToCurrent(): void
    {
        foreach ($this->alpha as $id => $val) {
            $this->baselineAlpha[$id] = $val;
        }
        foreach ($this->beta as $id => $val) {
            $this->baselineBeta[$id] = $val;
        }
    }

    public function restoreOriginalBaselines(): void
    {
        foreach ($this->originalBaselineAlpha as $id => $val) {
            $this->baselineAlpha[$id] = $val;
        }
        foreach ($this->originalBaselineBeta as $id => $val) {
            $this->baselineBeta[$id] = $val;
        }
    }

    // ----- CSV export -----
    public function exportCsv(): StreamedResponse
    {
        $rows = [];

        foreach ($this->regions as $r) {
            $id   = $r['id'];
            $name = $r['name'];
            $a    = max(0.5, (float)($this->alpha[$id] ?? 3));
            $b    = max(0.5, (float)($this->beta[$id]  ?? 2));
            $m    = $a / ($a + $b);

            $a0 = max(0.5, (float)($this->baselineAlpha[$id] ?? $a));
            $b0 = max(0.5, (float)($this->baselineBeta[$id]  ?? $b));
            $m0 = $a0 / ($a0 + $b0);

            $deltaPts = ($m - $m0) * 100;
            $deltaRel = $m0 > 0 ? (($m / $m0) - 1) * 100 : null;

            $rows[] = [
                'Region'        => $name,
                'Alpha (α)'     => number_format($a, 2, '.', ''),
                'Beta (β)'      => number_format($b, 2, '.', ''),
                'Mean (%)'      => number_format($m * 100, 2, '.', ''),
                'Baseline (%)'  => number_format($m0 * 100, 2, '.', ''),
                'Δ pts'         => number_format($deltaPts, 2, '.', ''),
                'Δ %'           => $deltaRel !== null ? number_format($deltaRel, 2, '.', '') : '',
            ];
        }

        $filename = 'gotv_lab_export_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_keys($rows[0] ?? [
                'Region','Alpha (α)','Beta (β)','Mean (%)','Baseline (%)','Δ pts','Δ %'
            ]));
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('livewire.g-o-t-v-lab.index')->layout('layouts.app');
    }
}
