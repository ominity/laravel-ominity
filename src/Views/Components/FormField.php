<?php

namespace Ominity\Laravel\Views\Components;

use Illuminate\View\Component;
use Ominity\Api\Resources\Modules\Forms\FormField as OminityFormField;

class FormField extends Component
{
    public OminityFormField $field;

    /**
     * Create a new component instance.
     *
     * @param  array  $items
     * @return void
     */
    public function __construct($field)
    {
        $this->field = $field;
    }
    
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        $field = $this->field;

        $componentClass = 'Ominity\\Laravel\\Views\\Components\\FormField\\' . ucfirst($field->type);

        if (class_exists($componentClass)) {
            $component = app($componentClass, ['field' => $field]);
            return $component->render();
        }

        return '';
    }
}
