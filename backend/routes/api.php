<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductCostController;
use App\Http\Controllers\Api\SettlementController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/suppliers', SupplierController::class)->names('suppliers');

    Route::put('/suppliers/{supplier}/status', [SupplierController::class, 'updateStatus'])->name('suppliers.update-status');
    Route::get('/suppliers/{supplier}/status-logs', [SupplierController::class, 'statusLogs'])->name('suppliers.status-logs');
    Route::get('/suppliers/{supplier}/allowed-transitions', [SupplierController::class, 'allowedTransitions'])->name('suppliers.allowed-transitions');
    Route::post('/suppliers/{supplier}/validate-transition', [SupplierController::class, 'validateTransition'])->name('suppliers.validate-transition');

    Route::put('/suppliers/{supplier}/verify', [SupplierController::class, 'verify'])->name('suppliers.verify');
    Route::put('/suppliers/{supplier}/activate', [SupplierController::class, 'activate'])->name('suppliers.activate');
    Route::put('/suppliers/{supplier}/suspend', [SupplierController::class, 'suspend'])->name('suppliers.suspend');
    Route::put('/suppliers/{supplier}/reject', [SupplierController::class, 'reject'])->name('suppliers.reject');
    Route::put('/suppliers/{supplier}/cancel', [SupplierController::class, 'cancel'])->name('suppliers.cancel');

    Route::get('products/calculate-cost', [ProductController::class, 'calculateCost']);
    Route::apiResource('products', ProductController::class);

    Route::post('product-costs/batch', [ProductCostController::class, 'batchStore']);
    Route::post('product-costs/{id}/toggle-active', [ProductCostController::class, 'toggleActive']);
    Route::apiResource('product-costs', ProductCostController::class);

    Route::get('settlements/statistics', [SettlementController::class, 'statistics']);
    Route::post('settlements/preview', [SettlementController::class, 'preview']);
    Route::post('settlements/{id}/recalculate', [SettlementController::class, 'recalculate']);
    Route::post('settlements/{id}/confirm', [SettlementController::class, 'confirm']);
    Route::post('settlements/{id}/settle', [SettlementController::class, 'settle']);
    Route::post('settlements/{id}/cancel', [SettlementController::class, 'cancel']);
    Route::apiResource('settlements', SettlementController::class);
});
