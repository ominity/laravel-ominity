<?php

namespace Ominity\Laravel;

use Ominity\Api\OminityApiClient;
use Ominity\Api\Resources\Cms\Page;
use Ominity\Laravel\Exceptions\PageContentNotLoadedException;
use Ominity\Laravel\Views\Components\OminityComponent;

class OminityPageRenderer
{
    protected $ominity;

    public function __construct(OminityApiClient $ominity)
    {
        $this->ominity = $ominity;
    }

    /**
     * Get rendered HTML for a page by ID
     *
     * @return string
     */
    public function renderPagebyId(int $pageId)
    {
        $page = $this->ominity->cms->pages->get($pageId, ['include' => 'content']);
        OminityComponent::setPage($page);

        $output = '';
        foreach ($page->content as $component) {
            $output .= $this->renderComponent($component);
        }

        return $output;
    }

    /**
     * Get rendered HTML for a page
     *
     * @param  int  $pageId
     * @return string
     *
     * @throws PageContentNotLoadedException
     */
    public function renderPage(Page $page)
    {
        if (empty($page->content)) {
            throw new PageContentNotLoadedException("Content for page ID {$page->id} is not included. Make sure to pass a Page object that includes page content.");
        }

        OminityComponent::setPage($page);

        $output = '';
        foreach ($page->content as $component) {
            $output .= $this->renderComponent($component);
        }

        return $output;
    }

    /**
     * @return string
     */
    protected function renderComponent($component)
    {
        $componentClass = config("ominity.components.{$component->component}");
        if (class_exists($componentClass)) {
            $component = new $componentClass($component->fields);

            return $component->render()->render();
        }

        return '';
    }
}
