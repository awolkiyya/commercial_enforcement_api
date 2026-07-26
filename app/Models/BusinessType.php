<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessType extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
        'priority_level',
        'is_movable',
        'requires_permanent_address',
        'requires_inspection',
        'inspection_frequency_months',
        'is_active',
    ];

    // =========================
    // RELATIONSHIPS
    // =========================

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }
}