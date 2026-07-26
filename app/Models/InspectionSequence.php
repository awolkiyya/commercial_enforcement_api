<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionSequence extends Model
{
    protected $fillable = [
        'year',
        'sequence',
        'inspection_id',
    ];

    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }
}