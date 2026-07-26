<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InspectionParticipant extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'inspection_id',
        'user_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // =========================
    // RELATION: Inspection
    // =========================
    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    // =========================
    // RELATION: User
    // =========================
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}