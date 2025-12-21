<?php

namespace App\Livewire\Manage;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Election;
use App\Models\District;
use App\Models\PollingStation;
use App\Models\Party;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ResultsImport extends Component
{
    use WithFileUploads;

    /** Top-left: election + CSV import */
    public ?int $election_id = null;
    public array $elections = [];

    public $importFile;
    public ?string $importMessage = null;
    public array $importErrors = [];

    /**
     * Manual District Entry (right-hand side)
     */
    public ?int $manualDistrictId = null;
    public ?int $manualRegistered = null;      // optional registered voters
    public array $manualVotes = [];            // [party_id => votes]

    /**
     * Results summary table (bottom)
     * Each element:
     * [
     *   'district_id'    => int,
     *   'district_name'  => string,
     *   'total_votes'    => int,
     *   'party_votes'    => [party_id => votes]
     * ]
     */
    public array $summaryRows = [];

    public function mount(): void
    {
        // List of elections for dropdown
        $this->elections = Election::orderByDesc('election_date')
            ->get(['id', 'name', 'election_date'])
            ->toArray();

        $this->election_id = $this->elections[0]['id'] ?? null;

        // Pre-create manualVotes keys for all parties
        $parties = Party::orderBy('short_code')->get();
        foreach ($parties as $party) {
            $this->manualVotes[$party->id] = null;
        }

        // Load initial summary (if an election is already selected)
        $this->loadSummary();
    }

    public function updatedImportFile(): void
    {
        // Clear messages whenever a new file is chosen
        $this->importMessage = null;
        $this->importErrors  = [];
    }

    public function updatedElectionId(): void
    {
        // When user switches election, refresh the summary table
        $this->loadSummary();
    }

    /**
     * Import district-level CSV like your 2007 file:
     * district, turnout_pct, invalid_votes, valid_votes, total_votes, APC, SLPP, ...
     * We treat each row as a single synthetic polling station per district.
     */
    public function import(): void
    {
        $this->validate([
            'election_id' => 'required|exists:elections,id',
            'importFile'  => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $this->importMessage = null;
        $this->importErrors  = [];

        $path = $this->importFile->getRealPath();
        $fh   = $path ? fopen($path, 'r') : false;

        if (!$fh) {
            $this->addErrorLine('Unable to open uploaded file.');
            return;
        }

        $header = fgetcsv($fh);
        if (!$header || !is_array($header)) {
            $this->addErrorLine('CSV header is missing or invalid.');
            fclose($fh);
            return;
        }

        // Normalise header names (lowercase, trimmed)
        $headerNorm = array_map(function ($h) {
            return strtolower(trim($h));
        }, $header);

        $idx = array_flip($headerNorm);

        // Required for district-wide file
        if (!isset($idx['district']) || !isset($idx['total_votes'])) {
            $this->addErrorLine('CSV must contain at least "district" and "total_votes" columns.');
            fclose($fh);
            return;
        }

        // Map of party short_code => id
        $partyIdByCode = Party::pluck('id', 'short_code')->toArray();

        // Detect party columns in the header (APC, SLPP, PMDC, etc.)
        $partyCols = [];          // [short_code => column_index]
        foreach ($headerNorm as $colIndex => $colName) {
            $code = strtoupper($colName);
            if (isset($partyIdByCode[$code])) {
                $partyCols[$code] = $colIndex;
            }
        }

        if (empty($partyCols)) {
            $this->addErrorLine('No party columns found. Header columns must match Party short codes (e.g. APC, SLPP).');
            fclose($fh);
            return;
        }

        // Map of district name => id
        $districtIdByName = District::pluck('id', 'name')->toArray();

        // Cache one synthetic PS per district
        $psIdByDistrict = [];   // [district_id => polling_station_id]

        $rows     = 0;
        $inserted = 0;
        $skipped  = 0;
        $electionId = (int) $this->election_id;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($fh)) !== false) {
                $rows++;

                $districtName = trim($row[$idx['district']] ?? '');
                if ($districtName === '') {
                    $this->addErrorLine("Row {$rows}: missing district; skipped.");
                    $skipped++;
                    continue;
                }

                $districtId = $districtIdByName[$districtName] ?? null;
                if (!$districtId) {
                    $this->addErrorLine("Row {$rows}: unknown district [{$districtName}]; skipped.");
                    $skipped++;
                    continue;
                }

                $totalVotes = (int) ($row[$idx['total_votes']] ?? 0);

                // Get / create synthetic polling station for this district
                if (!isset($psIdByDistrict[$districtId])) {
                    $code = 'DIST-' . $districtId . '-SYNTH';

                    $ps = PollingStation::firstOrCreate(
                        [
                            'district_id' => $districtId,
                            'code'        => $code,
                        ],
                        [
                            'name'              => 'District aggregate (' . $districtName . ')',
                            'registered_voters' => null,
                        ]
                    );

                    $psIdByDistrict[$districtId] = $ps->id;
                }

                $psId = $psIdByDistrict[$districtId];

                // For each party column, store result
                $rowInserted = false;

                foreach ($partyCols as $partyCode => $colIndex) {
                    $votes = (int) ($row[$colIndex] ?? 0);
                    if ($votes <= 0) {
                        continue;
                    }

                    $partyId = $partyIdByCode[$partyCode] ?? null;
                    if (!$partyId) {
                        $this->addErrorLine("Row {$rows}: unknown party code [{$partyCode}]; skipped this party.");
                        continue;
                    }

                    Result::updateOrCreate(
                        [
                            'election_id'        => $electionId,
                            'district_id'        => $districtId,
                            'polling_station_id' => $psId,
                            'party_id'           => $partyId,
                        ],
                        [
                            'votes'      => $votes,
                            'turnout'    => $totalVotes ?: null,
                            'registered' => null,
                            'meta'       => null,
                        ]
                    );

                    $rowInserted = true;
                    $inserted++;
                }

                if (!$rowInserted) {
                    $this->addErrorLine("Row {$rows}: no positive votes for any known party; skipped.");
                    $skipped++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->addErrorLine('Fatal error: ' . $e->getMessage());
        }

        fclose($fh);
        $this->importFile = null;

        $this->importMessage = "Processed {$rows} rows. Inserted/updated {$inserted}, skipped {$skipped}.";

        // Refresh summary after import
        $this->loadSummary();
    }

    /**
     * Manual district-level entry on the right side.
     * We again create / reuse a synthetic PS per district.
     */
    public function saveDistrictManual(): void
    {
        $this->validate([
            'election_id'      => 'required|exists:elections,id',
            'manualDistrictId' => 'required|exists:districts,id',
            'manualRegistered' => 'nullable|integer|min:0',
        ]);

        $votesArray = $this->manualVotes ?? [];
        $totalVotes = 0;

        foreach ($votesArray as $v) {
            $totalVotes += (int) $v;
        }

        if ($totalVotes <= 0) {
            $this->addErrorLine('Manual entry: please enter at least one positive vote value.');
            return;
        }

        $districtId = (int) $this->manualDistrictId;
        $electionId = (int) $this->election_id;

        DB::transaction(function () use ($districtId, $electionId, $totalVotes, $votesArray) {
            // Synthetic PS per district, shared with CSV imports
            $code = 'DIST-' . $districtId . '-SYNTH';

            $ps = PollingStation::firstOrCreate(
                [
                    'district_id' => $districtId,
                    'code'        => $code,
                ],
                [
                    'name'              => 'District aggregate manual',
                    'registered_voters' => $this->manualRegistered ?: null,
                ]
            );

            $psId = $ps->id;

            // Insert / update each party with votes
            foreach ($votesArray as $partyId => $votes) {
                $votes = (int) $votes;
                if ($votes <= 0) {
                    // You can leave some parties blank
                    continue;
                }

                Result::updateOrCreate(
                    [
                        'election_id'        => $electionId,
                        'district_id'        => $districtId,
                        'polling_station_id' => $psId,
                        'party_id'           => (int) $partyId,
                    ],
                    [
                        'votes'      => $votes,
                        'turnout'    => $totalVotes,
                        'registered' => $this->manualRegistered ?: null,
                        'meta'       => null,
                    ]
                );
            }
        });

        // Reset fields but keep selected district
        $this->manualRegistered = null;
        foreach ($this->manualVotes as $k => $v) {
            $this->manualVotes[$k] = null;
        }

        session()->flash('manual_status', 'District results saved for this election.');

        // Refresh summary
        $this->loadSummary();
    }

    protected function addErrorLine(string $msg): void
    {
        $this->importErrors[] = $msg;
    }

    /**
     * Build summaryRows for the table: per district totals + party votes.
     */
    protected function loadSummary(): void
    {
        if (!$this->election_id) {
            $this->summaryRows = [];
            return;
        }

        $districtNames = District::pluck('name', 'id')->toArray();

        $rows = Result::select(
                'district_id',
                'party_id',
                DB::raw('SUM(votes) as votes')
            )
            ->where('election_id', $this->election_id)
            ->groupBy('district_id', 'party_id')
            ->get();

        $summary = [];

        foreach ($rows as $r) {
            $dId    = (int) $r->district_id;
            $pId    = (int) $r->party_id;
            $votes  = (int) $r->votes;

            if (!isset($summary[$dId])) {
                $summary[$dId] = [
                    'district_id'   => $dId,
                    'district_name' => $districtNames[$dId] ?? ('#' . $dId),
                    'total_votes'   => 0,
                    'party_votes'   => [],
                ];
            }

            $summary[$dId]['party_votes'][$pId] = $votes;
            $summary[$dId]['total_votes']      += $votes;
        }

        // Re-index as simple array
        $this->summaryRows = array_values($summary);
    }

    public function render()
    {
        return view('livewire.manage.results-import', [
            'elections'       => $this->elections,
            'importMessage'   => $this->importMessage,
            'importErrors'    => $this->importErrors,
            'districtOptions' => District::orderBy('name')->get(),
            'parties'         => Party::orderBy('short_code')->get(),
            'summaryRows'     => $this->summaryRows,
        ])->layout('layouts.app');
    }
}
