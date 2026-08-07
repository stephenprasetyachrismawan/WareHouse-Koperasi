<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait CompanyValidationRules
{
    /**
     * Get the validation rules used to validate a new company/tenant name.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function companyNameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }
}
