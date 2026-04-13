<?php

use App\Modules\PurchaseReceipt\Http\Controllers\PurchaseReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'requireTenant'])->group(function () {
    Route::prefix('purchase-receipts')->name('purchase-receipts.')->group(function () {
        Route::get('/', [PurchaseReceiptController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseReceiptController::class, 'create'])->name('create');
        Route::post('/', [PurchaseReceiptController::class, 'store'])->name('store');
        Route::get('/{purchaseReceipt}', [PurchaseReceiptController::class, 'show'])->name('show');
        Route::get('/{purchaseReceipt}/edit', [PurchaseReceiptController::class, 'edit'])->name('edit');
        Route::put('/{purchaseReceipt}', [PurchaseReceiptController::class, 'update'])->name('update');
        Route::delete('/{purchaseReceipt}', [PurchaseReceiptController::class, 'destroy'])->name('destroy');

        Route::post('/{purchaseReceipt}/post', [PurchaseReceiptController::class, 'post'])->name('post');
        Route::post('/{purchaseReceipt}/cancel', [PurchaseReceiptController::class, 'cancel'])->name('cancel');
    });
});
