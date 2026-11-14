<?php

use App\Http\Controllers\AssetReportController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CategorySalesReportController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntityCodeController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemImportExportController;
use App\Http\Controllers\ItemSalesReportController;
use App\Http\Controllers\ItemUnitController;
use App\Http\Controllers\ManualExpenseController;
use App\Http\Controllers\ManualIncomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\ServiceReportController;
use App\Http\Controllers\ServiceTicketController;
use App\Http\Controllers\ServiceTicketTrackingController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\StockReportController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\TechnicianReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkshopServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/lacak/{token}', [ServiceTicketTrackingController::class, 'show'])
    ->name('service-tickets.track');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:dashboard view');

    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);

    Route::get('entity-codes/suggest', [EntityCodeController::class, 'suggest'])->name('entity-codes.suggest');

    // Fase 1 — Master data & inventory
    Route::resource('customers', CustomerController::class)->except(['create', 'edit']);
    Route::resource('suppliers', SupplierController::class)->except(['create', 'edit']);
    Route::resource('technicians', TechnicianController::class)->except(['create', 'edit']);
    Route::resource('item-categories', ItemCategoryController::class)->except(['create', 'edit']);
    Route::resource('item-units', ItemUnitController::class)->except(['create', 'edit']);
    Route::get('items/export', [ItemImportExportController::class, 'export'])->name('items.export');
    Route::get('items/import/template', [ItemImportExportController::class, 'template'])->name('items.import.template');
    Route::post('items/import', [ItemImportExportController::class, 'import'])->name('items.import');
    Route::resource('items', ItemController::class)->except(['create', 'edit']);

    Route::get('stock-ins', [StockInController::class, 'index'])->name('stock-ins.index');
    Route::post('stock-ins', [StockInController::class, 'store'])->name('stock-ins.store');
    Route::get('stock-ins/batch/{batchNo}', [StockInController::class, 'showBatch'])->name('stock-ins.batch');

    Route::get('stock-outs', [StockOutController::class, 'index'])->name('stock-outs.index');
    Route::post('stock-outs', [StockOutController::class, 'store'])->name('stock-outs.store');
    Route::get('stock-outs/batch/{batchNo}', [StockOutController::class, 'showBatch'])->name('stock-outs.batch');

    Route::get('stock-reports', [StockReportController::class, 'index'])->name('stock-reports.index');
    Route::get('stock-opnames/export', [StockOpnameController::class, 'export'])->name('stock-opnames.export');
    Route::get('stock-opnames', [StockOpnameController::class, 'index'])->name('stock-opnames.index');
    Route::post('stock-opnames/draft', [StockOpnameController::class, 'saveDraft'])->name('stock-opnames.draft');
    Route::post('stock-opnames/approve', [StockOpnameController::class, 'approve'])->name('stock-opnames.approve');
    Route::post('stock-opnames/{stockOpname}/reject', [StockOpnameController::class, 'reject'])->name('stock-opnames.reject');
    Route::get('asset-reports', [AssetReportController::class, 'index'])
        ->name('asset-reports.index')
        ->middleware('permission:asset report view');

    // Fase 2 — Transaksi & komisi teknisi
    Route::resource('workshop-services', WorkshopServiceController::class)->except(['create', 'edit']);
    Route::resource('service-tickets', ServiceTicketController::class);
    Route::post('service-tickets/{serviceTicket}/pickup-cancelled', [ServiceTicketController::class, 'pickupCancelled'])->name('service-tickets.pickup-cancelled');
    Route::get('service-tickets/{serviceTicket}/claim-slip', [ServiceTicketController::class, 'claimSlip'])->name('service-tickets.claim-slip');
    Route::post('service-tickets/{serviceTicket}/status', [ServiceTicketController::class, 'updateStatus'])->name('service-tickets.status');
    Route::post('service-tickets/{serviceTicket}/work-lines', [ServiceTicketController::class, 'updateWorkLines'])->name('service-tickets.work-lines');
    Route::post('service-tickets/{serviceTicket}/technician', [ServiceTicketController::class, 'updateTechnician'])->name('service-tickets.technician');
    Route::post('service-tickets/{serviceTicket}/warranty', [ServiceTicketController::class, 'updateWarranty'])->name('service-tickets.warranty');
    Route::post('service-tickets/{serviceTicket}/resend', [ServiceTicketController::class, 'resendNotification'])->name('service-tickets.resend');
    Route::get('transactions/items/availability', [TransactionController::class, 'itemAvailability'])->name('transactions.items.availability');
    Route::get('transactions/{transaction}/invoice', [TransactionController::class, 'invoice'])->name('transactions.invoice');
    Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::get('purchase-reports', [PurchaseReportController::class, 'index'])->name('purchase-reports.index');
    Route::get('purchase-reports/export-pdf', [PurchaseReportController::class, 'exportPdf'])->name('purchase-reports.export-pdf');

    Route::get('sales-reports', [SalesReportController::class, 'index'])->name('sales-reports.index');
    Route::get('sales-reports/export-pdf', [SalesReportController::class, 'exportPdf'])->name('sales-reports.export-pdf');

    Route::get('item-sales-reports', [ItemSalesReportController::class, 'index'])->name('item-sales-reports.index');
    Route::get('item-sales-reports/export-pdf', [ItemSalesReportController::class, 'exportPdf'])->name('item-sales-reports.export-pdf');

    Route::get('category-sales-reports', [CategorySalesReportController::class, 'index'])->name('category-sales-reports.index');
    Route::get('category-sales-reports/export-pdf', [CategorySalesReportController::class, 'exportPdf'])->name('category-sales-reports.export-pdf');

    Route::get('financial-reports', [FinancialReportController::class, 'index'])->name('financial-reports.index');
    Route::get('financial-reports/export-pdf', [FinancialReportController::class, 'exportPdf'])->name('financial-reports.export-pdf');

    Route::get('service-reports', [ServiceReportController::class, 'index'])
        ->name('service-reports.index')
        ->middleware('permission:service report view');
    Route::get('service-reports/export-excel', [ServiceReportController::class, 'exportExcel'])
        ->name('service-reports.export-excel')
        ->middleware('permission:service report view');

    Route::get('technician-reports', [TechnicianReportController::class, 'index'])
        ->name('technician-reports.index')
        ->middleware('permission:technician report view');
    Route::get('technician-reports/export-excel', [TechnicianReportController::class, 'exportExcel'])
        ->name('technician-reports.export-excel')
        ->middleware('permission:technician report view');

    Route::get('manual-incomes', [ManualIncomeController::class, 'index'])->name('manual-incomes.index');
    Route::post('manual-incomes', [ManualIncomeController::class, 'store'])->name('manual-incomes.store');
    Route::get('manual-incomes/{manualIncome}', [ManualIncomeController::class, 'show'])->name('manual-incomes.show');
    Route::delete('manual-incomes/{manualIncome}', [ManualIncomeController::class, 'destroy'])->name('manual-incomes.destroy');

    Route::get('manual-expenses', [ManualExpenseController::class, 'index'])->name('manual-expenses.index');
    Route::post('manual-expenses', [ManualExpenseController::class, 'store'])->name('manual-expenses.store');
    Route::get('manual-expenses/{manualExpense}', [ManualExpenseController::class, 'show'])->name('manual-expenses.show');
    Route::delete('manual-expenses/{manualExpense}', [ManualExpenseController::class, 'destroy'])->name('manual-expenses.destroy');

    Route::resource('bank-accounts', BankAccountController::class)->except(['create', 'edit']);

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
});
