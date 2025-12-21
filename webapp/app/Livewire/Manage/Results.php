<?php
// File: app/Livewire/Manage/Results.php

namespace App\Livewire\Manage;

use Livewire\Component;
use App\Models\Election;
use App\Models\District;
use App\Models\Party;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Results extends Component
{
    /** Filters */
    public array $elections = [];
    public ?int $electionId = null;

    public array $districts = [];
    public ?int $districtId = null;

    /** Data */
    public array $rows = [];
    public array $parties = [];

    /** Right sidebar form state */
    public ?int $editingDistrictId = null;
    public string $editingDistrictName = '';
    /** @var array<int,int> */
    public array $formVotes = [];
    public int $formTotal = 0;

    public function mount(): void
    {
        $this->elections = Election::orderByDesc('election_date')
            ->get(['id', 'name', 'election_date'])
            ->toArray();

        $this->electionId = $this->elections[0]['id'] ?? null;

        $this->districts = District::orderBy('name')->get(['id','name'])->toArray();
        $this->parties   = Party::orderBy('short_code')->get(['id','name','short_code'])->toArray();

        $this->loadRows();
        $this->createNew();
    }

    public function updatedElectionId(): void
    {
        $this->districtId = null;
        $this->createNew(); // avoid stale editor state
        $this->loadRows();
    }

    public function updatedDistrictId(): void
    {
        $this->createNew(); // keep sidebar clean on filter change
        $this->loadRows();
    }

    protected function loadRows(): void
    {
        if (!$this->electionId) { $this->rows = []; return; }

        $districtNames = District::pluck('name','id')->toArray();

        $q = Result::select('district_id','party_id', DB::raw('SUM(votes) as votes'))
            ->where('election_id', $this->electionId)
            ->groupBy('district_id','party_id');

        if ($this->districtId) {
            $q->where('district_id', $this->districtId);
        }

        $raw = $q->get();

        $summary = [];
        foreach ($raw as $r) {
            $dId = (int) $r->district_id;
            $pId = (int) $r->party_id;
            $v   = (int) $r->votes;

            if (!isset($summary[$dId])) {
                $summary[$dId] = [
                    'district_id'   => $dId,
                    'district_name' => $districtNames[$dId] ?? ('#'.$dId),
                    'total_votes'   => 0,
                    'party_votes'   => [],
                ];
            }

            $summary[$dId]['party_votes'][$pId] = $v;
            $summary[$dId]['total_votes']      += $v;
        }

        usort($summary, static fn($a,$b) => strcmp($a['district_name'], $b['district_name']));
        $this->rows = $summary;
    }

    /** Prepare empty sidebar form. */
    public function createNew(): void
    {
        $this->editingDistrictId   = null;
        $this->editingDistrictName = '';
        $this->formVotes = [];
        foreach ($this->parties as $p) {
            $this->formVotes[$p['id']] = 0;
        }
        $this->formTotal = 0;
    }

    /** Load selected district totals into the form. */
    public function openEditor(int $districtId): void
    {
        if (!$this->electionId) {
            throw ValidationException::withMessages(['electionId' => 'Select an election first.']);
        }

        $d = District::findOrFail($districtId);
        $this->editingDistrictId   = $d->id;
        $this->editingDistrictName = $d->name;

        foreach ($this->parties as $p) {
            $this->formVotes[$p['id']] = 0;
        }

        $existing = Result::select('party_id', DB::raw('SUM(votes) as votes'))
            ->where('election_id', $this->electionId)
            ->where('district_id', $districtId)
            ->groupBy('party_id')
            ->pluck('votes','party_id')
            ->toArray();

        foreach ($existing as $partyId => $votes) {
            if (array_key_exists($partyId, $this->formVotes)) {
                $this->formVotes[$partyId] = (int) $votes;
            }
        }

        $this->recomputeFormTotal();
    }

    /** Reactive recompute when any vote input changes. */
    public function updatedFormVotes(): void
    {
        $this->recomputeFormTotal();
    }

    protected function recomputeFormTotal(): void
    {
        $sum = 0;
        foreach ($this->formVotes as $v) { $sum += max(0, (int)($v ?? 0)); }
        $this->formTotal = $sum;
    }

    /** Persist totals for (election,district,party). */
    public function saveEditor(): void
    {
        if (!$this->electionId || !$this->editingDistrictId) {
            throw ValidationException::withMessages(['form' => 'Missing election or district.']);
        }

        foreach ($this->formVotes as $partyId => $votes) {
            if (!is_numeric($votes) || (int)$votes < 0) {
                throw ValidationException::withMessages([
                    "formVotes.$partyId" => 'Votes must be a non-negative integer.',
                ]);
            }
        }

        DB::transaction(function () {
            foreach ($this->formVotes as $partyId => $votes) {
                $partyId = (int)$partyId; $votes = (int)$votes;

                // why: avoid double count from prior granular rows
                Result::where('election_id', $this->electionId)
                    ->where('district_id', $this->editingDistrictId)
                    ->where('party_id', $partyId)
                    ->delete();

                if ($votes > 0) {
                    Result::create([
                        'election_id' => $this->electionId,
                        'district_id' => $this->editingDistrictId,
                        'party_id'    => $partyId,
                        'votes'       => $votes,
                    ]);
                }
            }
        });

        $this->loadRows();
        session()->flash('status', 'District totals saved.');
        $this->createNew(); // keep sidebar ready for next edit
    }

    public function render()
    {
        return view('livewire.manage.results', [
            'elections' => $this->elections,
            'districts' => $this->districts,
            'parties'   => $this->parties,
            'rows'      => $this->rows,
        ])->layout('layouts.app');
    }
}
