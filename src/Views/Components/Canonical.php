<?php

namespace Ominity\Laravel\Views\Components;

use Illuminate\View\Component;

class Canonical extends Component
{
    public $routes;

    public $keepQuery;

    /**
     * Create a new component instance.
     *
     * @param  array  $items
     * @return void
     */
    public function __construct($routes, $keepQuery = false)
    {
        $this->routes = $routes;
        $this->keepQuery = $keepQuery;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        $routes = $this->routes;
        $keepQuery = $this->keepQuery;

        return view('ominity::components.canonical', compact('routes', 'keepQuery'));
    }
}
