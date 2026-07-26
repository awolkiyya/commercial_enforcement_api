<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class Resolution extends Model
{
    use HasUuids;

    protected $table = 'resolutions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'inspection_id',
        'outcome',
        'summary',
        'resolved_by',
        'resolved_at',
        'document_path',
        'status',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * =========================
     * INSPECTION RELATION
     * =========================
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * =========================
     * RESOLVER (USER)
     * =========================
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getSqlSnapshot(): string
{
    return $this->query->toSql();
}
}