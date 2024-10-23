<?php

namespace Ominity\Laravel\Views\Components\Menu;

use Illuminate\View\Component;

class Dropdown extends Component
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
        return view('ominity::components.menu.dropdown', [
            'item' => $this->item,
            'class' => $this->class,
        ]);
    }
}
