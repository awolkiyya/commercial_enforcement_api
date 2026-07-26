<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ViolationType extends Model
{
    use HasUuids;

    protected $table = 'violation_types';

    protected $fillable = [
        'name',
        'description',
        'severity_level',
        'is_active',
    ];
}