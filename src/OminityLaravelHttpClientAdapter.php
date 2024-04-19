<?php

namespace Ominity\Laravel;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Ominity\Api\Exceptions\ApiException;
use Ominity\Api\HttpAdapter\HttpAdapterInterface;

class OminityLaravelHttpClientAdapter implements HttpAdapterInterface
{
    public function send($httpMethod, $url, $headers, $httpBody): ?object
    {
        $contentType = $headers['Content-Type'] ?? 'application/json';
        unset($headers['Content-Type']);

        $response = Http::withBody($httpBody, $contentType)
            ->withHeaders($headers)
            ->send($httpMethod, $url);

        return match (true) {
            $response->noContent() => null,
            $response->failed() => throw ApiException::createFromResponse($response->toPsrResponse(), null),
            empty($response->body()) => throw new ApiException('Ominity response body is empty.'),
            default => $this->parseResponseBody($response),
        };
    }

    private function parseResponseBody(Response $response): ?object
    {
        $body = $response->body();

        $object = @json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException("Unable to decode Ominity response: '{$body}'.");
        }

        return $object;
    }

    public function versionString(): string
    {
        return 'Laravel/HttpClient';
    }
}