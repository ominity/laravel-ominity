<?php

namespace Ominity\Laravel;

use Illuminate\Support\Facades\Cache;
use Ominity\Api\OminityApiClient;
use Ominity\Api\Resources\Cms\Page;
use Ominity\Laravel\Exceptions\PageConentNotIncludedException;
use Ominity\Laravel\Services\OminityTrackingService;
use Ominity\Laravel\Views\Components\OminityComponent;

class OminityPageRenderer
{
    protected $ominity;

    public function __construct(
        OminityApiClient $ominity,
        protected OminityTrackingService $tracking,
    ) {
        $this->ominity = $ominity;
    }

    /**
     * Get rendered HTML for a page by ID
     *
     * @param  string|null  $language  Get language in specific language, defaults to app locale
     * @param  bool  $forced  Force a render even if caching exists
     * @return string
     */
    public function renderPagebyId(int $pageId, $language = null, $forced = false)
    {
        if ($language === null) {
            $language = app()->getLocale();
        }

        $cacheKey = md5("ominity_page_{$pageId}_{$language}");
        $cacheConfig = config('ominity.pages.cache');
        if ($cacheConfig['enabled']) {
            $cacheStore = Cache::store($cacheConfig['store'] ?? 'file');
            if ($cacheStore->has($cacheKey) && ! $forced) {
                if (! $this->applyCachedTrackingMetadata($cacheStore, $cacheKey)) {
                    $this->ominity->setLanguage($language);
                    $this->applyPageTrackingMetadata($this->ominity->cms->pages->get($pageId), $language);
                }

                return $cacheStore->get($cacheKey);
            }
        }

        $this->ominity->setLanguage($language);
        $page = $this->ominity->cms->pages->get($pageId, ['include' => 'content']);
        OminityComponent::setPage($page);
        $this->applyPageTrackingMetadata($page, $language);

        $output = '';
        foreach ($page->content as $component) {
            $output .= $this->renderComponent($component);
        }

        if ($page->isCached && $cacheConfig['enabled']) {
            $cacheStore->put($cacheKey, $output, $cacheConfig['expiration']);
            $cacheStore->put($this->trackingCacheKey($cacheKey), $this->tracking->buildCmsPageMetadata($page, $language), $cacheConfig['expiration']);
        }

        return $output;
    }

    /**
     * Get rendered HTML for a page
     *
     * @param  string|null  $language  Get language in specific language, defaults to app locale
     * @param  bool  $forced  Force a render even if caching exists
     * @return string
     *
     * @throws PageConentNotIncludedException
     */
    public function renderPage(Page $page, $language = null, $forced = false)
    {
        if ($language === null) {
            $language = app()->getLocale();
        }

        $cacheKey = md5("ominity_page_{$page->id}_{$language}");
        $cacheConfig = config('ominity.pages.cache');
        if ($cacheConfig['enabled']) {
            $cacheStore = Cache::store($cacheConfig['store'] ?? 'file');
            if ($cacheStore->has($cacheKey) && ! $forced) {
                $this->applyPageTrackingMetadata($page, $language);

                return $cacheStore->get($cacheKey);
            }
        }

        if (empty($page->content)) {
            throw new PageConentNotIncludedException("Content for page ID {$page->id} is not included. Make sure to pass a Page object that includes page content.");
        }

        OminityComponent::setPage($page);
        $this->applyPageTrackingMetadata($page, $language);

        $output = '';
        foreach ($page->content as $component) {
            $output .= $this->renderComponent($component);
        }

        if ($page->isCached && $cacheConfig['enabled']) {
            $cacheStore->put($cacheKey, $output, $cacheConfig['expiration']);
            $cacheStore->put($this->trackingCacheKey($cacheKey), $this->tracking->buildCmsPageMetadata($page, $language), $cacheConfig['expiration']);
        }

        return $output;
    }

    /**
     * @return string
     */
    protected function renderComponent($component)
    {
        $componentClass = config("ominity.pages.components.{$component->component}");
        if (class_exists($componentClass)) {
            $component = new $componentClass($component->fields);

            return $component->render()->render();
        }

        return '';
    }

    protected function applyPageTrackingMetadata(Page $page, string $language): void
    {
        $this->tracking->mergePageMetadata($this->tracking->buildCmsPageMetadata($page, $language));
    }

    protected function applyCachedTrackingMetadata($cacheStore, string $cacheKey): bool
    {
        $trackingMetadata = $cacheStore->get($this->trackingCacheKey($cacheKey));
        if (is_array($trackingMetadata) && ! empty($trackingMetadata)) {
            $this->tracking->mergePageMetadata($trackingMetadata);
            return true;
        }

        return false;
    }

    protected function trackingCacheKey(string $cacheKey): string
    {
        return "{$cacheKey}_tracking";
    }
}
