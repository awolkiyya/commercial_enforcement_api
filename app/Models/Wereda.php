<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wereda extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sub_city_id',
        'name',
        'code',
    ];

    /**
     * Wereda belongs to SubCity
     */
    public function subcity()
    {
        return $this->belongsTo(SubCity::class, 'subcity_id');
    }

    /**
     * OPTIONAL: indirect relation to City (safe way)
     */
    public function city()
    {
        return $this->hasOneThrough(
            City::class,
            SubCity::class,
            'id',        // SubCity PK
            'id',        // City PK
            'subcity_id',
            'city_id'
        );
    }
}