<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Owner extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'full_name',
        'national_id',
        'phone',
        'email',
        'created_by',
        'is_active',
    ];

    // =========================
    // RELATIONSHIPS
    // =========================

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}