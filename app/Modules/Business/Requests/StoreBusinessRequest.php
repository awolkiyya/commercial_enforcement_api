<?php

namespace App\Modules\Business\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            // =========================
            // BUSINESS TYPE (CORE LOGIC)
            // =========================
            'businessTypeId' => 'required|uuid|exists:business_types,id',

            // =========================
            // BUSINESS INFO
            // =========================
            'businessName' => 'required|string|max:255',

            'businessLicenseNumber' => [
                'nullable',
                'string',
                'max:100',
                'unique:businesses,license_number'
            ],

            'tinNumber' => [
                'nullable',
                'string',
                'max:100',
                'unique:businesses,tin_number'
            ],

            // =========================
            // OWNER INFO
            // =========================
            'ownerFullName' => 'required|string|max:255',

            'nationalIdNumber' => 'nullable|string|max:50',

            'phoneNumber' => 'nullable|string|max:20',

            'email' => 'nullable|email|max:100',

            // =========================
            // DESCRIPTION
            // =========================
            'description' => 'nullable|string|max:500',

            // =========================
            // LOCATION (HYBRID MODEL)
            // =========================

            'location.latitude' => 'nullable|numeric|between:-90,90',

            'location.longitude' => 'nullable|numeric|between:-180,180',


            'location.accuracy' => 'nullable|numeric|min:0',

            // =========================
            // ADMINISTRATIVE LOCATION IDS
            // =========================


            'subcity_id' => 'required|uuid|exists:subcities,id',

            'wereda_id' => 'required|uuid|exists:weredas,id',
        ];
    }
}