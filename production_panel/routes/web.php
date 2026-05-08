<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\FundsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;

// ────────────────────────────────────────────────────────────────────────────
// PUBLIC ROUTES
// ────────────────────────────────────────────────────────────────────────────

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('landing');
})->name('home');

// ────────────────────────────────────────────────────────────────────────────
// AUTH ROUTES
// ────────────────────────────────────────────────────────────────────────────

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:10,1');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])
    ->middleware('throttle:5,1');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Password Reset Routes
Route::get('/password/reset', function () {
    return view('auth.passwords.email');
})->name('password.request');

Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:5,1')
    ->name('password.email');

Route::get('/password/reset/{token}', function ($token) {
    return view('auth.passwords.reset', ['token' => $token]);
})->name('password.reset');

Route::post('/password/update', [ResetPasswordController::class, 'reset'])
    ->middleware('throttle:5,1')
    ->name('password.update');

// ────────────────────────────────────────────────────────────────────────────
// AUTHENTICATED USER ROUTES
// ────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('new', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('store');
        Route::get('{order}', [OrderController::class, 'show'])->name('show');
    });

    // Services (public read)
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');

    // Funds / Payments
    Route::prefix('funds')->name('funds.')->group(function () {
        Route::get('/', [FundsController::class, 'index'])->name('index');
        Route::post('stripe', [FundsController::class, 'stripe'])
            ->middleware('throttle:5,1')
            ->name('stripe');
        Route::post('paypal', [FundsController::class, 'paypal'])
            ->middleware('throttle:5,1')
            ->name('paypal');
        Route::post('manual', [FundsController::class, 'manual'])
            ->middleware('throttle:10,1')
            ->name('manual');
    });

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Referrals
    Route::get('/referral', [ReferralController::class, 'index'])->name('referral.index');

    // Support Tickets
    Route::prefix('support')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('new', [TicketController::class, 'create'])->name('create');
        Route::post('/', [TicketController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('store');
        Route::get('{ticket}', [TicketController::class, 'show'])->name('show');
        Route::post('{ticket}/reply', [TicketController::class, 'reply'])
            ->middleware('throttle:10,1')
            ->name('reply');
    });
});

// ────────────────────────────────────────────────────────────────────────────
// ADMIN ROUTES
// ────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // Settings
    Route::get('settings', [AdminController::class, 'settings'])->name('settings');

    // API Providers Management
    Route::prefix('providers')->name('providers.')->group(function () {
        Route::get('/', [AdminController::class, 'providersIndex'])->name('index');
        Route::get('create', [AdminController::class, 'providersCreate'])->name('create');
        Route::post('/', [AdminController::class, 'providersStore'])->name('store');
        Route::get('{provider}/edit', [AdminController::class, 'providersEdit'])->name('edit');
        Route::put('{provider}', [AdminController::class, 'providersUpdate'])->name('update');
        Route::post('{provider}/sync', [AdminController::class, 'syncProvider'])->name('sync');
    });

    // Services Management
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', [AdminController::class, 'servicesIndex'])->name('index');
        Route::post('{service}/toggle', [AdminController::class, 'servicesToggle'])->name('toggle');
    });

    // Synchronization
    Route::prefix('sync')->name('sync.')->group(function () {
        Route::post('all', [AdminController::class, 'syncAll'])->name('all');
        Route::post('services', [AdminController::class, 'syncServices'])->name('services');
        Route::post('orders', [AdminController::class, 'syncOrders'])->name('orders');
    });

    // Orders Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [AdminController::class, 'ordersIndex'])->name('index');
        Route::patch('{order}/status', [AdminController::class, 'ordersUpdateStatus'])->name('status');
    });

    // Users Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [AdminController::class, 'usersIndex'])->name('index');
        Route::post('{user}/add-funds', [AdminController::class, 'usersAddFunds'])->name('add_funds');
        Route::post('{user}/ban', [AdminController::class, 'usersBan'])->name('ban');
        Route::post('{user}/unban', [AdminController::class, 'usersUnban'])->name('unban');
    });

    // Transactions Management
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [AdminController::class, 'transactionsIndex'])->name('index');
        Route::post('{transaction}/approve', [AdminController::class, 'transactionsApprove'])->name('approve');
        Route::post('{transaction}/reject', [AdminController::class, 'transactionsReject'])->name('reject');
    });

    // Tickets Management
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [AdminController::class, 'ticketsIndex'])->name('index');
        Route::post('{ticket}/reply', [AdminController::class, 'ticketsReply'])->name('reply');
        Route::post('{ticket}/close', [AdminController::class, 'ticketsClose'])->name('close');
    });

    // Analytics & Logs
    Route::get('logs/activity', [AdminController::class, 'activityLogs'])->name('logs.activity');
    Route::get('logs/payments', [AdminController::class, 'paymentLogs'])->name('logs.payments');
    Route::get('logs/providers', [AdminController::class, 'providerLogs'])->name('logs.providers');
});