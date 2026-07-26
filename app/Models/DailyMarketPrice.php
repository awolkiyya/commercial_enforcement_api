<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class DailyMarketPrice extends Model
{
    use HasFactory;

    protected $table = 'daily_market_prices';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'city_id',
        'market_item_id',
        'price_date',
        'price',
        'currency',
        'price_type',
        'source',
        'confidence_score',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'price_date' => 'date',
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

    public function item()
    {
        return $this->belongsTo(MarketItem::class, 'market_item_id');
    }

    public function city()
    {
        return $this->belongsTo(\App\Models\City::class, 'city_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}