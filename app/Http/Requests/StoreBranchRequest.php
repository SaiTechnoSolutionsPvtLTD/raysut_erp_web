<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('Branch & Shift Operations', 'CREATE') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('company_id') || empty($this->company_id)) {
            $this->merge([
                'company_id' => Company::first()?->company_id ?? 1,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,company_id',
            'branch_code' => 'required|string|max:20|unique:branches,branch_code',
            'branch_name' => 'required|string|max:150',
            'branch_type' => 'required|string|max:50',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }
}
