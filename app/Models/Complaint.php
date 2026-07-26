<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Complaint extends Model
{
    use HasFactory, HasUuids;

    /**
     * =========================
     * TABLE NAME
     * =========================
     */
    protected $table = 'complaints';

    /**
     * =========================
     * MASS ASSIGNABLE FIELDS
     * =========================
     */
    protected $fillable = [
        'id',
        'inspection_id',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'decision_notes',
    ];

    /**
     * =========================
     * TYPE CASTING
     * =========================
     */
    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * =========================
     * INSPECTION RELATION
     * =========================
     */
    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * =========================
     * FILES (POLYMORPHIC RELATION)
     * =========================
     */
    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * =========================
     * REVIEWED BY (USER)
     * =========================
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}