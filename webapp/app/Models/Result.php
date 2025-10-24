<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model {
    protected $fillable = ['election_id','district_id','polling_station_id','party_id','votes','turnout','registered'];
    public function election(){ return $this->belongsTo(Election::class); }
    public function district(){ return $this->belongsTo(District::class); }
    public function pollingStation(){ return $this->belongsTo(PollingStation::class); }
    public function party(){ return $this->belongsTo(Party::class); }
}