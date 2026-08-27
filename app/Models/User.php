<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens; 


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\City;
use App\Models\SubCity;
use App\Models\Wereda;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasUuids, HasFactory, Notifiable, HasRoles,HasApiTokens;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar_file_id',
        "role",

        // governance scope
        'level',
        'city_id',
        'subcity_id',
        'wereda_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',

            'is_active' => 'boolean',
            'failed_login_attempts' => 'integer',

            'password' => 'hashed',
        ];
    }

    /**
     * =====================================================
     * RELATIONS
     * =====================================================
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function subcity(): BelongsTo
    {
        return $this->belongsTo(SubCity::class);
    }

    public function wereda(): BelongsTo
    {
        return $this->belongsTo(Wereda::class);
    }

    /**
     * =====================================================
     * ROLE HELPERS
     * =====================================================
     */
    public function isSystemAdmin(): bool
    {
        return $this->hasRole('SUPER_ADMIN');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ADMIN');
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole('SUPERVISOR');
    }

    public function isInspector(): bool
    {
        return $this->hasRole('INSPECTOR');
    }

    /**
     * =====================================================
     * LEVEL HELPERS
     * =====================================================
     */
    public function isCityLevel(): bool
    {
        return $this->level === 'CITY';
    }

    public function isSubCityLevel(): bool
    {
        return $this->level === 'SUBCITY';
    }

    public function isWeredaLevel(): bool
    {
        return $this->level === 'WEREDA';
    }

    /**
     * =====================================================
     * CORE GOVERNANCE SCOPE (RAW DATA)
     * =====================================================
     */
    public function accessScope(): array
    {
        return [
            'level' => $this->level,
            'city_id' => $this->city_id,
            'subcity_id' => $this->subcity_id,
            'wereda_id' => $this->wereda_id,
        ];
    }

    /**
     * =====================================================
     * EFFECTIVE ACCESS TYPE (IMPORTANT FOR SERVICES)
     * =====================================================
     */
    public function canAccessAll(): bool
    {
        return $this->isSystemAdmin();
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function avatarFile()
    {
        return $this->belongsTo(File::class, 'avatar_file_id');
    }

    public function getAvatarAttribute(): ?string
    {
        return app(\App\Services\Storage\StorageService::class)
            ->url($this->avatarFile);
    }

    public function scopeFilterByUser($query, User $user)
{
    // SUPER ADMIN → full access
    if ($user->isSystemAdmin()) {
        return $query;
    }

    // ADMIN → depends on level but still higher privilege
    if ($user->isAdmin()) {
        return $query->where(function ($q) use ($user) {

            if ($user->isCityLevel()) {
                $q->where('city_id', $user->city_id);
            }

            if ($user->isSubCityLevel()) {
                $q->where('subcity_id', $user->subcity_id)
                  ->orWhere('city_id', $user->city_id);
            }

            if ($user->isWeredaLevel()) {
                $q->where('wereda_id', $user->wereda_id)
                  ->orWhere('subcity_id', $user->subcity_id);
            }
        });
    }

    // SUPERVISOR → same level only
    if ($user->isSupervisor()) {
        return $query->where(function ($q) use ($user) {

            if ($user->isCityLevel()) {
                $q->where('city_id', $user->city_id);
            }

            if ($user->isSubCityLevel()) {
                $q->where('subcity_id', $user->subcity_id);
            }

            if ($user->isWeredaLevel()) {
                $q->where('wereda_id', $user->wereda_id);
            }
        });
    }

    // INSPECTOR → strict isolation
    if ($user->isInspector()) {
        return $query->where('id', $user->id);
    }

    return $query->whereRaw('1 = 0');
    }

    public function inspectionParticipations()
    {
        return $this->hasMany(InspectionParticipant::class);
    }
        

}