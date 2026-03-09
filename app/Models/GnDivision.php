<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GnDivision extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'gn_code',
        'gn_number',
        'ds_division_name',
        'province_name',
        'latitude',
        'longitude',
        'district_id',
    ];

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}
