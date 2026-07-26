<?php

namespace App\Modules\Inspection\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'uuid', 'exists:businesses,id'],

            // =========================
            // MODE (IMPORTANT)
            // =========================
            'mode' => ['required', 'in:personal,group'],

            // =========================
            // PARTICIPANTS (GROUP ONLY)
            // =========================
            'participants' => ['nullable', 'array'],

            'participants.*.user_id' => [
                'required',
                'uuid',
                'exists:users,id',
            ],

            // =========================
            // VIOLATIONS
            // =========================
            'violations' => ['required', 'array', 'min:1'],

            'violations.*.violation_type_id' => [
                'required',
                'uuid',
                'exists:violation_types,id',
            ],

            'violations.*.description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            // =========================
            // FLAT PENALTY STRUCTURE (UPDATED)
            // =========================
            // PENALTY
            'penalty.penalty_type_id' => [
                'nullable',
                'uuid',
                'exists:penalty_types,id',
            ],
            'penalty.due_date' => ['nullable', 'date'],
            'penalty.notes' => ['nullable', 'string', 'max:1000'],

            // =========================
            // NOTES
            // =========================
            'notes' => ['nullable', 'string', 'max:2000'],

            // =========================
            // CONFIRMATION
            // =========================
            'confirmed' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_id.required' => 'Business selection is required.',
            'violations.required' => 'At least one violation must be recorded.',
            'violations.min' => 'Add at least one violation before submitting.',
            'mode.required' => 'Inspection mode is required.',
            'mode.in' => 'Mode must be personal or group.',
            'confirmed.accepted' => 'You must confirm before submitting this inspection.',
        ];
    }

    public function attributes(): array
    {
        return [
            'business_id' => 'business',
            'violations.*.violation_type_id' => 'violation type',
            'penalty_type_id' => 'penalty type',
            'participants.*.user_id' => 'participant',
        ];
    }
}