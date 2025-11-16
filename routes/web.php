<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('/accounts', static function (): Response {
    return Inertia::render('Accounts/Index');
})->name('accounts.index');

Route::get('/transactions', static function (): Response {
    return Inertia::render('Transactions/Index');
})->name('transactions.index');

Route::get('/budgets', static function (): Response {
    return Inertia::render('Budgets/Index');
})->name('budgets.index');
