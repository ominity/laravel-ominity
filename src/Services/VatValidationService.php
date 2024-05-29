<?php

namespace Ominity\Laravel\Services;

use Ominity\Api\Exceptions\ApiException;
use Ominity\Api\OminityApiClient;

class VatValidationService
{
    /**
     * Regular expression patterns per country code
     *
     * @link http://ec.europa.eu/taxation_customs/vies/faq.html?locale=en#item_11
     */
    protected static array $pattern_expression = [
        'AT' => 'U[A-Z\d]{8}',
        'BE' => '(0\d{9}|\d{10})',
        'BG' => '\d{9,10}',
        'CY' => '\d{8}[A-Z]',
        'CZ' => '\d{8,10}',
        'DE' => '\d{9}',
        'DK' => '(\d{2} ?){3}\d{2}',
        'EE' => '\d{9}',
        'EL' => '\d{9}',
        'ES' => '[A-Z]\d{7}[A-Z]|\d{8}[A-Z]|[A-Z]\d{8}',
        'FI' => '\d{8}',
        'FR' => '([A-Z]{2}|\d{2})\d{9}',
        'GB' => '\d{9}|\d{12}|(GD|HA)\d{3}',
        'HR' => '\d{11}',
        'HU' => '\d{8}',
        'IE' => '[A-Z\d]{8}|[A-Z\d]{9}',
        'IT' => '\d{11}',
        'LT' => '(\d{9}|\d{12})',
        'LU' => '\d{8}',
        'LV' => '\d{11}',
        'MT' => '\d{8}',
        'NL' => '\d{9}B\d{2}',
        'PL' => '\d{10}',
        'PT' => '\d{9}',
        'RO' => '\d{2,10}',
        'SE' => '\d{12}',
        'SI' => '\d{8}',
        'SK' => '\d{10}',
    ];

    protected OminityApiClient $ominityApiClient;

    public function __construct(OminityApiClient $ominityApiClient)
    {
        $this->ominityApiClient = $ominityApiClient;
    }

    /**
     * Return if a country is supported by this validator
     */
    public static function countryIsSupported(string $country): bool
    {
        return isset(self::$pattern_expression[$country]);
    }

    /**
     * Validate a VAT number format.
     */
    public function validateFormat(string $vatNumber): bool
    {
        $vatNumber = $this->vatCleaner($vatNumber);
        [$country, $number] = $this->splitVat($vatNumber);

        if (! isset(self::$pattern_expression[$country])) {
            return false;
        }

        $validate_rule = preg_match('/^'.self::$pattern_expression[$country].'$/', $number) > 0;

        if ($validate_rule === true && $country === 'IT') {
            $result = self::luhnCheck($number);

            return $result % 10 == 0;
        }

        return $validate_rule;
    }

    /**
     * Check existence VAT number
     */
    public function validateExistence(string $vatNumber): bool
    {
        $vatNumber = $this->vatCleaner($vatNumber);

        $isValid = $this->validateFormat($vatNumber);

        if ($isValid) {
            try {
                $vatValidation = $this->ominityApiClient->commerce->vatvalidations->get($vatNumber);
                $isValid = $vatValidation->isValid;
            } catch (ApiException $e) {
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Validates a VAT number
     */
    public function validate(string $vatNumber): bool
    {
        return $this->validateFormat($vatNumber) && $this->validateExistence($vatNumber);
    }

    /**
     * A php implementation of Luhn Algo
     *
     * @link https://en.wikipedia.org/wiki/Luhn_algorithm
     */
    public static function luhnCheck(string $vat): int
    {
        $sum = 0;
        $vat_array = str_split($vat);
        for ($index = 0; $index < count($vat_array); $index++) {
            $value = intval($vat_array[$index]);
            if ($index % 2) {
                $value = $value * 2;
                if ($value > 9) {
                    $value = 1 + ($value % 10);
                }
            }
            $sum += $value;
        }

        return $sum;
    }

    private function vatCleaner(string $vatNumber): string
    {
        $vatNumber_no_spaces = trim($vatNumber);

        return strtoupper($vatNumber_no_spaces);
    }

    private function splitVat(string $vatNumber): array
    {
        return [
            substr($vatNumber, 0, 2),
            substr($vatNumber, 2),
        ];
    }
}
