<?php

namespace App\Livewire\PVTVerifier;

use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public $csv; // upload of PVT reports
    public ?array $summary = null;

    public function upload()
    {
        $this->validate([
            'csv' => 'required|file|mimes:csv,txt|max:20480',
        ]);

        // TODO: parse CSV, store rows to verifications, compute CI & flags
        $this->summary = [
            'note' => 'Demo summary. Wire to Verification ingestion + CI checks.',
            'rows' => 123,
            'consistent' => 118,
            'flagged' => 5,
        ];
    }

    public function render()
    {
        return view('livewire.p-v-t-verifier.index')->layout('layouts.app');
    }
}
