<?php

namespace Ominity\Laravel\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Ominity\Api\OminityApiClient;

class PaymentMethodMandateSupport implements ValidationRule
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
        $mandate = false;

        try {
            $paymehtmethod = $this->ominityApiClient->settings->paymentmethods->get($value);
            $mandate = $paymehtmethod->supportsMandates();
        } catch (\Ominity\Api\Exceptions\ApiException $e) {
        }

        if (! $mandate) {
            $fail('The :attribute must be a a payment method that supports mandates.');
        }
    }
}
