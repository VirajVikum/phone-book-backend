<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Town extends Model
{
    use HasFactory;
    protected $fillable = ['district_id', 'name'];

    public function district() {
        return $this->belongsTo(District::class);
    }

    public function providers() {
        return $this->belongsToMany(ServiceProvider::class, 'provider_towns', 'town_id', 'provider_id');
    }
}
