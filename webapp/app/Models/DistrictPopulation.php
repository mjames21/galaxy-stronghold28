<?php

// app/Models/DistrictPopulation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistrictPopulation extends Model
{
    protected $fillable = [
        'district_id',
        'census_year',
        'total_population',
        'population_18_plus',
    ];

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}
