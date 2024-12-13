<?php

namespace Ominity\Laravel\Views\Components;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;
use Ominity\Laravel\Facades\Ominity;

class Menu extends Component
{
    public string $identifier;

    public string $class;

    /**
     * Create a new component instance.
     *
     * @param  array  $items
     * @return void
     */
    public function __construct(string $identifier, string $class = '')
    {
        $this->identifier = $identifier;
        $this->class = $class;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        $config = config('ominity.menus.cache');

        if ($config['enabled'] == 'true') {
            $cacheKey = 'menu-html-'.$this->identifier.'-'.app()->getLocale();

            $menuHtml = Cache::store($config['store'])->remember(
                $cacheKey,
                $config['expiration'],
                function () {
                    return $this->fetchAndRenderMenu();
                }
            );
        } else {
            $menuHtml = $this->fetchAndRenderMenu();
        }

        return $menuHtml;
    }

    /**
     * Fetch menu from the API and render it as HTML.
     *
     * @return string
     */
    protected function fetchAndRenderMenu()
    {
        $config = config('ominity.menus.cache');
        if ($config['enabled'] === 'data') {
            $cacheKey = 'menu-'.$this->identifier.'-'.app()->getLocale();

            $menu = Cache::store($config['store'])->remember(
                $cacheKey,
                $config['expiration'],
                function () {
                    $menus = Ominity::api()->cms->menus->all([
                        'limit' => 1,
                        'include' => 'rendered',
                        'filter' => [
                            'identifier' => $this->identifier,
                        ],
                    ]);

                    if ($menus->count() > 0) {
                        return $menus->first();
                    }

                    return null;
                }
            );
        } else {
            $menus = Ominity::api()->cms->menus->all([
                'limit' => 1,
                'include' => 'rendered',
                'filter' => [
                    'identifier' => $this->identifier,
                ],
            ]);

            $menu = $menus->first();
        }

        $class = $this->class;

        return view('ominity::components.menu', compact('menu', 'class'))->render();
    }
}
