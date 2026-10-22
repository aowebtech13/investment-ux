<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AdminWebController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Public Admin Auth Routes
Route::prefix('geyfdv')->group(function () {
    Route::get('/password/forgot', [AdminWebController::class, 'showForgotForm'])->name('admin.password.forgot');
    Route::post('/password/forgot', [AdminWebController::class, 'sendResetToken']);
    Route::get('/password/reset', [AdminWebController::class, 'showResetForm'])->name('admin.password.reset');
    Route::post('/password/reset', [AdminWebController::class, 'resetPassword']);
    
    // Add a simple login view for the admin blade panel
    Route::get('/login', [AdminWebController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminWebController::class, 'login']);
});

Route::middleware(['auth', 'admin'])->prefix('geyfdv')->group(function () {
    Route::get('/dashboard', [AdminWebController::class, 'dashboard'])->name('admin.dashboard');
    
    Route::get('/users', [AdminWebController::class, 'users'])->name('admin.users');
    Route::get('/users/{id}', [AdminWebController::class, 'showUser'])->name('admin.users.show');
    Route::post('/users/{id}', [AdminWebController::class, 'updateUser'])->name('admin.users.update');
    Route::post('/users/{id}/level', [AdminWebController::class, 'updateUserLevel'])->name('admin.users.level');
    Route::post('/users/{id}/fund', [AdminWebController::class, 'fundUser'])->name('admin.users.fund');
    Route::delete('/users/{id}', [AdminWebController::class, 'deleteUser'])->name('admin.users.delete');
    
    Route::get('/investments', [AdminWebController::class, 'investments'])->name('admin.investments');
    Route::post('/investments/{id}/cancel', [AdminWebController::class, 'cancelInvestment'])->name('admin.investments.cancel');
    Route::post('/investments/{id}/extend', [AdminWebController::class, 'extendInvestment'])->name('admin.investments.extend');

    Route::get('/plans', [AdminWebController::class, 'plans'])->name('admin.plans');
    Route::post('/plans', [AdminWebController::class, 'createPlan'])->name('admin.plans.create');
    Route::post('/plans/{id}', [AdminWebController::class, 'updatePlan'])->name('admin.plans.update');
    Route::delete('/plans/{id}', [AdminWebController::class, 'deletePlan'])->name('admin.plans.delete');

    // Products management
    Route::get('/products', [AdminWebController::class, 'products'])->name('admin.products');
    Route::post('/products', [AdminWebController::class, 'storeProduct'])->name('admin.products.store');
    Route::post('/products/{id}', [AdminWebController::class, 'updateProduct'])->name('admin.products.update');
    Route::delete('/products/{id}', [AdminWebController::class, 'deleteProduct'])->name('admin.products.delete');
    
    Route::get('/withdrawals', [AdminWebController::class, 'withdrawals'])->name('admin.withdrawals');
    Route::post('/withdrawals/{id}/update', [AdminWebController::class, 'updateWithdrawal'])->name('admin.withdrawals.update');
    
    Route::get('/deposits', [AdminWebController::class, 'deposits'])->name('admin.deposits');
    Route::post('/deposits/{id}/update', [AdminWebController::class, 'updateDeposit'])->name('admin.deposits.update');
    
    Route::get('/transactions', [AdminWebController::class, 'transactions'])->name('admin.transactions');
    
    Route::post('/logout', [AdminWebController::class, 'logout'])->name('admin.logout');
});
