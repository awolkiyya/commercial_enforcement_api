<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Business extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'business_type_id',
        'city_id',
        'subcity_id',
        'wereda_id',
        'registered_by',
        'owner_id',
        'name',
        'trade_name',
        'license_number',
        'tin_number',
        'latitude',
        'longitude',
        'status',
        'merged_into',
        'description',
    ];

    // =========================
    // RELATIONSHIPS
    // =========================

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function subcity()
    {
        return $this->belongsTo(SubCity::class);
    }
    public function wereda()
    {
        return $this->belongsTo(Wereda::class);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function mergedInto()
    {
        return $this->belongsTo(Business::class, 'merged_into');
    }

    public function mergedFrom()
    {
        return $this->hasMany(Business::class, 'merged_into');
    }

    public function scopeInInspectorArea($query, $user)
    {
        return $query
            ->where('city_id', $user->city_id)
            ->when($user->subcity_id, function ($q) use ($user) {
                $q->where('subcity_id', $user->subcity_id);
            })
            ->when($user->wereda_id, function ($q) use ($user) {
                $q->where('wereda_id', $user->wereda_id);
            });
    }
}