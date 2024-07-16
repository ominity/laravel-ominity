<?php

namespace Ominity\Laravel;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Ominity\Api\Exceptions\ApiException;
use Ominity\Api\HttpAdapter\HttpAdapterInterface;

class OminityLaravelHttpClientAdapter implements HttpAdapterInterface
{
    /**
     * Send a request to the specified Ominity api url.
     *
     * @param string $httpMethod
     * @param string $url
     * @param string|array $headers
     * @param string $httpBody
     * @return \stdClass|string|null
     * @throws \Ominity\Api\Exceptions\ApiException
     */
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
            default => $this->handleResponse($response),
        };
    }

    private function handleResponse(Response $response): ?object
    {
        $contentType = $response->header('Content-Type');

        if (stripos($contentType, 'application/json') !== false || stripos($contentType, 'application/hal+json') !== false) {
            return $this->parseResponseBody($response);
        }

        // For binary responses
        if (stripos($contentType, 'application/pdf') !== false || stripos($contentType, 'application/octet-stream') !== false) {
            return (object) ['body' => $response->body()];
        }

        throw new ApiException("Unsupported Content-Type: {$contentType}");
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
