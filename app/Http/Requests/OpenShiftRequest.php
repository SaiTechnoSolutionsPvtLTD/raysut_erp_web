<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'counter_id' => ['required', 'integer', 'exists:counters,counter_id'],
            'opening_float' => ['required', 'numeric', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'counter_id.required' => 'Please select a POS counter for this shift.',
            'opening_float.required' => 'Opening float amount is required.',
            'opening_float.min' => 'Opening float cannot be negative.',
        ];
    }
}
