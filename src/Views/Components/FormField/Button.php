<?php

namespace Ominity\Laravel\Views\Components\FormField;

use Illuminate\View\Component;
use Illuminate\Support\Str;

class Button extends Component
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
        if(isset($field->css->id)) {
            $id = $field->css->id;
        }

        $style = '';
        if($field->isInline) {
            $style = 'display: inline-block; ';
            if ($field->width) {
                $style .= 'width: calc(' . $field->width . ' - 3px);';
            } else {
                $style .= 'width: calc(50% - 3px);';
            }
        }
        else if($field->width) {
            $style = 'width: ' . $field->width . ';';
        }
        else {
            $style = 'width: auto;';
        }

        return view('ominity::components.form-field.button', compact('field', 'id', 'style'));
    }
}
