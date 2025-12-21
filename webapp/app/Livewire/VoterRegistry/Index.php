<?php

namespace App\Livewire\VoterRegistry;

use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;

use App\Models\Election;
use App\Models\VoterRegistry;

class Index extends Component
{
    use WithFileUploads;

    public array $elections = [];
    public ?int $electionId = null;

    public ?string $q = null;
    public ?string $district = null;
    public ?string $region = null;

    public int $perPage = 25;

    public $csvFile = null;

    public bool $showEdit = false;
    public ?int $editingId = null;

    public array $form = [
        'region' => null,
        'district' => null,
        'constituency' => null,
        'ward' => null,
        'centre_id' => null,
        'centre_name' => null,
        'station_id' => null,
        'station_code' => null,
        'registered_voters' => null,
    ];

    protected function rules(): array
    {
        return [
            'electionId' => ['required', 'exists:elections,id'],

            'form.region' => ['nullable','string','max:255'],
            'form.district' => ['nullable','string','max:255'],
            'form.constituency' => ['nullable','integer','min:0'],
            'form.ward' => ['nullable','integer','min:0'],
            'form.centre_id' => ['nullable','integer','min:0'],
            'form.centre_name' => ['nullable','string','max:255'],
            'form.station_id' => ['nullable','integer','min:0'],
            'form.station_code' => ['nullable','string','max:50'],
            'form.registered_voters' => ['nullable','integer','min:0'],

            'csvFile' => ['nullable','file','mimes:csv,txt','max:10240'],
        ];
    }

    public function mount(): void
    {
        $this->elections = Election::orderByDesc('election_date')
            ->get(['id','name','election_date'])
            ->map(fn($e) => [
                'id' => (int)$e->id,
                'name' => (string)$e->name,
                'election_date' => $e->election_date?->format('Y-m-d'),
            ])->toArray();

        $this->electionId = $this->elections[0]['id'] ?? null;
    }

    public function updatedElectionId(): void
    {
        $this->q = null;
        $this->district = null;
        $this->region = null;
        $this->showEdit = false;
        $this->editingId = null;
        $this->resetValidation();
    }

    // ------------------- Edit -------------------
    public function openEdit(int $id): void
    {
        $this->validateOnly('electionId');

        $row = VoterRegistry::query()
            ->where('election_id', $this->electionId)
            ->findOrFail($id);

        $this->editingId = $row->id;
        $this->form = [
            'region' => $row->region,
            'district' => $row->district,
            'constituency' => $row->constituency,
            'ward' => $row->ward,
            'centre_id' => $row->centre_id,
            'centre_name' => $row->centre_name,
            'station_id' => $row->station_id,
            'station_code' => $row->station_code,
            'registered_voters' => $row->registered_voters,
        ];

        $this->showEdit = true;
        $this->resetValidation();
    }

    public function closeEdit(): void
    {
        $this->showEdit = false;
        $this->editingId = null;
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        $this->validate();

        $row = VoterRegistry::query()
            ->where('election_id', $this->electionId)
            ->findOrFail($this->editingId);

        $centreId = $this->form['centre_id'];
        $stationId = $this->form['station_id'];

        if ($centreId !== null && $stationId !== null) {
            $exists = VoterRegistry::query()
                ->where('election_id', $this->electionId)
                ->where('centre_id', $centreId)
                ->where('station_id', $stationId)
                ->where('id', '!=', $row->id)
                ->exists();

            if ($exists) {
                $this->addError('form.station_id', 'This CentreID + StationID already exists for this election.');
                return;
            }
        }

        $row->update($this->form);

        session()->flash('status', 'Voter registry updated.');
        $this->closeEdit();
    }

    // ------------------- Import -------------------
    public function importCsv(): void
    {
        $this->validateOnly('electionId');
        $this->validateOnly('csvFile');

        if (!$this->csvFile) {
            $this->addError('csvFile', 'Please select a CSV file.');
            return;
        }

        $path = $this->csvFile->getRealPath();
        if (!$path) {
            $this->addError('csvFile', 'Upload failed. Try again.');
            return;
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->addError('csvFile', 'Could not read file.');
            return;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $this->addError('csvFile', 'CSV is empty.');
            return;
        }

        $headerNorm = array_map(fn($h) => trim((string)$h), $header);

        $idx = function(string $name) use ($headerNorm) {
            $pos = array_search($name, $headerNorm, true);
            return $pos === false ? null : $pos;
        };

        // Your CSV headers
        $colRegion       = $idx('Region');
        $colDistrict     = $idx('District');
        $colConstituency = $idx('Constituency');
        $colWard         = $idx('Ward');
        $colCentreID     = $idx('CentreID');
        $colCentreName   = $idx('CentreName');
        $colStationID    = $idx('StationID');
        $colStationCode  = $idx('ID');
        $colRegVoters    = $idx('RegistredVoters'); // file spelling

        if ($colCentreID === null || $colStationID === null) {
            fclose($handle);
            $this->addError('csvFile', 'Missing CentreID or StationID column.');
            return;
        }

        $toUpsert = [];
        $now = now();

        while (($row = fgetcsv($handle)) !== false) {
            $centreId = $this->toInt($row[$colCentreID] ?? null);
            $stationId = $this->toInt($row[$colStationID] ?? null);

            if ($centreId === null || $stationId === null) continue;

            $toUpsert[] = [
                'election_id' => (int)$this->electionId,
                'region' => $this->toStr($row[$colRegion] ?? null),
                'district' => $this->toStr($row[$colDistrict] ?? null),
                'constituency' => $this->toInt($row[$colConstituency] ?? null),
                'ward' => $this->toInt($row[$colWard] ?? null),
                'centre_id' => $centreId,
                'centre_name' => $this->toStr($row[$colCentreName] ?? null),
                'station_id' => $stationId,
                'station_code' => $this->toStr($row[$colStationCode] ?? null),
                'registered_voters' => $this->toInt($row[$colRegVoters] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        fclose($handle);

        if (count($toUpsert) === 0) {
            $this->addError('csvFile', 'No valid rows found (CentreID/StationID missing).');
            return;
        }

        DB::transaction(function () use ($toUpsert) {
            foreach (array_chunk($toUpsert, 1000) as $chunk) {
                VoterRegistry::upsert(
                    $chunk,
                    ['election_id','centre_id','station_id'],
                    [
                        'region','district','constituency','ward',
                        'centre_name','station_code','registered_voters',
                        'updated_at'
                    ]
                );
            }
        });

        $this->csvFile = null;
        session()->flash('status', 'Voter registry imported successfully.');
        $this->resetValidation();
    }

    // ------------------- Export (Stations) -------------------
    public function exportCsv(): StreamedResponse
    {
        $this->validateOnly('electionId');

        $rows = $this->queryRows()->get([
            'region','district','constituency','ward',
            'centre_id','centre_name','station_id','station_code',
            'registered_voters'
        ]);

        $filename = 'voter_registry_stations_' . $this->electionId . '_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Region','District','Constituency','Ward',
                'CentreID','CentreName','StationID','ID','RegisteredVoters'
            ]);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->region,
                    $r->district,
                    $r->constituency,
                    $r->ward,
                    $r->centre_id,
                    $r->centre_name,
                    $r->station_id,
                    $r->station_code,
                    $r->registered_voters,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ------------------- Export (District Summary) -------------------
    public function exportDistrictSummaryCsv(): StreamedResponse
    {
        $this->validateOnly('electionId');

        $q = VoterRegistry::query()
            ->where('election_id', $this->electionId)
            ->selectRaw('district as district, region as region')
            ->selectRaw('SUM(COALESCE(registered_voters,0)) as total_registered')
            ->selectRaw('COUNT(*) as stations')
            ->selectRaw('COUNT(DISTINCT centre_id) as centres')
            ->groupBy('region', 'district')
            ->orderBy('region')
            ->orderBy('district');

        $rows = $q->get();

        $filename = 'voter_registry_district_summary_' . $this->electionId . '_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Region','District','TotalRegisteredVoters','Stations','Centres']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->region,
                    $r->district,
                    (int)$r->total_registered,
                    (int)$r->stations,
                    (int)$r->centres,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ------------------- Query + helpers -------------------
    private function queryRows()
    {
        $q = VoterRegistry::query()->where('election_id', $this->electionId);

        if ($this->region) $q->where('region', $this->region);
        if ($this->district) $q->where('district', $this->district);

        if ($this->q) {
            $term = trim($this->q);
            $q->where(function($w) use ($term) {
                $w->where('centre_name', 'like', "%{$term}%")
                    ->orWhere('district', 'like', "%{$term}%")
                    ->orWhere('region', 'like', "%{$term}%")
                    ->orWhere('station_code', 'like', "%{$term}%")
                    ->orWhere('centre_id', 'like', "%{$term}%");
            });
        }

        return $q->orderBy('region')->orderBy('district')->orderBy('centre_id')->orderBy('station_id');
    }

    private function toStr($v): ?string
    {
        $s = trim((string)($v ?? ''));
        return $s === '' ? null : $s;
    }

    private function toInt($v): ?int
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        $s = str_replace([',',' '], '', $s);
        if (!is_numeric($s)) return null;
        return (int)$s;
    }

    public function render()
    {
        if (!$this->electionId) {
            return view('livewire.voter-registry.index', [
                'rows' => collect([]),
                'totals' => ['registered'=>0,'stations'=>0,'centres'=>0,'districts'=>0],
                'regionOptions' => [],
                'districtOptions' => [],
                'districtSummary' => collect([]),
            ])->layout('layouts.app');
        }

        $this->validateOnly('electionId');

        $base = VoterRegistry::where('election_id', $this->electionId);

        $totals = [
            'registered' => (int) (clone $base)->sum(DB::raw('COALESCE(registered_voters,0)')),
            'stations'   => (int) (clone $base)->count(),
            'centres'    => (int) (clone $base)->distinct('centre_id')->count('centre_id'),
            'districts'  => (int) (clone $base)->distinct('district')->count('district'),
        ];

        $regions = (clone $base)->whereNotNull('region')->select('region')->distinct()->orderBy('region')->pluck('region')->toArray();
        $districts = (clone $base)->whereNotNull('district')->select('district')->distinct()->orderBy('district')->pluck('district')->toArray();

        $districtSummary = VoterRegistry::query()
            ->where('election_id', $this->electionId)
            ->selectRaw('region as region, district as district')
            ->selectRaw('SUM(COALESCE(registered_voters,0)) as total_registered')
            ->selectRaw('COUNT(*) as stations')
            ->selectRaw('COUNT(DISTINCT centre_id) as centres')
            ->groupBy('region', 'district')
            ->orderBy('region')
            ->orderBy('district')
            ->get();

        $rows = $this->queryRows()->paginate($this->perPage);

        return view('livewire.voter-registry.index', [
            'rows' => $rows,
            'totals' => $totals,
            'regionOptions' => $regions,
            'districtOptions' => $districts,
            'districtSummary' => $districtSummary,
        ])->layout('layouts.app');
    }
}
