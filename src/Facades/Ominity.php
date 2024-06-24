<?php

namespace Ominity\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Ominity\Api\OminityApiClient;
use Ominity\Laravel\OminityManager;
use Ominity\Laravel\OminityPageRenderer;
use Ominity\Laravel\Services\OminityRouterService;
use Ominity\Laravel\Services\VatValidationService;

/**
 * (Facade) Class Ominity.
 *
 * @method static OminityApiClient api()
 * @method static OminityPageRenderer renderer()
 * @method static OminityRouterService router()
 * @method static VatValidationService vatValidator()
 */
class Ominity extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return OminityManager::class;
    }
}
