<?php

namespace Ominity\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ominity\Api\Resources\Modules\Forms\Form;
use Ominity\Api\Types\Modules\Forms\FieldType;
use Ominity\Laravel\Facades\Ominity;
use Ominity\Laravel\Services\FormValidationService;

class FormController extends Controller
{
    protected FormValidationService $validationService;

    public function __construct(FormValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    public function submit(Request $request)
    {
        if ($request->input('_locale')) {
            app()->setLocale($request->input('_locale'));
        }

        $request->validate([
            '_form' => ['required', 'numeric'],
        ]);

        $form = $this->getForm($request->input('_form'));

        if (! $form) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => __('ominity::forms.not_found')]);
            }

            return redirect()->back()->with('error', __('ominity::forms.not_found'));
        }

        $recaptchaConfig = config('ominity.forms.recaptcha');
        if ($recaptchaConfig['enabled']) {
            $this->validateRecaptcha($request, $recaptchaConfig);
        }

        $validated = $this->validationService->validate($request, $form);

        $data = $request->except('_token', '_form', '_locale', 'g-recaptcha-response');

        foreach ($form->fields() as $field) {
            if ($field->type == FieldType::METADATA) {
                $fieldKey = $field->name;
                if (! isset($data[$fieldKey])) {
                    $data[$fieldKey] = [];
                }

                foreach ($field->options as $option) {
                    switch ($option) {
                        case 'ip_address':
                            $data[$fieldKey]['ip_address'] = $request->ip();
                            break;
                        case 'user_agent':
                            $data[$fieldKey]['user_agent'] = $request->header('User-Agent');
                            break;
                        case 'referrer':
                            $data[$fieldKey]['referrer'] = $request->headers->get('referer');
                            break;
                        case 'locale':
                            $data[$fieldKey]['locale'] = app()->getLocale();
                            break;
                    }
                }
            }
        }

        try {
            Ominity::api()->modules->forms->submissions->create([
                'formId' => $form->id,
                'userId' => (Auth::check() && Auth::user() instanceof \Ominity\Laravel\Models\User) ? Auth::id() : null,
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

    protected function validateRecaptcha(Request $request, array $config)
    {
        $token = $request->input('g-recaptcha-response');

        if (! $token) {
            abort(422, __('ominity::forms.recaptcha_missing'));
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $config['secret_key'],
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        $result = $response->json();

        if (empty($result['success'])) {
            abort(422, __('ominity::forms.recaptcha_failed'));
        }

        if ($config['version'] === 'v3') {
            $score = $result['score'] ?? 0;
            $threshold = (float) $config['score'] ?? 0.5;

            if ($score < $threshold) {
                abort(422, __('ominity::forms.recaptcha_low_score'));
            }
        }
    }
}
