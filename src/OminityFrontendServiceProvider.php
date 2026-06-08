<?php

namespace Ominity\Laravel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Ominity\Laravel\Services\OminityTrackingService;

class OminityFrontendServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Blade::directive('ominity_recaptcha', function () {
            if (! config('ominity.forms.recaptcha.enabled')) {
                return '';
            }

            $version = config('ominity.forms.recaptcha.version', 'v3');
            $siteKey = trim((string) config('ominity.forms.recaptcha.site_key', ''));
            if ($siteKey === '') {
                return '';
            }

            $escapedSiteKey = e($siteKey);

            if ($version === 'v3') {
                return <<<HTML
<meta name="recaptcha-site-key" content="{$escapedSiteKey}">
<script src="https://www.google.com/recaptcha/api.js?render={$escapedSiteKey}"></script>
HTML;
            }

            // fallback for v2
            return <<<HTML
<meta name="recaptcha-site-key" content="{$escapedSiteKey}">
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
HTML;
        });

        Blade::directive('ominity_styles', function () {
            $packageVersion = OminityServiceProvider::PACKAGE_VERSION;
            $css = asset('vendor/ominity/ominity.css')."?v={$packageVersion}";

            return <<<HTML
<link rel="stylesheet" href="{$css}">
HTML;
        });

        Blade::directive('ominity_scripts', function () {
            return <<<'PHP'
<?php $ominityPackageVersion = \Ominity\Laravel\OminityServiceProvider::PACKAGE_VERSION; ?>
<script src="<?php echo e(asset('vendor/ominity/ominity.js').'?v='.$ominityPackageVersion); ?>"></script>
<?php echo app(\Ominity\Laravel\Services\OminityTrackingService::class)->renderBootstrapScript(); ?>
PHP;
        });

        Blade::directive('ominity_tracking', function () {
            return '<?php echo app('.OminityTrackingService::class.'::class)->renderBootstrapScript(); ?>';
        });

        Blade::directive('ominity_tracking_meta', function ($expression) {
            return '<?php app('.OminityTrackingService::class."::class)->mergePageMetadata({$expression}); ?>";
        });
    }
}
