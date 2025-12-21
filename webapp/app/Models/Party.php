<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Party extends Model {
    protected $table ='parties';
    protected $fillable = ['name','short_code','color_hex','is_active'];
    public function results(){ return $this->hasMany(Result::class); }
}
