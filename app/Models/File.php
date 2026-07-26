<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'disk',
        'path',
        'original_name',
        'file_name',
        'mime_type',
        'extension',
        'size',
        'category',
        'visibility',
        'uploaded_by',

        // 🔥 REQUIRED FOR uuidMorphs('fileable')
        'fileable_type',
        'fileable_id',
    ];

    // =====================================================
    // POLYMORPHIC RELATION (IMPORTANT)
    // =====================================================
    public function fileable()
    {
        return $this->morphTo();
    }

    // =====================================================
    // URL ACCESSOR
    // =====================================================
    public function getUrlAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }

        return $this->visibility === 'public'
            ? Storage::disk($this->disk)->url($this->path)
            : url("/api/v1/private-file/" . $this->id);
    }
}