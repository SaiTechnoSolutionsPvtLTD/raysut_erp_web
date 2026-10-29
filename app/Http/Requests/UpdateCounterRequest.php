<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT') ?? false;
    }

    public function rules(): array
    {
        $counterId = $this->route('counter')?->counter_id ?? $this->input('counter_id');

        return [
            'branch_id' => 'required|exists:branches,branch_id',
            'counter_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('counters', 'counter_code')
                    ->where(fn ($q) => $q->where('branch_id', $this->input('branch_id')))
                    ->ignore($counterId, 'counter_id'),
            ],
            'counter_name' => 'required|string|max:100',
            'terminal_id' => 'nullable|string|max:50',
            'assigned_user_id' => 'nullable|exists:users,user_id',
            'is_active' => 'boolean',
        ];
    }
}
