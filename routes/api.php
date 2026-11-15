<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BankAccountController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\EntityCodeController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\FinancialReportController;
use App\Http\Controllers\Api\V1\ManualCashEntryController;
use App\Http\Controllers\Api\V1\ItemCategoryController;
use App\Http\Controllers\Api\V1\ItemController;
use App\Http\Controllers\Api\V1\ItemUnitController;
use App\Http\Controllers\Api\V1\ServiceTicketController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\StockInController;
use App\Http\Controllers\Api\V1\StockOpnameController;
use App\Http\Controllers\Api\V1\StockOutController;
use App\Http\Controllers\Api\V1\StockReportController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\TechnicianController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WorkshopServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('entity-codes/suggest', [EntityCodeController::class, 'suggest'])->name('api.entity-codes.suggest');

        // Prefix nama route `api.*` agar tidak menimpa route web (customers.index, dll).
        Route::apiResource('customers', CustomerController::class)->names('api.customers');
        Route::apiResource('suppliers', SupplierController::class)->names('api.suppliers');
        Route::apiResource('technicians', TechnicianController::class)->names('api.technicians');
        Route::post('technicians/{technician}', [TechnicianController::class, 'update'])->name('api.technicians.update.multipart');
        Route::apiResource('bank-accounts', BankAccountController::class)->names('api.bank-accounts');
        Route::apiResource('workshop-services', WorkshopServiceController::class)->names('api.workshop-services');
        Route::apiResource('item-categories', ItemCategoryController::class)->names('api.item-categories');
        Route::apiResource('item-units', ItemUnitController::class)->names('api.item-units');
        Route::apiResource('items', ItemController::class)->names('api.items');
        // Multipart update (PHP sering gagal parse file di PUT).
        Route::post('items/{item}', [ItemController::class, 'update'])->name('api.items.update.multipart');

        Route::get('/dashboard', [DashboardController::class, 'show'])->name('api.dashboard.show');
        Route::get('financial-reports', [FinancialReportController::class, 'show'])->name('api.financial-reports.show');

        Route::get('manual-incomes/options', [ManualCashEntryController::class, 'incomeOptions'])->name('api.manual-incomes.options');
        Route::get('manual-incomes', [ManualCashEntryController::class, 'incomeIndex'])->name('api.manual-incomes.index');
        Route::post('manual-incomes', [ManualCashEntryController::class, 'incomeStore'])->name('api.manual-incomes.store');
        Route::get('manual-incomes/{manualCashEntry}', [ManualCashEntryController::class, 'incomeShow'])->name('api.manual-incomes.show');
        Route::delete('manual-incomes/{manualCashEntry}', [ManualCashEntryController::class, 'incomeDestroy'])->name('api.manual-incomes.destroy');

        Route::get('manual-expenses/options', [ManualCashEntryController::class, 'expenseOptions'])->name('api.manual-expenses.options');
        Route::get('manual-expenses', [ManualCashEntryController::class, 'expenseIndex'])->name('api.manual-expenses.index');
        Route::post('manual-expenses', [ManualCashEntryController::class, 'expenseStore'])->name('api.manual-expenses.store');
        Route::get('manual-expenses/{manualCashEntry}', [ManualCashEntryController::class, 'expenseShow'])->name('api.manual-expenses.show');
        Route::delete('manual-expenses/{manualCashEntry}', [ManualCashEntryController::class, 'expenseDestroy'])->name('api.manual-expenses.destroy');

        Route::get('users/roles', [UserController::class, 'roles'])->name('api.users.roles');
        Route::apiResource('users', UserController::class)->names('api.users');

        Route::get('roles/catalog', [RoleController::class, 'catalog'])->name('api.roles.catalog');
        Route::apiResource('roles', RoleController::class)->names('api.roles');

        Route::get('service-tickets/options', [ServiceTicketController::class, 'options'])->name('api.service-tickets.options');
        Route::get('service-tickets/{service_ticket}/claim-slip', [ServiceTicketController::class, 'claimSlip'])->name('api.service-tickets.claim-slip');
        Route::post('service-tickets/{service_ticket}/status', [ServiceTicketController::class, 'updateStatus'])->name('api.service-tickets.status');
        Route::post('service-tickets/{service_ticket}/work-lines', [ServiceTicketController::class, 'updateWorkLines'])->name('api.service-tickets.work-lines');
        Route::post('service-tickets/{service_ticket}/warranty', [ServiceTicketController::class, 'updateWarranty'])->name('api.service-tickets.warranty');
        Route::post('service-tickets/{service_ticket}/resend', [ServiceTicketController::class, 'resendNotification'])->name('api.service-tickets.resend');
        Route::apiResource('service-tickets', ServiceTicketController::class)->names('api.service-tickets');

        Route::get('/settings', [SettingController::class, 'show'])->name('api.settings.show');
        Route::match(['put', 'post'], '/settings', [SettingController::class, 'update'])->name('api.settings.update');

        Route::get('stock-ins', [StockInController::class, 'index'])->name('api.stock-ins.index');
        Route::post('stock-ins', [StockInController::class, 'store'])->name('api.stock-ins.store');
        Route::get('stock-ins/{batchNo}', [StockInController::class, 'show'])->name('api.stock-ins.show');

        Route::get('stock-reports', [StockReportController::class, 'show'])->name('api.stock-reports.show');

        Route::get('stock-opnames', [StockOpnameController::class, 'index'])->name('api.stock-opnames.index');
        Route::post('stock-opnames/draft', [StockOpnameController::class, 'saveDraft'])->name('api.stock-opnames.draft');
        Route::post('stock-opnames/approve', [StockOpnameController::class, 'approve'])->name('api.stock-opnames.approve');
        Route::post('stock-opnames/{stockOpname}/reject', [StockOpnameController::class, 'reject'])->name('api.stock-opnames.reject');

        Route::get('transactions/options', [TransactionController::class, 'options'])->name('api.transactions.options');
        Route::apiResource('transactions', TransactionController::class)->names('api.transactions');

        Route::get('purchases/options', [PurchaseController::class, 'options'])->name('api.purchases.options');
        Route::apiResource('purchases', PurchaseController::class)->names('api.purchases');

        Route::get('stock-outs', [StockOutController::class, 'index'])->name('api.stock-outs.index');
        Route::post('stock-outs', [StockOutController::class, 'store'])->name('api.stock-outs.store');
        Route::get('stock-outs/{batchNo}', [StockOutController::class, 'show'])->name('api.stock-outs.show');
    });
});
