<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VoterRegistry extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'region',
        'district',
        'constituency',
        'ward',
        'centre_id',
        'centre_name',
        'station_id',
        'station_code',
        'registered_voters',
    ];

    protected $casts = [
        'election_id' => 'integer',
        'constituency' => 'integer',
        'ward' => 'integer',
        'centre_id' => 'integer',
        'station_id' => 'integer',
        'registered_voters' => 'integer',
    ];

    public function election()
    {
        return $this->belongsTo(Election::class);
    }
}
