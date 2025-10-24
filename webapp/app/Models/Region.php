<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model {
    protected $fillable = ['name','code','geojson'];
    public function districts(){ return $this->hasMany(District::class); }
}
