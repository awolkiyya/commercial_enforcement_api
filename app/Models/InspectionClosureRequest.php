<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphMany;


class InspectionClosureRequest extends Model
{
    use HasUuids;


    protected $table = 'inspection_closure_requests';

    protected $guarded = [];

    /**
     * Inspection relationship
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class, 'inspection_id');
    }

    /**
     * User who requested
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Reviewer (optional)
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }


    // =========================
    // RELATION: INSPECTION FILES (FIX)
    // =========================
   
    /**
     * POLYMORPHIC FILES
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    
}