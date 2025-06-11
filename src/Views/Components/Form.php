<?php

namespace Ominity\Laravel\Views\Components;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;
use Ominity\Api\Resources\Modules\Forms\Form as OminityForm;
use Ominity\Laravel\Facades\Ominity;

class Form extends Component
{
    public OminityForm $form;

    public array $rows = [];

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        int|OminityForm $form,
        public string $class = '',
        public bool $ajax = false
    ) {
        if ($form instanceof OminityForm) {
            $this->form = $form;
        } else {
            $config = config('ominity.forms.cache');
            if ($config['enabled']) {
                $this->form = Cache::store($config['store'])->remember('forms-data-'.$form.'-'.app()->getLocale(), $config['expiration'], function () use ($form) {
                    return Ominity::api()->modules->forms->forms->get($form, [
                        'include' => 'fields',
                    ]);
                });
            } else {
                $this->form = Ominity::api()->modules->forms->forms->get($form, [
                    'include' => 'fields',
                ]);
            }
        }

        $this->form->fields = $this->form->fields();

        $this->rows = $this->buildFieldRows();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('ominity::components.form', [
            'form' => $this->form,
            'rows' => $this->rows,
            'class' => $this->class,
            'ajax' => $this->ajax,
        ])->render();
    }

    protected function buildFieldRows(): array
    {
        $rows = [];
        $currentRow = [];

        foreach ($this->form->fields as $field) {
            if (in_array($field->type, ['hidden', 'metadata'])) {
                continue;
            }

            if (! $field->isInline) {
                if (! empty($currentRow)) {
                    $rows[] = $currentRow;
                    $currentRow = [];
                }
                $rows[] = [$field];
            } else {
                $currentRow[] = $field;
            }
        }

        if (! empty($currentRow)) {
            $rows[] = $currentRow;
        }

        return $rows;
    }
}
