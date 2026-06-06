<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Ominity\Laravel\Http\Controllers\FormController;
use Ominity\Laravel\Http\Controllers\TrackingController;

Route::post('/submit-form', [FormController::class, 'submit'])->name('ominity.form.submit');

Route::middleware('web')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->post(ltrim((string) config('ominity.tracking.route.path', '/ominity/tracking/events'), '/'), [TrackingController::class, 'event'])
    ->name('ominity.tracking.events');
