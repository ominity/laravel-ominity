<?php

namespace Ominity\Laravel\Views\Components;

use Illuminate\View\Component;

class MenuItem extends Component
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

        $componentClass = 'Ominity\\Laravel\\Views\\Components\\Menu\\'.ucfirst($item->type);

        if (class_exists($componentClass)) {
            $component = app($componentClass, ['item' => $item]);

            return $component->render();
        }

        return '';
    }
}
