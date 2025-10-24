<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Election extends Model
{
    protected $fillable = ['name','slug','election_date','description','team_id'];

    public function results(){ return $this->hasMany(Result::class); }
    public function forecastRuns(){ return $this->hasMany(ForecastRun::class); }
    public function scenarios(){ return $this->hasMany(Scenario::class); }
    public function verifications(){ return $this->hasMany(Verification::class); }
}
