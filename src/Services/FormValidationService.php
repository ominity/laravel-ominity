<?php

namespace Ominity\Laravel\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Ominity\Api\Types\Modules\Forms\FieldType;

class FormValidationService
{
    /**
     * Validate the incoming request using form-defined rules.
     *
     * @param Request $request
     * @param $form
     * @return array
     */
    public function validate(Request $request, $form): array
    {
        $validation = $this->build($form);

        $validator = Validator::make(
            $request->all(),
            $validation['rules'],
            $validation['messages'],
            $validation['attributes']
        );

        $validator->validate();

        return $validator->validated();
    }

    /**
     * Build the validation rules, messages, and attributes for a form.
     *
     * @param $form
     * @return array
     */
    public function build($form): array
    {
        $rules = [];
        $messages = [];
        $attributes = [];

        foreach ($form->fields() as $field) {
            $fieldKey = $field->name;

            $attributes[$fieldKey] = $field->label ?: '';

            if ($field->type == FieldType::HONEYPOT) {
                $rules[$fieldKey] = ['nullable', 'string', 'size:0'];
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
                $fieldRules[] = 'min:' . $field->validation->minLength;
            }

            if (isset($field->validation->maxLength)) {
                $fieldRules[] = 'max:' . $field->validation->maxLength;
            }

            if (!empty($field->validation->rules)) {
                $fieldRules = array_merge($fieldRules, $field->validation->rules);
            }

            if ($fieldRules) {
                $rules[$fieldKey] = $fieldRules;

                if (!empty($field->validation->message)) {
                    $messages[$fieldKey] = $field->validation->message;
                }
            }
        }

        return [
            'rules' => $rules,
            'messages' => $messages,
            'attributes' => $attributes,
        ];
    }
}
