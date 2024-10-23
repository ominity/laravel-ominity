<?php

namespace Ominity\Laravel\Views\Components\Menu;

use Illuminate\View\Component;
use Ominity\Laravel\Facades\Ominity;

class Link extends Component
{
    public $item;

    public string $class;

    /**
     * Create a new component instance.
     *
     * @param  array  $items
     * @return void
     */
    public function __construct($item, string $class = '')
    {
        $this->item = $item;
        $this->class = $class;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        if ($this->item->target->resource == 'link') {
            $href = $this->item->target->href;
        } elseif ($this->item->target->resource == 'route') {
            $href = Ominity::router()->route($this->item->target);
        }

        return view('ominity::components.menu.link', [
            'item' => $this->item,
            'class' => $this->class,
            'href' => $href,
        ]);
    }
}
