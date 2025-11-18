<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/debug-login-manual', function () {
    /** @var User $user */
    $user = User::query()->firstOrFail();

    $loggedBefore = Auth::guard('web')->check();

    Auth::guard('web')->login($user);

    logger()->info('debug-login-manual', [
        'session_id' => session()->getId(),
        'logged_before' => $loggedBefore,
        'logged_after' => Auth::guard('web')->check(),
        'web_user_id' => Auth::guard('web')->id(),
        'default_guard_id' => Auth::id(),
        'session_keys' => array_keys(session()->all()),
    ]);

    return [
        'logged_before' => $loggedBefore,
        'logged_after' => Auth::guard('web')->check(),
        'web_user_id' => Auth::guard('web')->id(),
        'session_id' => session()->getId(),
    ];
})->middleware('web');

Route::get('/debug-auth', function () {
    return [
        'auth_default_user_id' => Auth::id(),
        'auth_web_user_id' => Auth::guard('web')->id(),
        'session_keys' => array_keys(session()->all()),
    ];
})->middleware('web');
