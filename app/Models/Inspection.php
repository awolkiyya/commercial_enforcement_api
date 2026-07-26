<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Andegna\DateTimeFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'business_id',
        'inspection_number',
        'inspector_id',
        'edited_by',
        'closed_by',
        'started_at',
        'completed_at',
        'status',
        'penalty_id',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    // =========================
    // RELATIONS
    // =========================

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function editedBy()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function violations()
    {
        return $this->hasMany(Violation::class);
    }

    public function penalty()
    {
        return $this->belongsTo(Penalty::class);
    }

    public function sequence()
    {
        return $this->hasOne(InspectionSequence::class);
    }

    public function resolution()
    {
        return $this->hasOne(\App\Models\Resolution::class);
    }

    public function participants()
    {
        return $this->hasMany(InspectionParticipant::class);
    }

     /**
     * Closure requests for this inspection
     */
    public function closureRequests(): HasMany
    {
        return $this->hasMany(
            InspectionClosureRequest::class,
            'inspection_id', // 🔥 REQUIRED
            'id'
        );
    }
}