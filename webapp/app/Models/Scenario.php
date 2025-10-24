<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scenario extends Model {
    protected $casts = ['changes'=>'array','summary'=>'array'];
    protected $fillable = ['election_id','forecast_run_id','name','changes','summary'];
    public function election(){ return $this->belongsTo(Election::class); }
    public function forecastRun(){ return $this->belongsTo(ForecastRun::class); }
}
