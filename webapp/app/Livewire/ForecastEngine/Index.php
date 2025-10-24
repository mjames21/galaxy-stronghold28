<?php

namespace App\Livewire\ForecastEngine;

use Livewire\Component;
use App\Models\Election;

class Index extends Component
{
    public array $elections = [];
    public int $electionId;
    public int $simulations = 1000;
    public float $alphaSmoothing = 1.0;
    public ?array $output = null;

    public function mount(): void
    {
        // WHY: Provide a working demo; replace with DB/polls as needed.
        $this->elections = [
            [
                'id' => 1,
                'name' => 'Demo National 2018',
                'parties' => [
                    'SLPP' => 0.45,
                    'APC' => 0.40,
                    'PNGC' => 0.15,
                ],
                'turnout' => ['alpha' => 60, 'beta' => 40], // mean ≈ 0.60
            ],
        ];
        $this->electionId = $this->elections[0]['id'];
    }

    public function run(): void
    {
        $election = collect($this->elections)->firstWhere('id', $this->electionId);
        if (!$election) {
            $this->addError('electionId', 'Invalid election selected.');
            return;
        }

        $parties = array_keys($election['parties']);
        $baseShares = $election['parties'];
        $turnA = max(0.001, (float) ($election['turnout']['alpha'] ?? 60));
        $turnB = max(0.001, (float) ($election['turnout']['beta'] ?? 40));

        $scale = max(1.0, 100.0 * $this->alphaSmoothing);
        $alphas = [];
        foreach ($parties as $p) {
            $alphas[$p] = max(0.001, $baseShares[$p] * $scale);
        }

        $sumShares = array_fill_keys($parties, 0.0);
        $sumTurnout = 0.0;

        $N = max(1, (int) $this->simulations);
        for ($i = 0; $i < $N; $i++) {
            $shares = $this->sampleDirichlet($alphas);
            $turnout = $this->sampleBeta($turnA, $turnB);
            foreach ($shares as $p => $s) {
                $sumShares[$p] += $s;
            }
            $sumTurnout += $turnout;
        }

        $meanShares = [];
        foreach ($sumShares as $p => $acc) {
            $meanShares[$p] = $acc / $N;
        }

        $this->output = [
            'national_mean' => $meanShares,
            'mean_turnout' => $sumTurnout / $N,
            'config' => [
                'simulations' => $N,
                'alpha_smoothing' => $this->alphaSmoothing,
                'dirichlet_alphas' => $alphas,
                'turnout_alpha' => $turnA,
                'turnout_beta' => $turnB,
            ],
        ];
    }

    public function resetSim(): void
    {
        $this->reset('output');
    }

    public function render()
    {
       return view('livewire.forecast-engine.index')->layout('layouts.app');
    }

    // ---- Random samplers (Gamma/Normal/Beta/Dirichlet) ----

    private function uniform(): float
    {
        return (mt_rand() / mt_getrandmax()) ?: 1e-12;
    }

    private function sampleNormal(): float
    {
        // Box–Muller
        $u1 = $this->uniform();
        $u2 = $this->uniform();
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
        // Marsaglia & Tsang (2000)
        $d = $k - 1.0 / 3.0;
        $c = 1.0 / sqrt(9.0 * $d);
        while (true) {
            $x = $this->sampleNormal();
            $v = pow(1.0 + $c * $x, 3);
            if ($v <= 0) {
                continue;
            }
            $u = $this->uniform();
            if ($u < 1.0 - 0.0331 * ($x ** 4)) {
                return $d * $v;
            }
            if (log($u) < 0.5 * $x * $x + $d * (1.0 - $v + log($v))) {
                return $d * $v;
            }
        }
    }

    private function sampleBeta(float $a, float $b): float
    {
        $g1 = $this->sampleGamma($a);
        $g2 = $this->sampleGamma($b);
        $sum = $g1 + $g2;
        return $sum > 0 ? $g1 / $sum : 0.5;
    }

    /**
     * @param array<string,float> $alphas
     * @return array<string,float>
     */
    private function sampleDirichlet(array $alphas): array
    {
        $gsum = 0.0;
        $g = [];
        foreach ($alphas as $k => $a) {
            $val = $this->sampleGamma(max(0.001, (float)$a));
            $g[$k] = $val;
            $gsum += $val;
        }
        $out = [];
        foreach ($g as $k => $val) {
            $out[$k] = $gsum > 0 ? $val / $gsum : 0.0;
        }
        return $out;
    }
}
