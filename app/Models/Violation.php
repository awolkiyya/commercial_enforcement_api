<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Violation extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'inspection_id',
        'business_id',
        'violation_type_id',
        'inspector_id',
        'description',
    ];

    // =========================
    // RELATIONS
    // =========================

    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function violationType()
    {
        return $this->belongsTo(ViolationType::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}