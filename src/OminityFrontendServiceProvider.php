<?php

namespace Ominity\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class OminityFrontendServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Blade::directive('ominityHead', function () {
            if (! config('ominity.forms.recaptcha.enabled')) {
                return '';
            }

            $version = config('ominity.forms.recaptcha.version', 'v3');
            $siteKey = config('ominity.forms.recaptcha.site_key');

            if ($version === 'v3') {
                return <<<HTML
<meta name="recaptcha-site-key" content="{$siteKey}">
<script src="https://www.google.com/recaptcha/api.js?render={$siteKey}"></script>
HTML;
            }

            // fallback for v2
            return <<<HTML
<meta name="recaptcha-site-key" content="{$siteKey}">
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
HTML;
        });

        Blade::directive('ominityScripts', function () {
            $packageVersion = OminityServiceProvider::PACKAGE_VERSION;
            $script = asset('vendor/ominity/ominity.umd.js') . "?v={$packageVersion}";

            return <<<HTML
<script src="{$script}"></script>
HTML;
        });
    }
}
