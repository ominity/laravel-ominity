<?php

namespace Ominity\Laravel\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Ominity\Api\OminityApiClient;
use Ominity\Laravel\Services\VatValidationService;

class PaymentMethodEnabled implements ValidationRule
{
    protected OminityApiClient $ominityApiClient;

    public function __construct(OminityApiClient $ominityApiClient)
    {
        $this->ominityApiClient = $ominityApiClient;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {   
        $enabled = false;

        try {
            $paymehtmethod = $this->ominityApiClient->settings->paymentmethods->get($value);
            $enabled = $paymehtmethod->isEnabled;
        }
        catch(\Ominity\Api\Exceptions\ApiException $e) {}

        if (! $enabled) {
            $fail('The :attribute must be a an enabled payment method.');
        }
    }
}
