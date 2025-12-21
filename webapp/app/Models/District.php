<?php

// app/Models/District.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = ['name', 'code', 'region'];

    public function populations()
    {
        return $this->hasMany(DistrictPopulation::class);
    }
}
