<?php

namespace Ominity\Laravel\Tests;

use Ominity\Api\OminityApiClient;
use Ominity\Laravel\OminityLaravelHttpClientAdapter;
use ReflectionClass;

class OminityApiClientTest extends TestCase
{
    public function testInjectedHttpAdapterIsLaravelHttpClientAdapter()
    {
        $this->assertInstanceOf(
            OminityLaravelHttpClientAdapter::class,
            $this->getUnaccessiblePropertyValue('httpClient')
        );
    }

    public function testApiKeyIsSetOnResolvingApiClient()
    {
        config(['ominity.key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxyz']);

        $this->assertEquals(
            'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxyz',
            $this->getUnaccessiblePropertyValue('apiKey')
        );
    }

    public function testDoesNotSetApiKeyIfKeyIsEmpty()
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
