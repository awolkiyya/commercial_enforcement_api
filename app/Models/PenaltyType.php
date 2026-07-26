<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PenaltyType extends Model
{
    use HasUuids;

    protected $fillable = [

        // BASIC INFO
        'name',
        'description',
        'category',
        'status',

        // ENFORCEMENT RULES
        'requires_due_date',
        'is_final_action',
        'allows_escalation',
        'stops_inspection_flow',
    ];

    protected $casts = [
        'status' => 'boolean',
        'requires_due_date' => 'boolean',
        'is_final_action' => 'boolean',
        'allows_escalation' => 'boolean',
        'stops_inspection_flow' => 'boolean',
    ];

    /**
     * If you still need to link penalties applied to inspections
     */
    public function inspectionPenalties()
    {
        return $this->hasMany(InspectionPenalty::class);
    }
}