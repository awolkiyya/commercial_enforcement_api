<?php

namespace App\Modules\Business\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Resolve business ID safely (supports both route model binding and raw UUID)
     */
    private function businessId(): string
    {
        $business = $this->route('business')
            ?? $this->route('id')
            ?? $this->route('businessId');
    
        if ($business instanceof \App\Models\Business) {
            return $business->id;
        }
    
        if (!$business) {
            throw new \RuntimeException("Business route parameter is missing or misnamed.");
        }
    
        return $business;
    }

    public function rules(): array
    {
        $businessId = $this->businessId();

        return [

            // =========================
            // BUSINESS TYPE
            // =========================
            'businessTypeId' => [
                'sometimes',
                'required',
                'uuid',
                'exists:business_types,id',
            ],

            // =========================
            // BUSINESS INFO
            // =========================
            'businessName' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'businessLicenseNumber' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('businesses', 'license_number')
                    ->ignore($businessId, 'id'),
            ],

            'tinNumber' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('businesses', 'tin_number')
                    ->ignore($businessId, 'id'),
            ],

            // =========================
            // OWNER INFO
            // =========================
            'ownerFullName' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'nationalIdNumber' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'phoneNumber' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:100',
            ],

            // =========================
            // DESCRIPTION
            // =========================
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],

            // =========================
            // LOCATION
            // =========================
            'location.latitude' => [
                'sometimes',
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'location.longitude' => [
                'sometimes',
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'location.accuracy' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

           // =========================
            // ADMINISTRATIVE LOCATION IDS
            // =========================


            'subcity_id' => 'required|uuid|exists:subcities,id',

            'wereda_id' => 'required|uuid|exists:weredas,id',
        ];
    }
}