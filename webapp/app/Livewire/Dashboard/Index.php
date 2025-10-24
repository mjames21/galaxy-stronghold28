<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Election;
use App\Models\Party;
use App\Models\Result;
use Illuminate\Support\Facades\DB;

class Index extends Component
{
    public $electionsCount;
    public $partiesCount;
    public $totalVotes;
    public $latestElection;

    // Chart datasets
    public array $voteShare = [];   // ['labels'=>[], 'data'=>[]]
    public array $turnoutTrend = []; // ['labels'=>[], 'data'=>[]]

    // KPI cards + alerts (optional)
    public array $cards = [];
    public array $alerts = [];

    public function mount()
    {
        $this->electionsCount = Election::count();
        $this->partiesCount   = Party::count();
        $this->totalVotes     = Result::sum('votes');
        $this->latestElection = Election::latest('election_date')->first();

        $this->cards = [
            ['title'=>'Elections','value'=>number_format($this->electionsCount)],
            ['title'=>'Parties','value'=>number_format($this->partiesCount)],
            ['title'=>'Total Votes','value'=>number_format($this->totalVotes)],
            ['title'=>'Latest','value'=>$this->latestElection?->name ?? '—'],
        ];

        // ----- Vote Distribution (by party) for latest election -----
        if ($this->latestElection) {
            $rows = Result::select('parties.short_code as code', DB::raw('SUM(results.votes) as v'))
                ->join('parties','results.party_id','=','parties.id')
                ->where('results.election_id', $this->latestElection->id)
                ->groupBy('parties.short_code')
                ->orderBy('v','desc')->get();

            $labels = $rows->pluck('code')->all();
            $data   = $rows->pluck('v')->all();
            $sum    = array_sum($data) ?: 1;
            $pct    = array_map(fn($x)=> round($x*100/$sum, 1), $data);

            $this->voteShare = [
                'labels' => $labels,
                'data'   => $pct,  // percentages
            ];
        } else {
            $this->voteShare = ['labels'=>[], 'data'=>[]];
        }

        // ----- Turnout Trend (toy example) -----
        // If you store turnout by date/time, replace this with a real query. Here we aggregate by district as a proxy:
        if ($this->latestElection) {
            $trend = Result::select('district_id', DB::raw('SUM(votes) as v'), DB::raw('MAX(registered) as reg'))
                ->where('election_id', $this->latestElection->id)
                ->groupBy('district_id')
                ->orderBy('district_id')
                ->get();

            $labels = $trend->pluck('district_id')->map(fn($id)=> "D{$id}")->all();
            $data   = $trend->map(fn($r)=> $r->reg ? round($r->v*100/$r->reg, 1) : null)->all();

            $this->turnoutTrend = [
                'labels' => $labels,
                'data'   => $data, // % turnout by district (as a stand-in for time)
            ];
        } else {
            $this->turnoutTrend = ['labels'=>[], 'data'=>[]];
        }
    }

    public function render()
    {
        return view('livewire.dashboard.index')->layout('layouts.app');
    }
}
