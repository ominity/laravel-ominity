<?php

namespace Ominity\Laravel\Views\Components\Menu;

use Illuminate\View\Component;
use Ominity\Laravel\Facades\Ominity;

class Link extends Component
{
    public $item;

    /**
     * Create a new component instance.
     *
     * @param  array  $items
     * @return void
     */
    public function __construct($item)
    {
        $this->item = $item;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        $item = $this->item;

        if ($item->target->resource == 'link') {
            $href = $item->target->href;
        } elseif ($item->target->resource == 'route') {
            $href = Ominity::router()->route($item->target);
        }

        return view('ominity::components.menu.link', compact('item', 'href'));
    }
}
