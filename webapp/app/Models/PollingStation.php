<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollingStation extends Model
{
    protected $fillable = [
        'district_id',
        'name',
        'code',
        'registered_voters',
        'centre_name',
        'section',
        'ward',
    ];

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }
}
