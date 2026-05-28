<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeTimeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ParametersController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSupplierReferenceController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\UnitOfMeasureController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified', 'requireTenant'])->group(function () {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

    Route::post('/company/switch', function (Request $request) {
        $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        session(['current_company_id' => $request->company_id]);

        return redirect()->route('dashboard');
    })->name('company.switch');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [UserController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [UserController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [RoleController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [RoleController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [AuditController::class, 'index'])->name('index');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [AuditController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [AuditController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index'])->name('index');
        Route::get('/create', [SupportTicketController::class, 'create'])->name('create');
        Route::post('/', [SupportTicketController::class, 'store'])->name('store');
        Route::get('/{supportTicket}', [SupportTicketController::class, 'show'])->name('show');

        Route::post('/{supportTicket}/reply', [SupportTicketController::class, 'reply'])->name('reply');
        Route::patch('/{supportTicket}/status', [SupportTicketController::class, 'updateStatus'])->name('update-status');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [SupportTicketController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [SupportTicketController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('parameters')->name('parameters.')->group(function () {
        Route::get('/', [ParametersController::class, 'index'])->name('index');
        Route::patch('/company-default-times', [ParametersController::class, 'updateCompanyDefaultTimes'])->name('company-default-times.update');
        Route::patch('/company-hour-bank', [ParametersController::class, 'updateCompanyHourBank'])->name('company-hour-bank.update');
    });

    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
        Route::get('/create', [DepartmentController::class, 'create'])->name('create');
        Route::post('/', [DepartmentController::class, 'store'])->name('store');
        Route::get('/{department}/edit', [DepartmentController::class, 'edit'])->name('edit');
        Route::put('/{department}', [DepartmentController::class, 'update'])->name('update');
        Route::delete('/{department}', [DepartmentController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [DepartmentController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [DepartmentController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('positions')->name('positions.')->group(function () {
        Route::get('/', [PositionController::class, 'index'])->name('index');
        Route::get('/create', [PositionController::class, 'create'])->name('create');
        Route::post('/', [PositionController::class, 'store'])->name('store');
        Route::get('/{position}/edit', [PositionController::class, 'edit'])->name('edit');
        Route::put('/{position}', [PositionController::class, 'update'])->name('update');
        Route::delete('/{position}', [PositionController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [PositionController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [PositionController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [EmployeeController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [EmployeeController::class, 'exportPdf'])->name('pdf');
        });

        Route::get('/{employee}/times', [EmployeeTimeController::class, 'edit'])->name('times.edit');
        Route::patch('/{employee}/times', [EmployeeTimeController::class, 'update'])->name('times.update');
    });

    Route::prefix('holidays')->name('holidays.')->group(function () {
        Route::get('/', [HolidayController::class, 'index'])->name('index');
        Route::get('/create', [HolidayController::class, 'create'])->name('create');
        Route::post('/', [HolidayController::class, 'store'])->name('store');
        Route::get('/{holiday}/edit', [HolidayController::class, 'edit'])->name('edit');
        Route::put('/{holiday}', [HolidayController::class, 'update'])->name('update');
        Route::delete('/{holiday}', [HolidayController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [HolidayController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [HolidayController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/', [ContactController::class, 'index'])->name('index');
        Route::get('/create', [ContactController::class, 'create'])->name('create');
        Route::post('/', [ContactController::class, 'store'])->name('store');
        Route::get('/{contact}/edit', [ContactController::class, 'edit'])->name('edit');
        Route::put('/{contact}', [ContactController::class, 'update'])->name('update');
        Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [ContactController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [ContactController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('product-categories')->name('product-categories.')->group(function () {
        Route::get('/', [ProductCategoryController::class, 'index'])->name('index');
        Route::get('/create', [ProductCategoryController::class, 'create'])->name('create');
        Route::post('/', [ProductCategoryController::class, 'store'])->name('store');
        Route::get('/{productCategory}/edit', [ProductCategoryController::class, 'edit'])->name('edit');
        Route::put('/{productCategory}', [ProductCategoryController::class, 'update'])->name('update');
        Route::delete('/{productCategory}', [ProductCategoryController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [ProductCategoryController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [ProductCategoryController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('brands')->name('brands.')->group(function () {
        Route::get('/', [BrandController::class, 'index'])->name('index');
        Route::get('/create', [BrandController::class, 'create'])->name('create');
        Route::post('/', [BrandController::class, 'store'])->name('store');
        Route::get('/{brand}/edit', [BrandController::class, 'edit'])->name('edit');
        Route::put('/{brand}', [BrandController::class, 'update'])->name('update');
        Route::delete('/{brand}', [BrandController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [BrandController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [BrandController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('unit-of-measures')->name('unit-of-measures.')->group(function () {
        Route::get('/', [UnitOfMeasureController::class, 'index'])->name('index');
        Route::get('/create', [UnitOfMeasureController::class, 'create'])->name('create');
        Route::post('/', [UnitOfMeasureController::class, 'store'])->name('store');
        Route::get('/{unitOfMeasure}/edit', [UnitOfMeasureController::class, 'edit'])->name('edit');
        Route::put('/{unitOfMeasure}', [UnitOfMeasureController::class, 'update'])->name('update');
        Route::delete('/{unitOfMeasure}', [UnitOfMeasureController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [UnitOfMeasureController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [UnitOfMeasureController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/dashboard', [ProductController::class, 'dashboard'])->name('dashboard');

        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [ProductController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [ProductController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::get('/create', [SupplierController::class, 'create'])->name('create');
        Route::post('/', [SupplierController::class, 'store'])->name('store');
        Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [SupplierController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [SupplierController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('supplier-product-references')->name('supplier-product-references.')->group(function () {
        Route::get('/', [ProductSupplierReferenceController::class, 'index'])->name('index');
        Route::get('/create', [ProductSupplierReferenceController::class, 'create'])->name('create');
        Route::post('/', [ProductSupplierReferenceController::class, 'store'])->name('store');
        Route::get('/{supplierProductReference}/edit', [ProductSupplierReferenceController::class, 'edit'])->name('edit');
        Route::put('/{supplierProductReference}', [ProductSupplierReferenceController::class, 'update'])->name('update');
        Route::delete('/{supplierProductReference}', [ProductSupplierReferenceController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [ProductSupplierReferenceController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [ProductSupplierReferenceController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [ClientController::class, 'index'])->name('index');
        Route::get('/create', [ClientController::class, 'create'])->name('create');
        Route::post('/', [ClientController::class, 'store'])->name('store');
        Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('edit');
        Route::put('/{client}', [ClientController::class, 'update'])->name('update');
        Route::delete('/{client}', [ClientController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [ClientController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [ClientController::class, 'exportPdf'])->name('pdf');
        });
    });
});

require base_path('app/Modules/Worktime/Routes/web.php');
require base_path('app/Modules/Inventory/Routes/web.php');
require base_path('app/Modules/Production/Routes/web.php');
require base_path('app/Modules/Order/Routes/web.php');
require base_path('app/Modules/PurchaseReceipt/Routes/web.php');

require __DIR__ . '/settings.php';
