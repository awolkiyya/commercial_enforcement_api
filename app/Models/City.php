<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * A City has many SubCities
     */
    public function subCities()
    {
        return $this->hasMany(SubCity::class);
    }

    /**
     * A City has many Weredas through SubCities
     */
    public function weredas()
    {
        return $this->hasManyThrough(
            Wereda::class,
            SubCity::class
        );
    }
}