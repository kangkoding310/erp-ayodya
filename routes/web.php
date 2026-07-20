<?php

use App\Http\Controllers\Accounting\AccountingBillController;
use App\Http\Controllers\Accounting\PurchaseInvoiceController;
use App\Http\Controllers\Accounting\PurchasePaymentController;
use App\Http\Controllers\ApprovalMatrix\ApprovalMatrixController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Expense\ExpenseApprovalController;
use App\Http\Controllers\Expense\ExpenseReportController;
use App\Http\Controllers\MasterData\BankController;
use App\Http\Controllers\MasterData\CoaController;
use App\Http\Controllers\MasterData\DivisionController;
use App\Http\Controllers\MasterData\EmployeeController;
use App\Http\Controllers\MasterData\ProductCategoryController;
use App\Http\Controllers\MasterData\ProductController;
use App\Http\Controllers\MasterData\ProjectController;
use App\Http\Controllers\MasterData\PurchaseTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Purchase\PurchaseApprovalController;
use App\Http\Controllers\Purchase\PurchaseRequestController;
use App\Http\Controllers\Purchase\PurchaseRequestLineController;
use App\Http\Controllers\Purchase\PurchaseRequestMessageController;
use App\Http\Controllers\Purchase\PurchaseRfqController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
    $user = Auth::user();
    if ($user) return redirect('/dashboard');
    else return redirect('/login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Approval Matrix
    Route::prefix('approval-matrix')->name('approval-matrix.')->group(function () {
        Route::get('/', [ApprovalMatrixController::class, 'index'])->name('index');
        Route::post('/', [ApprovalMatrixController::class, 'store'])->name('store');
        Route::put('/{approvalMatrix}', [ApprovalMatrixController::class, 'update'])->name('update');
        Route::delete('/{approvalMatrix}', [ApprovalMatrixController::class, 'destroy'])->name('destroy');
    });

    // Master Data
    Route::prefix('master-data')->name('master-data.')->group(function () {
        Route::resource('products', ProductController::class)->except('show');
        Route::resource('product-categories', ProductCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('banks', BankController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('coa', CoaController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('employees', EmployeeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('divisions', DivisionController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('purchase-types', PurchaseTypeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('projects', ProjectController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    // Purchase
    Route::prefix('purchase')->name('purchase.')->group(function () {
        Route::resource('requests', PurchaseRequestController::class)->parameters(['requests' => 'purchaseRequest']);
        Route::post('requests/{purchaseRequest}/submit', [PurchaseRequestController::class, 'submit'])->name('requests.submit');

        Route::post('requests/{purchaseRequest}/lines', [PurchaseRequestLineController::class, 'store'])->name('requests.lines.store');
        Route::put('requests/{purchaseRequest}/lines/{line}', [PurchaseRequestLineController::class, 'update'])->name('requests.lines.update');
        Route::delete('requests/{purchaseRequest}/lines/{line}', [PurchaseRequestLineController::class, 'destroy'])->name('requests.lines.destroy');

        Route::post('requests/{purchaseRequest}/messages', [PurchaseRequestMessageController::class, 'store'])->name('requests.messages.store');

        Route::get('approvals', [PurchaseApprovalController::class, 'index'])->name('approvals.index');
        Route::get('approvals/{approval}', [PurchaseApprovalController::class, 'show'])->name('approvals.show');
        Route::post('approvals/{approval}/approve', [PurchaseApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('approvals/{approval}/reject', [PurchaseApprovalController::class, 'reject'])->name('approvals.reject');

        Route::get('rfq', [PurchaseRfqController::class, 'index'])->name('rfq.index');
        Route::post('rfq/{purchaseRequest}', [PurchaseRfqController::class, 'store'])->name('rfq.store');
        Route::post('rfq/{purchaseRfq}/send-to-accounting', [PurchaseRfqController::class, 'sendToAccounting'])->name('rfq.send-to-accounting');
    });

    // Expense
    Route::prefix('expense')->name('expense.')->group(function () {
        Route::resource('reports', ExpenseReportController::class)->parameters(['reports' => 'expenseReport']);
        Route::post('reports/{expenseReport}/submit', [ExpenseReportController::class, 'submit'])->name('reports.submit');
        Route::post('reports/{expenseReport}/cancel', [ExpenseReportController::class, 'cancel'])->name('reports.cancel');
        Route::post('reports/{expenseReport}/send-to-accounting', [ExpenseReportController::class, 'sendToAccounting'])->name('reports.send-to-accounting');

        Route::get('approvals', [ExpenseApprovalController::class, 'index'])->name('approvals.index');
        Route::get('approvals/{approval}', [ExpenseApprovalController::class, 'show'])->name('approvals.show');
        Route::post('approvals/{approval}/approve', [ExpenseApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('approvals/{approval}/reject', [ExpenseApprovalController::class, 'reject'])->name('approvals.reject');
    });

    // Accounting
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('bills', [AccountingBillController::class, 'index'])->name('bills.index');
        Route::get('bills/{bill}', [AccountingBillController::class, 'show'])->name('bills.show');

        Route::resource('purchase-invoices', PurchaseInvoiceController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('purchase-payments', PurchasePaymentController::class)->only(['index', 'create', 'store', 'show']);
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
