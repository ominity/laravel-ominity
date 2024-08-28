<?php

namespace Ominity\Laravel\Views\Components\FormField;

use Illuminate\View\Component;
use Illuminate\Support\Str;

class Phone extends Component
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
            $style .= 'min-width: 0; ';
            if($field->width) {
                $style .= 'flex: 0 0 ' . $field->width . ';';
            } else {
                $style .= 'flex: 1;';
            }
        }
        else if($field->width) {
            $style = 'width: ' . $field->width . ';';
        }
        else {
            $style = 'width: 100%;';
        }

        return view('ominity::components.form-field.phone', compact('field', 'id', 'style'));
    }
}
