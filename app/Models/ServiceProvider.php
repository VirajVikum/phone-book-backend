<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class ServiceProvider extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;


    protected $fillable = [
        'name', 'gender', 'mobile_no', 'whatsapp_no', 'address',
        'district_id', 'industries', 'photo_url', 'email'
    ];

    protected $casts = [
        'industries' => 'array'
    ];

    // THESE ARE SAFE EVEN IF COLUMNS DON'T EXIST
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relationships
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function towns()
    {
        return $this->belongsToMany(Town::class, 'provider_towns', 'provider_id', 'town_id');
    }
}