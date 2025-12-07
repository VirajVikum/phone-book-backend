<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class ServiceProvider extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes;


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

    protected static function booted()
{
    static::addGlobalScope('active', function ($builder) {
        $builder->whereNull('deleted_at');
    });
}
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