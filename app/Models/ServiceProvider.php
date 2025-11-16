<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'gender', 'mobile_no', 'whatsapp_no', 'address',
        'district_id', 'industries', 'photo_url'
    ];

    protected $casts = [
        'industries' => 'array'
    ];

    public function district() {
        return $this->belongsTo(District::class);
    }

    public function towns() {
        return $this->belongsToMany(Town::class, 'provider_towns', 'provider_id', 'town_id');
    }
}
