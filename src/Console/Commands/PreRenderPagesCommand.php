<?php

namespace Ominity\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Ominity\Api\Exceptions\ApiException;
use Ominity\Laravel\Facades\Ominity;

use function Termwind\{render};

class PreRenderPagesCommand extends Command
{
    protected $signature = 'ominity:pages:pre-render {pageId?} {locale?}';

    protected $description = 'Pre-renders pages and updates their cache based on an optional page ID and locale';

    public function handle()
    {
        if (! config('ominity.pages.cache')['enabled']) {
            $this->renderError('Page caching is disabled. Enable it in the ominity.php config file or set OMINITY_PAGES_CACHE_ENABLED=true in your .env file.');

            return self::FAILURE;
        }

        render(<<<'HTML'
            <div class="mx-2 my-1">
                <div class="space-x-1">
                    <span class="px-1 bg-blue-500 text-white">Ominity Page Pre-Renderer</span>
                </div>
            </div>
        HTML);

        $pageId = $this->argument('pageId');
        $locale = $this->argument('locale') ?: null;

        $ominity = Ominity::api();
        $enabledLanguages = $ominity->settings->languages->all(['filter' => ['enabled' => true]]);

        if ($locale !== null && ! isset($enabledLanguages[$locale])) {
            $this->renderError("Locale $locale is not a supported language by the API.");

            return self::FAILURE;
        }

        $count = ['success' => 0, 'failed' => 0];

        if ($pageId) {
            $this->preRenderSinglePage($pageId, $locale, $enabledLanguages, $count);
        } else {
            $this->preRenderAllPages($enabledLanguages, $locale, $count);
        }

        render(<<<'HTML'
            <div class="mx-2 mt-1">
                <span class="font-bold text-green">Totals</span>
            </div>
        HTML);
        $this->outputLine('Success', $count['success'], 'green');
        $this->outputLine('Failed', $count['failed'], 'red');

        return self::SUCCESS;
    }

    private function preRenderSinglePage($pageId, $locale, $enabledLanguages, &$count)
    {
        if ($locale) {
            $this->attemptPreRender($pageId, $locale, $count);
        } else {
            foreach ($enabledLanguages as $language) {
                $this->attemptPreRender($pageId, $language->code, $count);
            }
        }
    }

    private function preRenderAllPages($enabledLanguages, $locale, &$count)
    {
        $pages = Ominity::api()->cms->pages->all(['filter' => ['cache' => true, 'published' => true]]);

        foreach ($pages as $page) {
            if ($locale) {
                $this->attemptPreRender($page->id, $locale, $count);
            } else {
                foreach ($enabledLanguages as $language) {
                    $this->attemptPreRender($page->id, $language->code, $count);
                }
            }
        }
    }

    private function attemptPreRender($pageId, $locale, &$count)
    {
        app()->setLocale($locale);
        Ominity::api()->setLanguage($locale);

        try {
            $startTime = microtime(true);

            $page = Ominity::api()->cms->pages->get($pageId, ['include' => 'content']);
            if (! $page->isCached) {
                $this->outputLine($page->name, 'FAILED', 'red', $locale);
                $count['failed']++;

                return;
            }

            Ominity::renderer()->renderPage($page, $locale, true);

            $endTime = microtime(true);
            $renderTime = $endTime - $startTime;
            $formattedTime = sprintf('%.1f s', $renderTime);

            $this->outputLine($page->name, $formattedTime, 'gray', $locale);
            $count['success']++;
        } catch (ApiException $e) {
            $this->outputLine($page->name, 'FAILED', 'red', $locale);
            $count['failed']++;
        }
    }

    private function outputLine($title, $value, $valueColor = 'gray', $subText = null)
    {
        render(view('ominity::cli.line', [
            'title' => $title,
            'subText' => $subText,
            'value' => $value,
            'valueColor' => $valueColor,
        ])->render());
    }

    private function renderError($message)
    {
        render(<<<'HTML'
            <div class='bg-red-500 text-white p-1'>$message</div>
        HTML);
    }
}
