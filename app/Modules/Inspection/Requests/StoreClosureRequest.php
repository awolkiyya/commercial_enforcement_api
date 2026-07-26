<?php

namespace App\Modules\Inspection\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClosureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|min:20|max:2000',

            // Evidence is required
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:20480', // 20 MB
        ];
    }

    public function messages(): array
    {
        return [

            // Message
            'message.required' => 'Ibsi cufiinsaa dirqama.',
            'message.string' => 'Ibsi barruu sirrii ta\'uu qaba.',
            'message.min' => 'Ibsi cufiinsaa qubee 20 ol ta\'uu qaba.',
            'message.max' => 'Ibsi cufiinsaa qubee 2000 caaluu hin qabu.',

            // Files
            'files.required' => 'Ragaa ol fe\'uun dirqama.',
            'files.array' => 'Ragaan sirnaan dhiyaachuu qaba.',
            'files.min' => 'Yoo xiqqaate ragaa tokko ol fe\'uu qabdu.',

            'files.*.file' => 'Faayiliin galfame sirrii miti.',
            'files.*.max' => 'Faayiliin tokko MB 20 caaluu hin qabu.',
        ];
    }
}