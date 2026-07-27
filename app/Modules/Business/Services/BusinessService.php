<?php

namespace App\Modules\Business\Services;

use App\Models\Business;
use App\Models\Owner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessService
{
    public function list($request)
    {
        $search = $request->search ? trim($request->search) : null;
    
        return Business::query()
    
            // =========================
            // EAGER LOAD (PERFORMANCE)
            // =========================
            ->with([
                'owner',
                'businessType',
                "city",
                "subcity",
                'wereda',
                'registeredBy'
            ])
    
            // =========================
            // GLOBAL SEARCH ENGINE
            // =========================
            ->when($search, function ($q) use ($search) {
    
                $q->where(function ($sub) use ($search) {
    
                    $term = strtolower($search);
    
                    // =========================
                    // BUSINESS CORE SEARCH
                    // =========================
                    $sub->whereRaw('LOWER(name) ILIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(trade_name) ILIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(license_number) ILIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(tin_number) ILIKE ?', ["%{$term}%"])
    
                        // =========================
                        // OWNER SEARCH (RELATION)
                        // =========================
                        ->orWhereHas('owner', function ($owner) use ($term) {
                            $owner->whereRaw('LOWER(full_name) ILIKE ?', ["%{$term}%"])
                                  ->orWhereRaw('LOWER(national_id) ILIKE ?', ["%{$term}%"])
                                  ->orWhereRaw('LOWER(phone) ILIKE ?', ["%{$term}%"])
                                  ->orWhereRaw('LOWER(email) ILIKE ?', ["%{$term}%"]);
                        })
    
                        // =========================
                        // INSPECTOR / REGISTERED BY
                        // =========================
                        ->orWhereHas('registeredBy', function ($user) use ($term) {
                            $user->whereRaw('LOWER(name) ILIKE ?', ["%{$term}%"])
                                 ->orWhereRaw('LOWER(email) ILIKE ?', ["%{$term}%"]);
                        })
    
                        // =========================
                        // WEREDA SEARCH
                        // =========================
                        ->orWhereHas('wereda', function ($wereda) use ($term) {
                            $wereda->whereRaw('LOWER(name) ILIKE ?', ["%{$term}%"]);
                        });
                });
            })
    
            // =========================
            // FILTERS (INDEX OPTIMIZED)
            // =========================
            ->when($request->business_type_id, fn($q) =>
                $q->where('business_type_id', $request->business_type_id)
            )
    
            ->when($request->wereda_id, fn($q) =>
                $q->where('wereda_id', $request->wereda_id)
            )
    
            ->when($request->status, fn($q) =>
                $q->where('status', $request->status)
            )
    
            ->when($request->registered_by, fn($q) =>
                $q->where('registered_by', $request->registered_by)
            )
    
            // =========================
            // ORDER (NEWEST FIRST)
            // =========================
            ->latest();
    }

    // =========================
    // CREATE
    // =========================
    public function create(array $data): Business
    {
        return DB::transaction(function () use ($data) {

            $user = auth()->user();

            // =========================
            // 1. CREATE OR FIND OWNER
            // =========================
            $owner = Owner::firstOrCreate(
                [
                    'national_id' => $data['nationalIdNumber'] ?? null,
                ],
                [
                    'id' => Str::uuid(),
                    'full_name' => $data['ownerFullName'] ?? null,
                    'phone' => $data['phoneNumber'] ?? null,
                    'email' => $data['email'] ?? null,
                    'created_by' => $user->id,
                    'is_active' => true,
                ]
            );

            $subcity = \App\Models\Subcity::find($data['subcity_id']);


            // =========================
            // 2. CREATE BUSINESS
            // =========================
            return Business::create([
                'id' => Str::uuid(),

                // BUSINESS INFO
                'name' => $data['businessName'] ?? null,
                'business_type_id' => $data['businessTypeId'] ?? null,

                'license_number' => $data['businessLicenseNumber'] ?? null,
                'tin_number' => $data['tinNumber'] ?? null,

                // OWNER RELATION (IMPORTANT FIX)
                'owner_id' => $owner->id,

                // LOCATION
                'latitude' => $data['location']['latitude'] ?? null,
                'longitude' => $data['location']['longitude'] ?? null,
                'location_accuracy' => $data['location']['accuracy'] ?? null,

                // GOVERNANCE — derive city_id from subcity, don't trust the creating user's own city_id
                'city_id' => $subcity?->city_id,
                'subcity_id' => $data['subcity_id'],
                'wereda_id' => $data['wereda_id'],

                'registered_by' => $user->id,
            ]);
        });
    }

    // =========================
    // FIND
    // =========================
    public function find(string $id): Business
    {
        $query = Business::query()
            ->with([
                'owner',
                'businessType',
                "city",
                "subcity",
                'wereda',
                'registeredBy'
            ]);
    
        return $query->findOrFail($id);
    }

    // =========================
    // UPDATE
    // =========================
    public function update(string $id, array $data): Business
    {
        return DB::transaction(function () use ($id, $data) {

            $business = Business::findOrFail($id);

            if (isset($data['ownerFullName']) || isset($data['phoneNumber']) || isset($data['email'])) {
                $owner = $business->owner;

                if ($owner) {
                    $owner->update([
                        'full_name' => $data['ownerFullName'] ?? $owner->full_name,
                        'phone' => $data['phoneNumber'] ?? $owner->phone,
                        'email' => $data['email'] ?? $owner->email,
                    ]);
                }
            }

            // Resolve subcity/wereda safely, falling back to existing values
            $subcityId = $data['subcity_id'] ?? $business->subcity_id;
            $weredaId  = $data['wereda_id'] ?? $business->wereda_id;

            // Derive city_id fresh whenever subcity changes (or on every update, cheaply)
            $cityId = $business->city_id;
            if ($subcityId) {
                $subcity = \App\Models\Subcity::find($subcityId);
                $cityId = $subcity?->city_id ?? $cityId;
            }

            $business->update([
                'name' => $data['businessName'] ?? $business->name,
                'business_type_id' => $data['businessTypeId'] ?? $business->business_type_id,

                'license_number' => $data['businessLicenseNumber'] ?? $business->license_number,
                'tin_number' => $data['tinNumber'] ?? $business->tin_number,

                'latitude' => $data['location']['latitude'] ?? $business->latitude,
                'longitude' => $data['location']['longitude'] ?? $business->longitude,
                'location_accuracy' => $data['location']['accuracy'] ?? $business->location_accuracy,

                // GOVERNANCE — always consistent with each other
                'city_id' => $cityId,
                'subcity_id' => $subcityId,
                'wereda_id' => $weredaId,
            ]);

            return $business;
        });
    }

    // =========================
    // STATUS CHANGE
    // =========================
    public function changeStatus(string $id, string $status): Business
    {
        return DB::transaction(function () use ($id, $status) {

            $business = Business::findOrFail($id);

            $business->update([
                'status' => $status
            ]);

            return $business;
        });
    }

    // =========================================================
// SCOPED QUERY (AUTO LOCATION SECURITY LAYER)
// =========================================================
public function scopedList($request)
{
    $query = $this->list($request);

    $user = auth()->user();

    // =====================================================
    // STRICT LOCATION SCOPE (SECURITY ENFORCED)
    // =====================================================
    if (!empty($user->wereda_id)) {
        $query->where('wereda_id', $user->wereda_id);

    } elseif (!empty($user->subcity_id)) {
        $query->where('subcity_id', $user->subcity_id);

    } elseif (!empty($user->city_id)) {
        $query->where('city_id', $user->city_id);
    }

    return $query;
}
}