<?php

namespace Ominity\Laravel\Views\Components\FormField;

use Illuminate\Support\Str;
use Illuminate\View\Component;

class Multicheckbox extends Component
{
    public $field;

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

        $id = Str::random(10);
        if (isset($field->css->id)) {
            $id = $field->css->id;
        }

        $id = $field->css->id ?? Str::random(10);

        $style = $field->width ? "width: {$field->width};" : '';

        return view('ominity::components.form-field.multicheckbox', compact('field', 'id', 'style'));
    }
}
