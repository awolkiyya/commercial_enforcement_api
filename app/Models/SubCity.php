<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubCity extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'subcities';

    protected $fillable = [
        'city_id',
        'name',
        'code',
    ];

    /**
     * Belongs to City
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Has many Weredas
     */
    public function weredas()
    {
        return $this->hasMany(Wereda::class);
    }
}