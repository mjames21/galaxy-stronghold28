<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model {
    protected $fillable = ['region_id','name','code','geojson','seats'];
    public function region(){ return $this->belongsTo(Region::class); }
    public function pollingStations(){ return $this->hasMany(PollingStation::class); }
}
