<?php

namespace Ominity\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ominity\Api\Resources\Modules\Forms\Form;
use Ominity\Api\Types\Modules\Forms\FieldType;
use Ominity\Laravel\Facades\Ominity;

class FormController extends Controller
{
    public function submit(Request $request)
    {
        if ($request->input('_locale')) {
            app()->setLocale($request->input('_locale'));
        }

        // Validate the form ID first
        $validator = Validator::make($request->all(), [
            '_form' => ['required', 'numeric'],
        ]);

        $validator->validate();

        $form = $this->getForm($request->input('_form'));
        $data = $request->except('_token', '_form', 'g-recaptcha-response');

        $rules = [];
        $messages = [];
        $attributes = [];

        foreach ($form->fields() as $field) {
            if ($field->type == FieldType::METADATA) {
                foreach ($field->options as $option) {
                    if (! isset($data['field_'.$field->id]) || ! is_array($data['field_'.$field->id])) {
                        $data['field_'.$field->id] = [];
                    }

                    switch ($option) {
                        case 'ip_address':
                            $data['field_'.$field->id]['ip_address'] = $request->ip();
                            break;
                        case 'user_agent':
                            $data['field_'.$field->id]['user_agent'] = $request->header('User-Agent');
                            break;
                        case 'referrer':
                            $data['field_'.$field->id]['referrer'] = $request->headers->get('referer');
                            break;
                        case 'locale':
                            $data['field_'.$field->id]['locale'] = app()->getLocale();
                            break;
                    }
                }

                continue;
            }

            $attributes['field_'.$field->id] = $field->label ?: '';

            if ($field->type == FieldType::HONEYPOT) {
                $rules['field_'.$field->id] = ['nullable', 'string', 'size:0'];

                continue;
            }

            $fieldRules = [];

            if ($field->validation->isRequired) {
                $fieldRules[] = 'required';
            }

            if ($field->type == FieldType::EMAIL) {
                $fieldRules[] = 'email';
            }

            if ($field->type == FieldType::NUMBER) {
                $fieldRules[] = 'numeric';
            }

            if (isset($field->validation->minLength)) {
                $fieldRules[] = 'min:'.$field->validation->minLength;
            }

            if (isset($field->validation->maxLength)) {
                $fieldRules[] = 'max:'.$field->validation->maxLength;
            }

            if (! empty($field->validation->rules)) {
                $fieldRules = array_merge($fieldRules, $field->validation->rules);
            }

            if ($fieldRules) {
                $rules['field_'.$field->id] = $fieldRules;

                if (! empty($field->validation->message)) {
                    $messages['field_'.$field->id] = $field->validation->message;
                }
            }
        }

        $validator = Validator::make($request->all(), $rules, $messages, $attributes);
        $validator->validate();

        try {
            Ominity::api()->modules->forms->submissions->create([
                'formId' => $form->id,
                'userId' => null,
                'data' => $data,
            ]);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => __('ominity::forms.success')]);
            }

            return redirect()->back()->with('success', __('ominity::forms.success'));
        } catch (\Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => __('ominity::forms.error')]);
            }

            return redirect()->back()->with('error', __('ominity::forms.error'));
        }
    }

    protected function getForm(int $formId): Form
    {
        $config = config('ominity.forms.cache');
        $cacheKey = 'forms-data-'.$formId.'-'.app()->getLocale();

        if ($config['enabled']) {
            $form = Cache::store($config['store'])->remember(
                $cacheKey,
                $config['expiration'],
                function () use ($formId) {
                    return $this->fetchFormData($formId);
                }
            );
        } else {
            $form = $this->fetchFormData($formId);
        }

        return $form;
    }

    protected function fetchFormData(int $formId)
    {
        return Ominity::api()->modules->forms->forms->get($formId, [
            'include' => 'fields',
        ]);
    }
}
