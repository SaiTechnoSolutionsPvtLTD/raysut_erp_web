<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT') ?? false;
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->branch_id ?? $this->input('branch_id');

        return [
            'branch_code' => ['required', 'string', 'max:20', Rule::unique('branches', 'branch_code')->ignore($branchId, 'branch_id')],
            'branch_name' => 'required|string|max:150',
            'branch_type' => 'required|string|max:50',
            'city' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ];
    }
}
