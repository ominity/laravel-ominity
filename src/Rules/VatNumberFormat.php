<?php

namespace Ominity\Laravel\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Ominity\Laravel\Services\VatValidationService;

class VatNumberFormat implements ValidationRule
{
    protected VatValidationService $vatValidationService;
    
    public function __construct(VatValidationService $vatValidationService)
    {
        $this->vatValidationService = $vatValidationService;
    }

    /**
     * Run the validation rule.
     *
     * @param  string $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if(! $this->vatValidationService->validateFormat($value)) {
            $fail('The :attribute must be a valid VAT number foramt.');
        }
    }
}