<?php

use Illuminate\Support\Facades\Route;
use Ominity\Laravel\Http\Controllers\FormController;

Route::post('/submit-form', [FormController::class, 'submit'])->name('ominity.form.submit');
