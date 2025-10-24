<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForecastRun extends Model {
    protected $casts = ['params'=>'array','summary'=>'array'];
    protected $fillable = ['election_id','label','simulations','params','summary'];
    public function election(){ return $this->belongsTo(Election::class); }
}

