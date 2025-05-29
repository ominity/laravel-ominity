<?php

namespace Ominity\Laravel\Views\Components;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;
use Ominity\Laravel\Facades\Ominity;

class Form extends Component
{
    public int $formId;

    public string $class;

    public bool $ajax;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(int $form, string $class = '', bool $ajax = false)
    {
        $this->formId = $form;
        $this->class = $class;
        $this->ajax = $ajax;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        $config = config('ominity.forms.cache');
        $cacheKey = 'forms-data-'.$this->formId.'-'.app()->getLocale();

        if ($config['enabled']) {
            $form = Cache::store($config['store'])->remember(
                $cacheKey,
                $config['expiration'],
                function () {
                    return $this->fetchFormData();
                }
            );
        } else {
            $form = $this->fetchFormData();
        }

        $class = $this->class;

        return view('ominity::components.form', [
            'form' => $form,
            'class' => $class,
            'ajax' => $this->ajax,
        ])->render();
    }

    /**
     * Fetch form data from the API.
     *
     * @return array
     */
    protected function fetchFormData()
    {
        $form = Ominity::api()->modules->forms->forms->get($this->formId, [
            'include' => 'fields',
        ]);

        return $form;
    }
}
