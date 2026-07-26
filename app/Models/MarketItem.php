<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class MarketItem extends Model
{
    use HasFactory;

    protected $table = 'market_items';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'category_id',
        'name',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    // ─────────────────────────────
    // Relationships
    // ─────────────────────────────

    public function category()
    {
        return $this->belongsTo(MarketCategory::class, 'category_id');
    }

    public function prices()
    {
        return $this->hasMany(DailyMarketPrice::class, 'market_item_id');
    }
}