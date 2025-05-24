<?php

namespace Ominity\Laravel\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SvgHelper
{
    /**
     * Retrieve and optionally inject width, height, and CSS classes into an external SVG.
     *
     * @param string $url
     * @param string|null $width
     * @param string|null $height
     * @param string|null $cssClass
     * @param bool $force Ignore cache and refresh
     * @return string|null
     */
    public static function fetchSvg(string $url, ?string $width = null, ?string $height = null, ?string $cssClass = null, bool $force = false): ?string
    {
        if (!self::isSvg($url)) {
            return null;
        }

        $cacheEnabled = Config::get('ominity.svg.cache.enabled', true);
        $cacheStore = Config::get('ominity.svg.cache.store', 'file');
        $cacheSeconds = Config::get('ominity.svg.cache.expiration', 3600);
        $cacheKey = 'svg_cache_' . md5($url . $width . $height . $cssClass);
        $cache = Cache::store($cacheStore);

        if ($cacheEnabled) {
            if ($force) {
                $svg = self::fetchAndProcessSvg($url, $width, $height, $cssClass);
                if ($svg !== null) {
                    $cache->put($cacheKey, $svg, $cacheSeconds);
                }
                return $svg;
            }

            return $cache->remember($cacheKey, $cacheSeconds, function () use ($url, $width, $height, $cssClass) {
                return self::fetchAndProcessSvg($url, $width, $height, $cssClass);
            });
        }

        return self::fetchAndProcessSvg($url, $width, $height, $cssClass);
    }

    /**
     * Check if a given URL points to an SVG file (based on extension).
     *
     * @param string $url
     * @return bool
     */
    public static function isSvg(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        return $path && str_ends_with(strtolower($path), '.svg');
    }

    private static function fetchAndProcessSvg(string $url, ?string $width, ?string $height, ?string $cssClass): ?string
    {
        try {
            $response = Http::timeout(5)->get($url);

            if ($response->successful()) {
                $svg = $response->body();

                if (preg_match('/<svg([^>]*?)>/', $svg)) {
                    return self::injectSvgAttributes($svg, $width, $height, $cssClass);
                }

                return $svg;
            } else {
                Log::warning("SvgHelper: Failed to fetch SVG from {$url}, status {$response->status()}");
            }
        } catch (\Exception $e) {
            Log::warning("SvgHelper: Exception fetching SVG from {$url}: " . $e->getMessage());
        }

        return null;
    }

    private static function injectSvgAttributes(string $svg, ?string $width, ?string $height, ?string $cssClass): string
    {
        return preg_replace_callback('/<svg([^>]*?)>/', function ($matches) use ($width, $height, $cssClass) {
            $attributes = $matches[1];

            // Inject or append class
            if ($cssClass) {
                if (preg_match('/class=["\']([^"\']*)["\']/', $attributes, $classMatch)) {
                    $existingClasses = $classMatch[1];
                    $newClasses = trim($existingClasses . ' ' . $cssClass);
                    $attributes = preg_replace('/class=["\']([^"\']*)["\']/', 'class="' . e($newClasses) . '"', $attributes);
                } else {
                    $attributes .= ' class="' . e($cssClass) . '"';
                }
            }

            // Inject width and height
            if ($width) {
                $attributes .= ' width="' . e($width) . '"';
            }
            if ($height) {
                $attributes .= ' height="' . e($height) . '"';
            }

            return '<svg' . $attributes . '>';
        }, $svg);
    }
}
