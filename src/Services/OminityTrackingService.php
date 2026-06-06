<?php

namespace Ominity\Laravel\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Ominity\Api\OminityApiClient;
use Ominity\Api\Resources\Cms\Page;

class OminityTrackingService
{
    protected array $config;

    protected array $pageMetadata = [];

    protected ?string $visitorId = null;

    public function __construct(
        protected OminityApiClient $ominity,
        array $config,
        protected Application $app,
    ) {
        $this->config = $config;
    }

    public function isEnabled(): bool
    {
        return ($this->config['enabled'] ?? null) !== false;
    }

    public function shouldDispatch(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if ($this->app->environment('local') && ! ($this->config['send_in_local'] ?? false)) {
            return false;
        }

        return true;
    }

    public function shouldLog(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if ($this->app->environment('local') && ($this->config['log_in_local'] ?? true)) {
            return true;
        }

        return (bool) ($this->config['log_events'] ?? false);
    }

    public function setPageMetadata(array $metadata): static
    {
        $this->pageMetadata = $this->normalizeAssociativeArray($metadata);

        return $this;
    }

    public function mergePageMetadata(array $metadata): static
    {
        $this->pageMetadata = array_replace_recursive(
            $this->pageMetadata,
            $this->normalizeAssociativeArray($metadata)
        );

        return $this;
    }

    public function getPageMetadata(?Request $request = null): array
    {
        if (empty($this->pageMetadata)) {
            return [];
        }

        $request ??= $this->currentRequest();
        if (! $request) {
            return $this->pageMetadata;
        }

        $metadata = $this->pageMetadata;
        $origin = $metadata['origin_resource'] ?? null;
        if (is_array($origin)) {
            $origin['path'] = $request->getPathInfo();
            $origin['canonicalPath'] = $request->getPathInfo();
            $origin['url'] = $request->fullUrl();
            $metadata['origin_resource'] = $this->normalizeAssociativeArray($origin);
        }

        return $metadata;
    }

    public function setPageOrigin(array $origin): static
    {
        return $this->mergePageMetadata([
            'origin_resource' => $origin,
        ]);
    }

    public function setPageOriginFromCmsPage(Page $page, ?string $locale = null): static
    {
        return $this->mergePageMetadata($this->buildCmsPageMetadata($page, $locale));
    }

    public function buildCmsPageMetadata(Page $page, ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $route = $this->buildCmsPageRoute($page, $locale);

        $origin = [
            'resource' => $page->resource ?: 'page',
            'id' => $page->id,
            'slug' => $page->slug ?: null,
            'title' => $page->name ?: null,
            'locale' => $locale,
            'route' => $route,
        ];

        return [
            'origin_resource' => $this->normalizeAssociativeArray($origin),
        ];
    }

    public function buildBootstrapConfiguration(?Request $request = null): array
    {
        $request ??= $this->currentRequest();
        $visitorId = $this->getVisitorId($request);
        $this->queueVisitorCookie($visitorId, $request);

        $frontend = is_array($this->config['frontend'] ?? null)
            ? $this->config['frontend']
            : [];
        $cookieConfig = is_array($this->config['cookie'] ?? null)
            ? $this->config['cookie']
            : [];

        $configuration = [
            'enabled' => $this->isEnabled(),
            'endpoint' => $this->resolveTrackingEndpoint($request),
            'visitorId' => $visitorId,
            'visitorCookie' => [
                'name' => $cookieConfig['name'] ?? '_omtvid',
                'path' => $cookieConfig['path'] ?? '/',
                'maxAgeSeconds' => (int) (($cookieConfig['expiration'] ?? (60 * 24 * 365)) * 60),
                'secure' => $this->resolveSecureCookie($request),
                'sameSite' => $cookieConfig['same_site'] ?? 'lax',
            ],
            'sampleRate' => $this->normalizeSampleRate($frontend['sample_rate'] ?? 1),
            'trackPageViews' => $frontend['track_page_views'] ?? true,
            'trackSessions' => $frontend['track_sessions'] ?? true,
            'trackScrollDepth' => $frontend['track_scroll_depth'] ?? true,
            'scrollDepthThresholds' => $frontend['scroll_depth_thresholds'] ?? [25, 50, 75, 100],
            'trackOutboundClicks' => $frontend['track_outbound_clicks'] ?? true,
            'trackFileDownloads' => $frontend['track_file_downloads'] ?? true,
            'trackFormSubmissions' => $frontend['track_form_submissions'] ?? true,
            'trackCustomClicks' => $frontend['track_custom_clicks'] ?? true,
            'flushQueueOnMount' => $frontend['flush_queue_on_mount'] ?? true,
            'queueKey' => $frontend['queue_key'] ?? '__ominity_tracking_queue_v1',
            'sessionKey' => $frontend['session_key'] ?? '__ominity_tracking_session_v1',
            'maxQueueSize' => (int) ($frontend['max_queue_size'] ?? 50),
            'eventNames' => $this->normalizeAssociativeArray($frontend['event_names'] ?? []),
            'pageMetadata' => $this->getPageMetadata($request),
            'extraMetadata' => $this->normalizeAssociativeArray($frontend['extra_metadata'] ?? []),
        ];

        $userId = $this->resolveAuthenticatedUserId();
        if ($userId !== null) {
            $configuration['userId'] = $userId;
        }

        return $this->normalizeAssociativeArray($configuration, preserveEmptyArrays: true);
    }

    public function renderBootstrapScript(?Request $request = null): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        $configuration = $this->buildBootstrapConfiguration($request);
        $json = json_encode(
            $configuration,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );

        if (! is_string($json)) {
            return '';
        }

        return <<<HTML
<script>
window.__ominityTrackingConfig = {$json};
if (window.OminityTracking && typeof window.OminityTracking.start === 'function') {
    window.OminityTracking.start(window.__ominityTrackingConfig);
}
</script>
HTML;
    }

    public function track(array $input, ?Request $request = null): array
    {
        $request ??= $this->currentRequest();
        $payload = $this->normalizeTrackingPayload($input, $request);
        $visitorId = $payload['visitorId'];

        $this->queueVisitorCookie($visitorId, $request);

        if ($this->shouldLog()) {
            $this->logTrackingEvent($payload, $request, $this->shouldDispatch() ? 'forwarding' : 'logged_only');
        }

        if (! $this->shouldDispatch()) {
            return [
                'success' => true,
                'visitorId' => $visitorId,
            ];
        }

        $headers = $this->buildForwardedHeaders($request);
        if (! empty($headers)) {
            $this->ominity->addRequestHeaders($headers);
        }

        $result = $this->ominity->tracking->events->track($payload);

        return [
            'success' => (bool) ($result->success ?? true),
            'visitorId' => (string) ($result->visitorId ?? $visitorId),
        ];
    }

    public function getVisitorId(?Request $request = null): string
    {
        if (is_string($this->visitorId) && Str::isUuid($this->visitorId)) {
            return $this->visitorId;
        }

        $request ??= $this->currentRequest();
        $cookieName = $this->config['cookie']['name'] ?? '_omtvid';
        $candidate = $request?->cookie($cookieName);

        if (is_string($candidate)) {
            $candidate = trim($candidate);
            if (Str::isUuid($candidate)) {
                return $this->visitorId = $candidate;
            }
        }

        return $this->visitorId = (string) Str::uuid();
    }

    public function queueVisitorCookie(?string $visitorId = null, ?Request $request = null): void
    {
        $visitorId = $visitorId ?: $this->getVisitorId($request);
        if (! Str::isUuid($visitorId)) {
            return;
        }

        $cookieConfig = is_array($this->config['cookie'] ?? null)
            ? $this->config['cookie']
            : [];

        Cookie::queue(Cookie::make(
            $cookieConfig['name'] ?? '_omtvid',
            $visitorId,
            (int) ($cookieConfig['expiration'] ?? (60 * 24 * 365)),
            $cookieConfig['path'] ?? '/',
            null,
            $this->resolveSecureCookie($request),
            false,
            false,
            $cookieConfig['same_site'] ?? 'lax',
        ));
    }

    protected function normalizeTrackingPayload(array $input, ?Request $request = null): array
    {
        $request ??= $this->currentRequest();
        $payload = [];

        $event = trim((string) ($input['event'] ?? ''));
        if ($event === '') {
            throw new \InvalidArgumentException('Tracking event name is required.');
        }

        $payload['event'] = $event;
        $payload['timestamp'] = $this->normalizeTimestamp($input['timestamp'] ?? null);
        $payload['visitorId'] = $this->normalizeVisitorId($input['visitorId'] ?? null) ?: $this->getVisitorId($request);

        $userId = $this->normalizeUserId($input['userId'] ?? null);
        if ($userId === null) {
            $userId = $this->resolveAuthenticatedUserId();
        }
        if ($userId !== null) {
            $payload['userId'] = $userId;
        }

        foreach (['title', 'url', 'referrer'] as $field) {
            $value = $this->normalizeString($input[$field] ?? null);
            if ($value !== null) {
                $payload[$field] = $value;
            }
        }

        $metadata = [];
        if (! empty($this->pageMetadata)) {
            $metadata = $this->getPageMetadata($request);
        }
        if (is_array($input['metadata'] ?? null)) {
            $metadata = array_replace_recursive($metadata, $this->normalizeAssociativeArray($input['metadata']));
        }
        if (! empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        if (is_array($input['utm'] ?? null)) {
            $utm = [];
            foreach ($input['utm'] as $key => $value) {
                $normalized = $this->normalizeString($value);
                if ($normalized !== null) {
                    $utm[(string) $key] = $normalized;
                }
            }

            if (! empty($utm)) {
                $payload['utm'] = $utm;
            }
        }

        return $payload;
    }

    protected function buildCmsPageRoute(Page $page, string $locale): ?array
    {
        $routes = $this->toArrayRecursive($page->routes ?? null);
        if (! is_array($routes) || empty($routes)) {
            return null;
        }

        $route = $routes[$locale] ?? reset($routes);
        if (! is_array($route)) {
            return null;
        }

        $parameters = $this->normalizeAssociativeArray($route['parameters'] ?? [], preserveEmptyArrays: true);

        return $this->normalizeAssociativeArray([
            'resource' => 'route',
            'name' => $route['name'] ?? null,
            'locale' => $route['locale'] ?? $locale,
            'parameters' => empty($parameters) ? null : $parameters,
        ]);
    }

    protected function resolveTrackingEndpoint(?Request $request = null): string
    {
        $path = (string) ($this->config['route']['path'] ?? '/ominity/tracking/events');
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $path = '/'.ltrim($path, '/');
        if ($request) {
            return rtrim($request->getSchemeAndHttpHost(), '/').$path;
        }

        return url($path);
    }

    protected function resolveSecureCookie(?Request $request = null): bool
    {
        $configured = $this->config['cookie']['secure'] ?? null;
        if ($configured !== null) {
            return (bool) $configured;
        }

        $request ??= $this->currentRequest();

        return $request ? $request->isSecure() : true;
    }

    protected function resolveAuthenticatedUserId(): ?int
    {
        $id = Auth::id();

        return is_numeric($id) ? (int) $id : null;
    }

    protected function normalizeSampleRate(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 1.0;
        }

        $value = (float) $value;

        return max(0.0, min(1.0, $value));
    }

    protected function normalizeVisitorId(mixed $value): ?string
    {
        $value = $this->normalizeString($value);

        return $value !== null && Str::isUuid($value) ? $value : null;
    }

    protected function normalizeUserId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    protected function normalizeTimestamp(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        $value = $this->normalizeString($value);
        if ($value !== null) {
            return $value;
        }

        return now()->toIso8601String();
    }

    protected function normalizeString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function buildForwardedHeaders(?Request $request = null): array
    {
        if (! $request) {
            return [];
        }

        $headers = [];

        $requestId = $this->normalizeString($request->headers->get('x-request-id'));
        if ($requestId !== null) {
            $headers['X-Request-Id'] = $requestId;
        }

        $userAgent = $this->normalizeString($request->userAgent());
        if ($userAgent !== null) {
            $headers['User-Agent'] = $userAgent;
        }

        $forwardedFor = $this->normalizeString($request->headers->get('x-forwarded-for'));
        $clientIp = $this->resolveClientIp($request);

        if ($forwardedFor !== null) {
            $headers['X-Forwarded-For'] = $forwardedFor;
        } elseif ($clientIp !== null) {
            $headers['X-Forwarded-For'] = $clientIp;
        }

        if ($clientIp !== null) {
            $headers['X-Real-IP'] = $clientIp;
            $headers['X-Ominity-Client-IP'] = $clientIp;
        }

        return $headers;
    }

    protected function resolveClientIp(Request $request): ?string
    {
        $headerNames = [
            'x-ominity-client-ip',
            'x-vercel-forwarded-for',
            'x-forwarded-for',
            'x-real-ip',
            'cf-connecting-ip',
            'true-client-ip',
            'fastly-client-ip',
            'x-client-ip',
            'x-nf-client-connection-ip',
            'fly-client-ip',
        ];

        foreach ($headerNames as $headerName) {
            $value = $this->normalizeString($request->headers->get($headerName));
            if ($value === null) {
                continue;
            }

            if ($headerName === 'x-forwarded-for') {
                $parts = array_values(array_filter(array_map('trim', explode(',', $value))));
                if (! empty($parts)) {
                    return $parts[0];
                }

                continue;
            }

            return $value;
        }

        $fallback = $this->normalizeString($request->ip());

        return $fallback;
    }

    protected function logTrackingEvent(array $payload, ?Request $request = null, string $mode = 'forwarding'): void
    {
        $context = [
            'mode' => $mode,
            'environment' => $this->app->environment(),
            'payload' => $payload,
        ];

        if ($request) {
            $context['request'] = [
                'path' => $request->path(),
                'ip' => $this->resolveClientIp($request),
                'user_agent' => $request->userAgent(),
            ];
        }

        $channel = $this->normalizeString($this->config['log_channel'] ?? null);
        if ($channel !== null) {
            Log::channel($channel)->info('Ominity tracking event', $context);

            return;
        }

        Log::info('Ominity tracking event', $context);
    }

    protected function currentRequest(): ?Request
    {
        return $this->app->bound('request') ? $this->app->make('request') : null;
    }

    protected function toArrayRecursive(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->toArrayRecursive($item), $value);
        }

        if (is_object($value)) {
            return array_map(fn ($item) => $this->toArrayRecursive($item), get_object_vars($value));
        }

        return $value;
    }

    protected function normalizeAssociativeArray(mixed $value, bool $preserveEmptyArrays = false): array
    {
        $array = $this->toArrayRecursive($value);
        if (! is_array($array)) {
            return [];
        }

        $normalized = [];

        foreach ($array as $key => $item) {
            if (is_array($item)) {
                $normalizedItem = $this->normalizeAssociativeArray($item, $preserveEmptyArrays);
                if ($normalizedItem !== [] || $preserveEmptyArrays) {
                    $normalized[$key] = $normalizedItem;
                }

                continue;
            }

            if ($item === null) {
                continue;
            }

            if (is_string($item)) {
                $item = trim($item);
                if ($item === '') {
                    continue;
                }
            }

            $normalized[$key] = $item;
        }

        return $normalized;
    }
}
