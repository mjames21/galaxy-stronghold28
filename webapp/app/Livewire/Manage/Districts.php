<?php

// app/Livewire/Manage/Districts.php
namespace App\Livewire\Manage;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\District;
use Illuminate\Support\Facades\Validator;

class Districts extends Component
{
    use WithFileUploads;

    public $districts = [];

    // Form fields
    public $editingId = null;
    public $name = '';
    public $code = '';
    public $region = '';

    // Import
    public $importFile;
    public $importMessage = null;
    public $importErrors = [];

    protected $rules = [
        'name'   => 'required|string|max:255',
        'code'   => 'required|string|max:10',
        'region' => 'nullable|string|max:50',
    ];

    public function mount()
    {
        $this->loadDistricts();
    }

    public function loadDistricts(): void
    {
        $this->districts = District::orderBy('name')->get()->toArray();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->editingId = null;
    }

    public function edit(int $id): void
    {
        $d = District::findOrFail($id);
        $this->editingId = $d->id;
        $this->name      = $d->name;
        $this->code      = $d->code;
        $this->region    = $d->region;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $district = District::findOrFail($this->editingId);
            $district->update($data);
        } else {
            District::create($data);
        }

        $this->loadDistricts();
        session()->flash('status', 'District saved successfully.');

        // keep current record on screen
    }

    public function resetForm(): void
    {
        $this->name   = '';
        $this->code   = '';
        $this->region = '';
    }

    public function import(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $this->importMessage = null;
        $this->importErrors  = [];

        $path = $this->importFile->getRealPath();
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
        foreach (['district', 'code', 'region'] as $col) {
            if (!isset($idx[$col])) {
                $this->addImportError("Missing required column: {$col}");
                fclose($handle);
                return;
            }
        }

        $rows   = 0;
        $insert = 0;
        $update = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rows++;
            $payload = [
                'name'   => trim($row[$idx['district']] ?? ''),
                'code'   => strtoupper(trim($row[$idx['code']] ?? '')),
                'region' => trim($row[$idx['region']] ?? ''),
            ];

            if ($payload['name'] === '' || $payload['code'] === '') {
                $this->addImportError("Row {$rows}: missing district or code, skipped.");
                continue;
            }

            // validate row-level (quietly)
            $v = Validator::make($payload, [
                'name'   => 'required|string|max:255',
                'code'   => 'required|string|max:10',
                'region' => 'nullable|string|max:50',
            ]);

            if ($v->fails()) {
                $this->addImportError("Row {$rows}: ".implode('; ', $v->errors()->all()));
                continue;
            }

            $district = District::where('name', $payload['name'])->first();
            if ($district) {
                $district->update($payload);
                $update++;
            } else {
                District::create($payload);
                $insert++;
            }
        }

        fclose($handle);

        $this->loadDistricts();
        $this->importMessage = "Imported {$rows} rows. Created {$insert}, updated {$update}.";
        $this->importFile = null;
    }

    protected function addImportError(string $msg): void
    {
        $this->importErrors[] = $msg;
    }

    public function render()
    {
        return view('livewire.manage.districts')
            ->layout('layouts.app');
    }
}
