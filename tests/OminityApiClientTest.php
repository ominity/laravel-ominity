<?php

namespace Ominity\Laravel\Tests;

use Ominity\Api\OminityApiClient;
use Ominity\Laravel\OminityLaravelHttpClientAdapter;
use ReflectionClass;

class OminityApiClientTest extends TestCase
{
    public function test_injected_http_adapter_is_laravel_http_client_adapter()
    {
        $this->assertInstanceOf(
            OminityLaravelHttpClientAdapter::class,
            $this->getUnaccessiblePropertyValue('httpClient')
        );
    }

    public function test_api_key_is_set_on_resolving_api_client()
    {
        config(['ominity.key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxyz']);

        $this->assertEquals(
            'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxyz',
            $this->getUnaccessiblePropertyValue('apiKey')
        );
    }

    public function test_does_not_set_api_key_if_key_is_empty()
    {
        config(['ominity.key' => '']);

        $this->assertEquals(
            null,
            $this->getUnaccessiblePropertyValue('apiKey')
        );
    }

    private function getUnaccessiblePropertyValue(string $propertyName): mixed
    {
        $resolvedInstance = resolve(OminityApiClient::class);

        $reflection = new ReflectionClass($resolvedInstance);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($resolvedInstance);
    }
}
