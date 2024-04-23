<?php

namespace Ominity\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Ominity\Api\Exceptions\ApiException;
use Ominity\Laravel\Facades\Ominity;

class PreRenderPagesCommand extends Command
{
    protected $signature = 'ominity:pages:pre-render {pageId?} {locale?}';

    protected $description = 'Pre-renders pages and updates their cache based on an optional page ID and locale';

    public function handle()
    {
        $pageId = $this->argument('pageId');
        $locale = $this->argument('locale') ?: null;

        if (! config('ominity.pages.cache')['enabled']) {
            $this->error('Caching is disabled. It can be enabled in the ominity.php config file or by setting OMINITY_PAGES_CACHE_PRERENDER=true in your .env file.');

            return;
        }

        $ominity = Ominity::api();
        $enabledLanguages = $ominity->settings->languages->all([
            'enabled' => true,
        ]);

        if ($locale !== null && ! $enabledLanguages->get($locale)) {
            $this->error("Locale $locale is not a supported language by the API.");

            return;
        }

        $count = [
            'success' => 0,
            'failed' => 0,
        ];

        if ($pageId) {
            if ($locale !== null) {
                if ($this->preRenderPage($pageId, $locale)) {
                    $count['success']++;
                } else {
                    $count['failed']++;
                }
            } else {
                foreach ($enabledLanguages as $language) {
                    if ($this->preRenderPage($pageId, $language)) {
                        $count['success']++;
                    } else {
                        $count['failed']++;
                    }
                }
            }
        } else {
            $this->info('No specific page ID provided, pre-rendering all pages');

            $pages = $ominity->cms->pages->page(1, 10, [
                'filter' => [
                    'cache' => true,
                    'published' => true,
                ],
            ]);

            while ($pages) {
                foreach ($pages as $page) {

                    if ($locale !== null) {
                        if ($this->preRenderPage($page->id, $locale)) {
                            $count['success']++;
                        } else {
                            $count['failed']++;
                        }
                    } else {
                        foreach ($enabledLanguages as $language) {
                            if ($this->preRenderPage($page->id, $language)) {
                                $count['success']++;
                            } else {
                                $count['failed']++;
                            }
                        }
                    }
                }
                $pages = $pages->next();
            }
        }

        $this->info("Total successful caches: {$count['success']}, Total failed caches: {$count['failed']}");
    }

    protected function preRenderPage($pageId, $locale)
    {
        app()->setLocale($locale);

        $ominity = Ominity::api();
        $ominity->setLanguage($locale);

        try {
            $page = $ominity->cms->pages->get($pageId, ['include' => 'content']);
        } catch (ApiException $e) {
            $this->error("Failed to fetch page $pageId: ".$e->getMessage());

            return false;
        }

        if (! $page->isCached) {
            $dashboardUrl = $page->_links->dashboard ?? 'URL not available';
            $this->error("Caching is not enabled for page ID $pageId. Enable it here: $dashboardUrl");

            return false;
        }

        $output = Ominity::renderer()->renderPage($page, $locale, true);
        $this->info("Pre-rendered and cached page: $pageId for locale: $locale - Output length: ".strlen($output));

        return true;
    }
}
