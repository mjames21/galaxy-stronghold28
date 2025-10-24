<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollingStation extends Model {
    protected $fillable = ['district_id','name','code','registered_voters'];
    public function district(){ return $this->belongsTo(District::class); }
}
