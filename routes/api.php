<?php

use App\Http\Controllers\MobileApprovalController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/config', [MobileApprovalController::class, 'config']);
    Route::get('/app-version', [MobileApprovalController::class, 'appVersion']);
    Route::post('/login', [MobileApprovalController::class, 'login']);
    Route::post('/logout', [MobileApprovalController::class, 'logout']);
    Route::get('/me', [MobileApprovalController::class, 'me']);
    Route::get('/dashboard', [MobileApprovalController::class, 'dashboard']);
    Route::get('/notifications', [MobileApprovalController::class, 'notifications']);
    Route::post('/device-token', [MobileApprovalController::class, 'deviceToken']);
    Route::get('/history', [MobileApprovalController::class, 'history']);
    Route::get('/web/dashboard', [MobileApprovalController::class, 'webDashboard']);
    Route::get('/web/dashboard/detail', [MobileApprovalController::class, 'webDashboardDetail']);
    Route::get('/web/detail', [MobileApprovalController::class, 'webDashboardDetail']);
    Route::get('/web/history', [MobileApprovalController::class, 'webHistory']);
    Route::get('/web/section/pb-verification', [MobileApprovalController::class, 'webSectionPbVerification']);
    Route::get('/web/section/work-orders', [MobileApprovalController::class, 'webSectionWorkOrders']);
    Route::get('/web/section/done-today', [MobileApprovalController::class, 'webSectionDoneToday']);
    Route::get('/web/stock-sparepart', [MobileApprovalController::class, 'webStockSparepart']);
    Route::get('/web/pb/{id}', [MobileApprovalController::class, 'webPbDetail'])->whereNumber('id');
    Route::get('/web/wo/{id}', [MobileApprovalController::class, 'webWoDetail'])->whereNumber('id');
    Route::get('/web/engineering', [MobileApprovalController::class, 'webEngineeringHome']);
    Route::get('/web/engineering/pb', [MobileApprovalController::class, 'webEngineeringPb']);
    Route::get('/web/engineering/pb/create', [MobileApprovalController::class, 'webEngineeringPbCreate']);
    Route::get('/web/engineering/wo', [MobileApprovalController::class, 'webEngineeringWo']);
    Route::get('/web/engineering/wo/create', [MobileApprovalController::class, 'webEngineeringWoCreate']);
    Route::get('/engineering/search/targets', [MobileApprovalController::class, 'engineeringSearchTargets']);
    Route::get('/engineering/search/items', [MobileApprovalController::class, 'engineeringSearchItems']);
    Route::get('/engineering/search/work-orders', [MobileApprovalController::class, 'engineeringSearchWorkOrders']);
    Route::post('/engineering/pb', [MobileApprovalController::class, 'engineeringPbStore']);
    Route::post('/engineering/wo', [MobileApprovalController::class, 'engineeringWoStore']);
    Route::post('/engineering/wo/{id}/submit', [MobileApprovalController::class, 'engineeringWoSubmit'])->whereNumber('id');

    Route::get('/pb', [MobileApprovalController::class, 'pbIndex']);
    Route::get('/pb/{id}', [MobileApprovalController::class, 'pbShow'])->whereNumber('id');
    Route::post('/pb/{id}/approve', [MobileApprovalController::class, 'pbApprove'])->whereNumber('id');
    Route::post('/pb/{id}/reject', [MobileApprovalController::class, 'pbReject'])->whereNumber('id');

    Route::get('/wo', [MobileApprovalController::class, 'woIndex']);
    Route::get('/wo/{id}', [MobileApprovalController::class, 'woShow'])->whereNumber('id');
    Route::get('/wo/{id}/document', [MobileApprovalController::class, 'woDocument'])->whereNumber('id');
    Route::get('/pelaksana', [MobileApprovalController::class, 'pelaksana']);
    Route::post('/wo/{id}/approve', [MobileApprovalController::class, 'woApprove'])->whereNumber('id');
    Route::post('/wo/{id}/reject', [MobileApprovalController::class, 'woReject'])->whereNumber('id');

    Route::get('/section/work-orders', [MobileApprovalController::class, 'sectionWorkOrders']);
    Route::get('/section/work-orders/history', [MobileApprovalController::class, 'sectionWorkOrderHistory']);
    Route::post('/section/work-orders/{id}/progress', [MobileApprovalController::class, 'sectionWorkOrderProgress'])->whereNumber('id');
    Route::post('/section/work-orders/{id}/photos', [MobileApprovalController::class, 'sectionWorkOrderPhotos'])->whereNumber('id');
    Route::post('/section/work-orders/{id}/done', [MobileApprovalController::class, 'sectionWorkOrderDone'])->whereNumber('id');
});
