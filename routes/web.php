<?php

use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\TermsConditionsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

if (env('RENDER_DUMMY', false)) {
    Route::get('/', function () {
        return response('Render deploy check OK', 200);
    });
    Route::any('{any}', function () {
        return response('Render deploy check OK', 200);
    });
    return;
}



// public website: home (App Store "Marketing URL") and support (App Store "Support URL")
Route::view('/', 'site.home')->name('site.home');
Route::view('/support', 'site.support')->name('site.support');

// Reset Password
Route::get('/password-reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/password-reset', [ResetPasswordController::class, 'reset'])->name('password.update');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Policy Routes
Route::get('/policies/{slug}', [PolicyController::class, 'show'])
    ->whereIn('slug', ['privacy-policy', 'terms-conditions'])
    ->name('policy.show');
Auth::routes();
