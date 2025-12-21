<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GotvPrior extends Model
{
    protected $fillable = [
        'election_id',
        'district_id',
        'alpha',
        'beta',
        'baseline_alpha',
        'baseline_beta',
    ];

    protected $casts = [
        'alpha' => 'float',
        'beta' => 'float',
        'baseline_alpha' => 'float',
        'baseline_beta' => 'float',
    ];

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}
