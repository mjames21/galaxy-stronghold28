<?php

// app/Livewire/Manage/Elections.php
namespace App\Livewire\Manage;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Election;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class Elections extends Component
{
    use WithFileUploads;

    public $rows = [];

    public $editingId = null;
    public $name = '';
    public $slug = '';
    public $election_date = '';
    public $type = 'presidential';
    public $round = 1;

    public $importFile;
    public $importMessage = null;
    public $importErrors = [];

    public function mount()
    {
        $this->loadRows();
    }

    public function loadRows(): void
    {
        $this->rows = Election::orderBy('election_date','desc')->get()->toArray();
    }

    public function createNew(): void
    {
        $this->editingId     = null;
        $this->name          = '';
        $this->slug          = '';
        $this->election_date = '';
        $this->type          = 'presidential';
        $this->round         = 1;
    }

    public function edit(int $id): void
    {
        $e = Election::findOrFail($id);
        $this->editingId     = $e->id;
        $this->name          = $e->name;
        $this->slug          = $e->slug;
        $this->election_date = optional($e->election_date)->format('Y-m-d');
        $this->type          = $e->type;
        $this->round         = $e->round;
    }

    public function updatedName()
    {
        if (!$this->editingId && !$this->slug && $this->name) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function save(): void
    {
        $data = Validator::make([
            'name'          => $this->name,
            'slug'          => $this->slug,
            'election_date' => $this->election_date,
            'type'          => $this->type,
            'round'         => $this->round,
        ], [
            'name'          => 'required|string|max:255',
            'slug'          => 'required|string|max:255|unique:elections,slug,' . ($this->editingId ?: 'NULL'),
            'election_date' => 'nullable|date',
            'type'          => 'required|string|max:50',
            'round'         => 'required|integer|min:1|max:3',
        ])->validate();

        if ($this->editingId) {
            $e = Election::findOrFail($this->editingId);
            $e->update($data);
        } else {
            Election::create($data);
        }

        $this->loadRows();
        session()->flash('status', 'Election saved.');
    }

    public function import(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $this->importMessage = null;
        $this->importErrors  = [];

        $path = $this->importFile->getRealPath();
        $h = fopen($path, 'r');
        if (!$h) {
            $this->addImportError('Unable to open file.');
            return;
        }

        $header = fgetcsv($h);
        if (!$header) {
            $this->addImportError('Empty/invalid header.');
            fclose($h);
            return;
        }

        $idx = array_flip($header);
        foreach (['name','slug','election_date','type','round'] as $col) {
            if (!isset($idx[$col])) {
                $this->addImportError("Missing required column: {$col}");
                fclose($h);
                return;
            }
        }

        $rows = 0;
        $ins  = 0;
        $up   = 0;

        while (($row = fgetcsv($h)) !== false) {
            $rows++;
            $payload = [
                'name'          => trim($row[$idx['name']] ?? ''),
                'slug'          => trim($row[$idx['slug']] ?? ''),
                'election_date' => trim($row[$idx['election_date']] ?? ''),
                'type'          => trim($row[$idx['type']] ?? 'presidential'),
                'round'         => (int) ($row[$idx['round']] ?? 1),
            ];

            if ($payload['name'] === '' || $payload['slug'] === '') {
                $this->addImportError("Row {$rows}: missing name/slug, skipped.");
                continue;
            }

            $validator = Validator::make($payload, [
                'name'          => 'required|string|max:255',
                'slug'          => 'required|string|max:255',
                'election_date' => 'nullable|date',
                'type'          => 'required|string|max:50',
                'round'         => 'required|integer|min:1|max:3',
            ]);

            if ($validator->fails()) {
                $this->addImportError("Row {$rows}: ".implode('; ', $validator->errors()->all()));
                continue;
            }

            $election = Election::updateOrCreate(
                ['slug' => $payload['slug']],
                $payload
            );

            $election->wasRecentlyCreated ? $ins++ : $up++;
        }

        fclose($h);

        $this->importMessage = "Imported {$rows} rows. Created {$ins}, updated {$up}.";
        $this->importFile    = null;

        $this->loadRows();
    }

    protected function addImportError(string $msg): void
    {
        $this->importErrors[] = $msg;
    }

    public function render()
    {
        return view('livewire.manage.elections')
            ->layout('layouts.app');
    }
}
