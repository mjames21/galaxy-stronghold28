<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verification extends Model {
    protected $casts = ['reported_votes'=>'array'];
    protected $fillable = ['election_id','polling_station_id','reported_turnout','reported_votes','z_score','status'];
    public function election(){ return $this->belongsTo(Election::class); }
    public function pollingStation(){ return $this->belongsTo(PollingStation::class); }
}
