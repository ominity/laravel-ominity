<?php

namespace Ominity\Laravel\Views\Components\FormField;

use Illuminate\Support\Str;
use Illuminate\View\Component;

class Select extends Component
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

        $style = '';
        if ($field->isInline) {
            $style = 'display: inline-block; ';
            if ($field->width) {
                $style .= 'width: calc('.$field->width.' - 3px);';
            } else {
                $style .= 'width: calc(50% - 3px);';
            }
        } elseif ($field->width) {
            $style = 'width: '.$field->width.';';
        } else {
            $style = 'width: 100%;';
        }

        return view('ominity::components.form-field.select', compact('field', 'id', 'style'));
    }
}
