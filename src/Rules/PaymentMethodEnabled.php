<?php

namespace Ominity\Laravel\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Ominity\Api\Exceptions\ApiException;
use Ominity\Api\OminityApiClient;

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
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $enabled = false;

        try {
            $paymehtmethod = $this->ominityApiClient->settings->paymentmethods->get($value);
            $enabled = $paymehtmethod->isEnabled;
        } catch (ApiException $e) {
        }

        if (! $enabled) {
            $fail('The :attribute must be a an enabled payment method.');
        }
    }
}
