<?php

namespace App\Livewire\Manage;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Validator;
use App\Models\District;
use App\Models\DistrictPopulation;

class Populations extends Component
{
    use WithFileUploads;

    /** @var \Illuminate\Support\Collection|\App\Models\DistrictPopulation[] */
    public $rows;

    /** @var array<int,int> */
    public $years = [];

    // Filter
    public string $filterYear = 'all';   // "all" | specific year

    // Form fields
    public ?int $editingId = null;
    public string $district_id = '';
    public string $census_year = '';
    public string $total_population = '';
    public ?string $population_18_plus = '';

    // Import
    public $importFile;
    public ?string $importMessage = null;
    public array $importErrors = [];

    public function mount(): void
    {
        $this->years = DistrictPopulation::select('census_year')
            ->distinct()
            ->orderBy('census_year', 'desc')
            ->pluck('census_year')
            ->toArray();

        // default: show all years
        $this->filterYear = 'all';

        $this->loadRows();
        $this->createNew();
    }

    /**
     * Load rows according to current filter.
     */
    public function loadRows(): void
    {
        $q = DistrictPopulation::with('district')
            ->orderBy('census_year', 'desc')
            ->orderBy(
                District::select('name')
                    ->whereColumn('districts.id', 'district_populations.district_id')
            );

        if ($this->filterYear !== 'all') {
            $q->where('census_year', (int) $this->filterYear);
        }

        $this->rows = $q->get();
    }

    /**
     * Reactive filter — reload when year changes.
     */
    public function updatedFilterYear(): void
    {
        $this->loadRows();
    }

    /**
     * Prepare form for a new record.
     */
    public function createNew(): void
    {
        $this->editingId        = null;
        $this->district_id      = '';
        $this->census_year      = $this->filterYear !== 'all'
            ? (string) $this->filterYear
            : (string) (now()->year);
        $this->total_population   = '';
        $this->population_18_plus = '';
    }

    /**
     * Load an existing record into the form.
     */
    public function edit(int $id): void
    {
        $p = DistrictPopulation::findOrFail($id);

        $this->editingId          = $p->id;
        $this->district_id        = (string) $p->district_id;
        $this->census_year        = (string) $p->census_year;
        $this->total_population   = (string) $p->total_population;
        $this->population_18_plus = $p->population_18_plus !== null
            ? (string) $p->population_18_plus
            : '';
    }

    /**
     * Create / update record.
     */
    public function save(): void
    {
        $data = Validator::make([
            'district_id'        => $this->district_id,
            'census_year'        => $this->census_year,
            'total_population'   => $this->total_population,
            'population_18_plus' => $this->population_18_plus,
        ], [
            'district_id'        => 'required|exists:districts,id',
            'census_year'        => 'required|integer|min:1900|max:2100',
            'total_population'   => 'required|integer|min:0',
            'population_18_plus' => 'nullable|integer|min:0',
        ])->validate();

        if ($this->editingId) {
            $row = DistrictPopulation::findOrFail($this->editingId);
            $row->update($data);
        } else {
            DistrictPopulation::updateOrCreate(
                [
                    'district_id' => $data['district_id'],
                    'census_year' => $data['census_year'],
                ],
                $data
            );
        }

        // refresh year list
        $this->years = DistrictPopulation::select('census_year')
            ->distinct()
            ->orderBy('census_year', 'desc')
            ->pluck('census_year')
            ->toArray();

        // set filter to that year, or keep "all" if user was seeing all
        if ($this->filterYear !== 'all') {
            $this->filterYear = (string) $data['census_year'];
        }

        $this->loadRows();
        session()->flash('status', 'Population record saved.');

        // reset form for another entry
        $this->createNew();
    }

    /**
     * CSV import: columns district,census_year,total_population,population_18_plus
     */
    public function import(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $this->importMessage = null;
        $this->importErrors  = [];

        $path   = $this->importFile->getRealPath();
        $handle = fopen($path, 'r');

        if (!$handle) {
            $this->addImportError('Unable to open uploaded file.');
            return;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            $this->addImportError('Empty or invalid CSV header.');
            fclose($handle);
            return;
        }

        $idx = array_flip($header);
        foreach (['district', 'census_year', 'total_population'] as $col) {
            if (!isset($idx[$col])) {
                $this->addImportError("Missing required column: {$col}");
                fclose($handle);
                return;
            }
        }

        $districtMap = District::pluck('id', 'name');

        $rows = 0;
        $created = 0;
        $updated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rows++;

            $districtName = trim($row[$idx['district']] ?? '');
            $year         = (int) ($row[$idx['census_year']] ?? 0);
            $total        = (int) ($row[$idx['total_population']] ?? 0);
            $adult        = isset($idx['population_18_plus'])
                ? (int) ($row[$idx['population_18_plus']] ?? 0)
                : null;

            if ($districtName === '' || $year === 0 || $total === 0) {
                $this->addImportError("Row {$rows}: missing district/year/population, skipped.");
                continue;
            }

            $districtId = $districtMap[$districtName] ?? null;
            if (!$districtId) {
                $this->addImportError("Row {$rows}: unknown district [{$districtName}], skipped.");
                continue;
            }

            $payload = [
                'district_id'        => $districtId,
                'census_year'        => $year,
                'total_population'   => $total,
                'population_18_plus' => $adult ?: null,
            ];

            $validator = Validator::make($payload, [
                'district_id'        => 'required|exists:districts,id',
                'census_year'        => 'required|integer|min:1900|max:2100',
                'total_population'   => 'required|integer|min:0',
                'population_18_plus' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                $this->addImportError(
                    "Row {$rows}: " . implode('; ', $validator->errors()->all())
                );
                continue;
            }

            $model = DistrictPopulation::updateOrCreate(
                ['district_id' => $districtId, 'census_year' => $year],
                $payload
            );

            $model->wasRecentlyCreated ? $created++ : $updated++;
        }

        fclose($handle);

        $this->importMessage = "Imported {$rows} rows. Created {$created}, updated {$updated}.";
        $this->importFile    = null;

        // refresh datasets
        $this->years = DistrictPopulation::select('census_year')
            ->distinct()
            ->orderBy('census_year', 'desc')
            ->pluck('census_year')
            ->toArray();

        if ($this->filterYear !== 'all' && in_array((int)$this->filterYear, $this->years, true)) {
            // keep existing filter
        } else {
            $this->filterYear = 'all';
        }

        $this->loadRows();
    }

    protected function addImportError(string $msg): void
    {
        $this->importErrors[] = $msg;
    }

    public function render()
    {
        return view('livewire.manage.populations', [
            'districtOptions' => District::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
