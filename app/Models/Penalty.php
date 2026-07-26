<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Penalty extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'penalty_type_id',
        'amount',
        'issued_by',
        'due_date',
        'notes',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    // =========================
    // RELATIONS
    // =========================

    public function penaltyType()
    {
        return $this->belongsTo(PenaltyType::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}