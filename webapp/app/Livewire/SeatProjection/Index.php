<?php
// app/Livewire/SeatProjection/Index.php
namespace App\Livewire\SeatProjection;

use Livewire\Component;
use App\Models\Election;

class Index extends Component
{
    public array $elections = [];
    public int $electionId;
    public string $method = 'FPTP';
    public int $simulations = 2000;
    public ?int $totalSeats = null;
    public float $alphaSmoothing = 1.0;

    public ?array $result = null;

    // Coalition UI
    public string $coalitionsInput = '';
    public ?int $majorityThreshold = null;
    public ?array $coalitionOutput = null;

    protected $rules = [
        'electionId'      => ['required','integer'],
        'method'          => ['required','in:FPTP,PR_DHONDT,PR_SAINTE_LAGUE,PR_REGIONAL_DHONDT,PR_REGIONAL_SAINTE_LAGUE'],
        'simulations'     => ['required','integer','min:200','max:100000'],
        'alphaSmoothing'  => ['required','numeric','min:0.1','max:10'],
        'totalSeats'      => ['nullable','integer','min:1','max:10000'],
        'coalitionsInput' => ['nullable','string','max:500'],
        'majorityThreshold' => ['nullable','integer','min:1','max:10000'],
    ];

    public function mount(): void
    {
        $this->elections = Election::orderByDesc('election_date')->get(['id','name'])->toArray();
        $this->electionId = $this->elections[0]['id'] ?? 1;
    }

    public function run(): void
    {
        $this->validate();

        // TODO: Replace with real SeatProjectionService using method, N, α, totalSeats.
        $this->result = [
            'method' => $this->method,
            'simulations' => $this->simulations,
            'total_seats' => $this->totalSeats ?? 132,
            'summary' => [
                'SLPP' => ['mean'=>70,'ci95'=>[64,76]],
                'APC'  => ['mean'=>60,'ci95'=>[54,66]],
                'NGC'  => ['mean'=> 2,'ci95'=>[ 0, 4]],
                'C4C'  => ['mean'=> 0,'ci95'=>[ 0, 2]],
            ],
            'samples' => [], // attach simulation samples when wired
        ];
        $this->coalitionOutput = null;
    }

    public function computeCoalitions(): void
    {
        if (!$this->result) return;

        $coalitions = $this->parseCoalitions($this->coalitionsInput);
        $threshold = $this->majorityThreshold ?: (int) floor(($this->result['total_seats'] / 2) + 1);

        // TODO: Compute true probabilities from $this->result['samples'].
        $this->coalitionOutput = [
            'threshold' => $threshold,
            'party' => ['SLPP'=>0.62,'APC'=>0.31,'NGC'=>0.02,'C4C'=>0.01],
            'coalition' => array_map(fn($c) => ['label'=>$c, 'prob'=>0.5], $coalitions),
        ];
    }

    public function render()
    {
        return view('livewire.seat-projection.index')->layout('layouts.app');
    }

    /** @return array<int,string> */
    private function parseCoalitions(string $input): array
    {
        // why: robust split/trim; ignore empties
        $out = [];
        foreach (explode(',', $input) as $chunk) {
            $label = trim($chunk);
            if ($label !== '') $out[] = $label;
        }
        return $out;
    }
}