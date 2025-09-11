<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

// Safely include Breeze/Fortify auth routes if they exist
$authFile = __DIR__ . '/auth.php';
if (is_file($authFile)) {
    require $authFile;
}

/**
 * Public landing:
 *  - If authenticated, push them through the role router ("/dashboard").
 *  - Else show login (when Breeze/Fortify present).
 */
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard'); // role-based redirect below
    }
    return Route::has('login')
        ? redirect()->route('login')
        : response('OK', 200);
});

/**
 * Unified, role-based post-login redirect.
 * Breeze redirects to RouteServiceProvider::HOME (usually "/dashboard"),
 * so we handle the switch here.
 */
Route::get('/dashboard', function () {
    $role = auth()->user()->role ?? null;

    return match ($role) {
        'Admin' => redirect()->route('admin.dashboard.index'),
        'Houseowner' => redirect()->route('customer.bills.index'),
        'Merchant' => redirect()->route('merchant.rentals.index'),
        'Employee' => redirect()->route('employee.home'),
        default => abort(403),
    };
})->middleware('auth')->name('dashboard');

Route::middleware(['auth'])->group(function () {

    // ----- Profile (Breeze nav expects these) -----
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ----- Admin -----
    Route::middleware('role:Admin')->prefix('admin')->as('admin.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])
            ->name('dashboard.index');

        // Houses
        Route::get('/houses', [App\Http\Controllers\Admin\HouseController::class, 'index'])->name('houses.index');
        Route::get('/houses/create', [App\Http\Controllers\Admin\HouseController::class, 'create'])->name('houses.create');
        Route::post('/houses', [App\Http\Controllers\Admin\HouseController::class, 'store'])->name('houses.store');
        Route::get('/houses/{houseNo}', [App\Http\Controllers\Admin\HouseController::class, 'show'])->name('houses.show');
        Route::get('/houses/{houseNo}/edit', [App\Http\Controllers\Admin\HouseController::class, 'edit'])->name('houses.edit');
        Route::put('/houses/{houseNo}', [App\Http\Controllers\Admin\HouseController::class, 'update'])->name('houses.update');
        Route::delete('/houses/{houseNo}', [App\Http\Controllers\Admin\HouseController::class, 'destroy'])->name('houses.destroy');
        // House Bills (HouseRental)
        Route::get('/house-bills', [App\Http\Controllers\Admin\HouseBillController::class, 'index'])->name('house-bills.index');
        Route::post('/house-bills/generate', [App\Http\Controllers\Admin\HouseBillController::class, 'generate'])->name('house-bills.generate');
        Route::post('/house-bills/{id}/approve', [App\Http\Controllers\Admin\HouseBillApproveController::class, 'approve'])->name('house-bills.approve');
        Route::post('/house-bills/{id}/reject', [App\Http\Controllers\Admin\HouseBillApproveController::class, 'reject'])->name('house-bills.reject');


        Route::resource('shops', App\Http\Controllers\Admin\ShopController::class)
            ->names('shops')
            ->except('show');

        // Shop Rentals
        Route::get('/shop-rentals', [App\Http\Controllers\Admin\ShopRentalController::class, 'index'])
            ->name('shop-rentals.index');
        Route::post('/shop-rentals/generate', [App\Http\Controllers\Admin\ShopRentalController::class, 'generate'])
            ->name('shop-rentals.generate'); // <-- add this
        Route::post('/shop-rentals/{id}/approve', [App\Http\Controllers\Admin\ShopRentalApproveController::class, 'approve'])
            ->name('shop-rentals.approve');
        Route::post('/shop-rentals/{id}/reject', [App\Http\Controllers\Admin\ShopRentalApproveController::class, 'reject'])
            ->name('shop-rentals.reject');

        // Inventory Sales
        Route::resource('inventory-sales', App\Http\Controllers\Admin\InventorySaleController::class)
            ->names('inventory-sales');

        // Contracts
        Route::resource('contracts', App\Http\Controllers\Admin\ContractsController::class)
            ->names('contracts')->except('show');

        // Payroll
        Route::get('/payroll', [App\Http\Controllers\Admin\PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll', [App\Http\Controllers\Admin\PayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/history', [App\Http\Controllers\Admin\PayrollController::class, 'history'])->name('payroll.history');

        // Other Expenses
        Route::resource('expenses', App\Http\Controllers\Admin\ExpenseController::class)
            ->names('expenses')->except('show');

        // Reports
        Route::get('/reports', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/pdf', [App\Http\Controllers\Admin\ReportExportController::class, 'pdf'])->name('reports.export.pdf');
        Route::get('/reports/export/csv', [App\Http\Controllers\Admin\ReportExportController::class, 'csv'])->name('reports.export.csv');

        // Settings
        Route::get('/settings', [App\Http\Controllers\Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::post('/settings', [App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');

        // Users
        Route::resource('users', App\Http\Controllers\Admin\UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    });

    // ----- Customer (Houseowner) -----
    Route::middleware('role:Houseowner')->prefix('customer')->as('customer.')->group(function () {
        Route::get('/bills', [App\Http\Controllers\Customer\BillController::class, 'index'])->name('bills.index');
        Route::post('/bills/{id}/pay/transfer', [App\Http\Controllers\Customer\BillPayController::class, 'transfer'])->name('bills.pay.transfer');
        Route::post('/bills/{id}/pay/card', [App\Http\Controllers\Customer\BillPayController::class, 'card'])->name('bills.pay.card');
    });

    // ----- Merchant (Shop Owner) -----
    Route::middleware('role:Merchant')->prefix('merchant')->as('merchant.')->group(function () {
        Route::get('/rentals', [App\Http\Controllers\Merchant\RentalController::class, 'index'])->name('rentals.index');
        Route::post('/rentals/{id}/pay/transfer', [App\Http\Controllers\Merchant\RentalPayController::class, 'transfer'])->name('rentals.pay.transfer');
        Route::post('/rentals/{id}/pay/card', [App\Http\Controllers\Merchant\RentalPayController::class, 'card'])->name('rentals.pay.card');
    });

    // ----- Employee placeholder -----
    Route::middleware('role:Employee')->get('/employee', function () {
        return 'Employee portal';
    })->name('employee.home');
});
