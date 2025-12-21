<?php

// app/Models/Election.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Election extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'election_date',
        'type',
        'round',
    ];

    protected $casts = [
        'election_date' => 'date',
    ];
}
