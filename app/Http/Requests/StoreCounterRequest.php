<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('Branch & Shift Operations', 'CREATE') ?? false;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,branch_id',
            'counter_code' => 'required|string|max:20',
            'counter_name' => 'required|string|max:100',
            'terminal_id' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ];
    }
}
