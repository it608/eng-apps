<?php

use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\BangunanController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoricalImportController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\MesinController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UtilityOverheadController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\Warehouse2\DashboardController as Warehouse2DashboardController;
use App\Http\Controllers\Warehouse2\IssuingController as Warehouse2IssuingController;
use App\Http\Controllers\Warehouse2\ItemController as Warehouse2ItemController;
use App\Http\Controllers\Warehouse2\ReceivingController as Warehouse2ReceivingController;
use App\Http\Controllers\Warehouse2\StockController as Warehouse2StockController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('landing');
});

Route::get('/logout-success', function () {
    return view('auth.logout-success');
})->middleware('guest')->name('logout.success');

// ============= DASHBOARD =============
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/admin', [DashboardController::class, 'index'])
        ->middleware('role:admin,approval')
        ->name('admin.dashboard');
});

// ============= ROUTE E-REQUEST ALIAS =============
Route::middleware(['auth'])->prefix('e-requests')->name('e-requests.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('transaksi.index');
    })->name('index');

    Route::get('/create', function () {
        return request('service_key') === 'engineering_service'
            ? redirect()->route('workorder.index')
            : redirect()->route('transaksi.index');
    })->name('create');

    Route::get('/{id}', function () {
        return redirect()->route('transaksi.index');
    })->whereNumber('id')->name('show');
});

// ============= ROUTE ADMIN =============
Route::middleware(['auth', 'role:admin,approval'])
    ->prefix('admin')
    ->group(function () {
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
            Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
            Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('admin.users.toggle-active');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

            Route::get('/departments', [DepartmentController::class, 'index'])->name('admin.departments.index');
            Route::get('/departments/create', [DepartmentController::class, 'create'])->name('admin.departments.create');
            Route::post('/departments', [DepartmentController::class, 'store'])->name('admin.departments.store');
            Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('admin.departments.edit');
            Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('admin.departments.update');
            Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('admin.departments.destroy');
        });

        Route::put('/mesin/{id}', [MasterController::class, 'updateMesin'])
            ->whereNumber('id')
            ->name('admin.mesin.update');

        Route::put('/bangunan/{id}', [MasterController::class, 'updateBangunan'])
            ->whereNumber('id')
            ->name('admin.bangunan.update');

        Route::get('/logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('admin.logs');
        Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('admin.audit-logs.index');

        Route::redirect('/reports', '/report');
        Route::redirect('/reports-analytics', '/report');
    });

// ============= ROUTE MASTER DATA =============
Route::middleware(['auth'])->group(function () {
    Route::get('/master', [MasterController::class, 'index'])->name('master.index');

    Route::prefix('master')->name('master.')->group(function () {
        Route::get('/sparepart/data', [MasterController::class, 'getSparepartData'])->name('sparepart.data');
        Route::get('/mesin/data', [MasterController::class, 'getMesinData'])->name('mesin.data');
        Route::post('/mesin', [MasterController::class, 'storeMesin'])->name('mesin.store');
        Route::get('/bangunan/data', [MasterController::class, 'getBangunanData'])->name('bangunan.data');
    });
});

// ============= ROUTE GOOD ISSUE ERP (READ-ONLY) =============
Route::middleware(['auth'])->get('/good-issue-erp', [StockController::class, 'goodIssueIndex'])->name('good-issue.index');

// ============= ROUTE UTILITY OVERHEAD =============
Route::middleware(['auth'])->prefix('utility-overhead')->name('utility-overhead.')->group(function () {
    Route::get('/', [UtilityOverheadController::class, 'index'])->name('index');
    Route::post('/', [UtilityOverheadController::class, 'store'])->name('store');
    Route::get('/{record}/edit', [UtilityOverheadController::class, 'edit'])->whereNumber('record')->name('edit');
    Route::put('/{record}', [UtilityOverheadController::class, 'update'])->whereNumber('record')->name('update');
    Route::delete('/{record}', [UtilityOverheadController::class, 'destroy'])->whereNumber('record')->name('destroy');
});

// ============= ROUTE HISTORICAL IMPORT PB & WO =============
Route::middleware(['auth'])->prefix('historical-import')->name('historical-import.')->group(function () {
    Route::get('/', [HistoricalImportController::class, 'index'])->name('index');
    Route::get('/template', [HistoricalImportController::class, 'template'])->name('template');
    Route::post('/', [HistoricalImportController::class, 'store'])->name('store');
    Route::get('/{batch}', [HistoricalImportController::class, 'show'])->whereNumber('batch')->name('show');
    Route::post('/{batch}/submit', [HistoricalImportController::class, 'submit'])->whereNumber('batch')->name('submit');
    Route::post('/{batch}/sign-off', [HistoricalImportController::class, 'signOff'])->whereNumber('batch')->name('sign-off');
});

// ============= ROUTE STOCK SPAREPART =============
Route::middleware(['auth'])->prefix('stock')->name('stock.')->group(function () {
    Route::get('/', [StockController::class, 'index'])->name('index');
    Route::get('/data', [StockController::class, 'getStockData'])->name('data');
    Route::get('/detail/{id}', [StockController::class, 'getDetail'])->name('detail');
    Route::get('/movement', [StockController::class, 'getMovement'])->name('movement');
    Route::get('/good-issue', [StockController::class, 'getGoodIssue'])->name('good-issue');
    Route::get('/good-issue/export', [StockController::class, 'exportGoodIssue'])->name('good-issue.export');
    Route::post('/opname', [StockController::class, 'saveOpname'])->name('opname');
    Route::get('/export', [StockController::class, 'export'])->name('export');
    Route::get('/by-location/{location?}', [StockController::class, 'getByLocation'])->name('by-location');
});

// ============= ROUTE STOCK NON-SPAREPART =============
Route::middleware(['auth'])->prefix('stock-non-sparepart')->name('stock-non-sparepart.')->group(function () {
    Route::get('/', [StockController::class, 'index'])->name('index');
    Route::get('/data', [StockController::class, 'getStockData'])->name('data');
    Route::get('/detail/{id}', [StockController::class, 'getDetail'])->name('detail');
    Route::get('/movement', [StockController::class, 'getMovement'])->name('movement');
    Route::get('/good-issue', [StockController::class, 'getGoodIssue'])->name('good-issue');
    Route::post('/opname', [StockController::class, 'saveOpname'])->name('opname');
    Route::get('/export', [StockController::class, 'export'])->name('export');
    Route::get('/by-location/{location?}', [StockController::class, 'getByLocation'])->name('by-location');
});

// ============= ROUTE WORK ORDER =============
Route::middleware(['auth'])->prefix('workorder')->name('workorder.')->group(function () {
    Route::get('/', [WorkOrderController::class, 'index'])->name('index');
    Route::get('/data', [WorkOrderController::class, 'getData'])->name('data');
    Route::get('/generate-nomor', [WorkOrderController::class, 'generateNomor'])->name('generate-nomor');
    Route::post('/store', [WorkOrderController::class, 'store'])->name('store');
    Route::get('/preview/{id}', [WorkOrderController::class, 'preview'])->whereNumber('id')->name('preview');
    Route::get('/download/{id}', [WorkOrderController::class, 'download'])->whereNumber('id')->name('download');
    Route::delete('/delete/{id}', [WorkOrderController::class, 'destroy'])->whereNumber('id')->name('delete');
    Route::post('/submit/{id}', [WorkOrderController::class, 'submit'])->whereNumber('id')->name('submit');
    Route::post('/approve/{id}', [WorkOrderController::class, 'approve'])->whereNumber('id')->name('approve');
    Route::post('/reject/{id}', [WorkOrderController::class, 'reject'])->whereNumber('id')->name('reject');
    Route::get('/progress-data', [WorkOrderController::class, 'progressData'])->name('progress.data');
    Route::get('/photo/{id}', [WorkOrderController::class, 'photo'])->whereNumber('id')->name('photo');
    Route::post('/progress/{id}', [WorkOrderController::class, 'updateProgress'])->whereNumber('id')->name('progress.update');
    Route::get('/timeline/{id}', [WorkOrderController::class, 'getTimeline'])->whereNumber('id')->name('timeline');
});

// ============= ROUTE TRANSAKSI =============
Route::middleware(['auth'])->group(function () {
    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    Route::post('/transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
    Route::get('/transaksi/generate-nomor', [TransaksiController::class, 'generateNomor'])->name('transaksi.generate-nomor');
    Route::get('/transaksi/approved-work-orders', [TransaksiController::class, 'approvedWorkOrders'])->name('transaksi.approved-work-orders');
    Route::get('/transaksi/{id}', [TransaksiController::class, 'show'])->whereNumber('id')->name('transaksi.show');
});

Route::middleware(['auth', 'role:section_head,admin'])
    ->prefix('pb-verification')
    ->name('pb-verification.')
    ->group(function () {
        Route::get('/', [TransaksiController::class, 'verificationIndex'])->name('index');
        Route::get('/data', [TransaksiController::class, 'verificationData'])->name('data');
        Route::get('/history', [TransaksiController::class, 'verificationHistoryData'])->name('history');
        Route::post('/{id}/verify', [TransaksiController::class, 'verify'])->whereNumber('id')->name('verify');
        Route::post('/{id}/reject', [TransaksiController::class, 'rejectVerification'])->whereNumber('id')->name('reject');
    });

// ============= ROUTE UNTUK API =============
Route::prefix('api')->middleware(['auth'])->group(function () {
    Route::get('/barang/search', [BarangController::class, 'search'])->name('api.barang.search');
    Route::get('/mesin/list', [MesinController::class, 'list'])->name('api.mesin.list');
    Route::get('/mesin/search', [MesinController::class, 'search'])->name('api.mesin.search');
    Route::get('/mesin/{id}', [MesinController::class, 'show'])->whereNumber('id')->name('api.mesin.show');
    Route::get('/mesin/{id}/spare-parts', [MesinController::class, 'spareParts'])->whereNumber('id')->name('api.mesin.spare-parts');
    Route::get('/bangunan/list', [BangunanController::class, 'list'])->name('api.bangunan.list');
    Route::get('/bangunan/search', [BangunanController::class, 'search'])->name('api.bangunan.search');
    Route::get('/bangunan/{id}', [BangunanController::class, 'show'])->whereNumber('id')->name('api.bangunan.show');
    Route::get('/stock/summary', [StockController::class, 'summary'])->name('api.stock.summary');
    Route::get('/stock/history/{id}', [StockController::class, 'history'])->name('api.stock.history');
});

Route::get('/cari-barang', [BarangController::class, 'search'])->name('barang.search')->middleware('auth');

// ============= ROUTE NOTIFIKASI =============
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/user', [NotificationController::class, 'user'])->name('user');
    Route::get('/approval1', [NotificationController::class, 'approvalLevelOne'])->name('approval1');
    Route::get('/approval2', [NotificationController::class, 'approvalLevelTwo'])->name('approval2');
    Route::get('/section-head', [NotificationController::class, 'sectionHead'])->name('section-head');
});

// ============= ROUTE UNTUK APPROVAL =============
Route::middleware(['auth', 'role:approval,approval2,admin'])
    ->prefix('approval')
    ->name('approval.')
    ->group(function () {
        Route::get('/pending', [ApprovalController::class, 'index'])->name('index');
        Route::post('/bulk-approve', [ApprovalController::class, 'bulkApprove'])->name('bulk');
        Route::get('/statistics', [ApprovalController::class, 'statistics'])->name('stats');
        Route::post('/{id}/approve', [ApprovalController::class, 'approve'])->whereNumber('id')->name('approve');
        Route::post('/{id}/reject', [ApprovalController::class, 'reject'])->whereNumber('id')->name('reject');
    });

// ============= ROUTE WAREHOUSE PB FULFILLMENT =============
Route::middleware(['auth', 'role:warehouse,admin'])
    ->prefix('warehouse')
    ->name('warehouse.')
    ->group(function () {
        Route::get('/pb', [WarehouseController::class, 'index'])->name('pb.index');
        Route::get('/pb/data', [WarehouseController::class, 'data'])->name('pb.data');
        Route::get('/pb/{id}', [WarehouseController::class, 'show'])->whereNumber('id')->name('pb.show');
        Route::post('/pb/{id}/erp-reference', [WarehouseController::class, 'updateReference'])
            ->whereNumber('id')
            ->name('pb.erp-reference');
        Route::get('/pb/{id}/items/{detailId}/stock-options', [WarehouseController::class, 'stockOptions'])
            ->whereNumber('id')
            ->whereNumber('detailId')
            ->name('pb.items.stock-options');
        Route::post('/pb/{id}/items/{detailId}', [WarehouseController::class, 'updateItem'])
            ->whereNumber('id')
            ->whereNumber('detailId')
            ->name('pb.items.update');
        Route::get('/pb/stock-receipt/{receiptNumber}', [WarehouseController::class, 'printStockReceipt'])
            ->name('pb.stock-receipt.print');
    });

// ============= ROUTE WAREHOUSE 2 =============
Route::middleware(['auth'])->prefix('warehouse2')->name('warehouse2.')->group(function () {
    Route::get('/dashboard', [Warehouse2DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/', [Warehouse2StockController::class, 'index'])->name('index');
        Route::get('/data', [Warehouse2StockController::class, 'getData'])->name('data');
        Route::get('/export', [Warehouse2StockController::class, 'export'])->name('export');
        Route::get('/summary', [Warehouse2StockController::class, 'summary'])->name('summary');
        Route::get('/movement/{id}', [Warehouse2StockController::class, 'movement'])->whereNumber('id')->name('movement');
        Route::post('/adjust/{id}', [Warehouse2StockController::class, 'adjust'])->middleware('role:warehouse,admin')->whereNumber('id')->name('adjust');
        Route::post('/opname', [Warehouse2StockController::class, 'storeOpname'])->middleware('role:warehouse,admin')->name('opname.store');
        Route::get('/opname-data', [Warehouse2StockController::class, 'opnameData'])->name('opname.data');
        Route::get('/opname/{id}', [Warehouse2StockController::class, 'opnameShow'])->whereNumber('id')->name('opname.show');
        Route::get('/{id}', [Warehouse2StockController::class, 'show'])->whereNumber('id')->name('show');
    });

    Route::prefix('receiving')->name('receiving.')->group(function () {
        Route::get('/', [Warehouse2ReceivingController::class, 'index'])->name('index');
        Route::get('/data', [Warehouse2ReceivingController::class, 'getData'])->name('data');
        Route::get('/create', [Warehouse2ReceivingController::class, 'create'])->middleware('role:warehouse,admin')->name('create');
        Route::post('/', [Warehouse2ReceivingController::class, 'store'])->middleware('role:warehouse,admin')->name('store');
        Route::get('/print/{id}', [Warehouse2ReceivingController::class, 'print'])->whereNumber('id')->name('print');
        Route::get('/download-pdf/{id}', [Warehouse2ReceivingController::class, 'downloadPdf'])->whereNumber('id')->name('download-pdf');
        Route::get('/{id}', [Warehouse2ReceivingController::class, 'show'])->whereNumber('id')->name('show');
    });

    Route::prefix('issuing')->name('issuing.')->group(function () {
        Route::get('/', [Warehouse2IssuingController::class, 'index'])->name('index');
        Route::get('/data', [Warehouse2IssuingController::class, 'getData'])->name('data');
        Route::get('/create', [Warehouse2IssuingController::class, 'create'])->middleware('role:warehouse,admin')->name('create');
        Route::post('/', [Warehouse2IssuingController::class, 'store'])->middleware('role:warehouse,admin')->name('store');
        Route::get('/print/{id}', [Warehouse2IssuingController::class, 'print'])->whereNumber('id')->name('print');
        Route::get('/download-pdf/{id}', [Warehouse2IssuingController::class, 'downloadPdf'])->whereNumber('id')->name('download-pdf');
        Route::get('/{id}', [Warehouse2IssuingController::class, 'show'])->whereNumber('id')->name('show');
    });

    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/items/search', [Warehouse2ItemController::class, 'search'])->name('items.search');
        Route::get('/items/{id}', [Warehouse2ItemController::class, 'show'])->whereNumber('id')->name('items.show');
        Route::get('/stock/by-item/{id}', [Warehouse2StockController::class, 'getByItem'])->whereNumber('id')->name('stock.by-item');
        Route::get('/dashboard/stats', [Warehouse2DashboardController::class, 'stats'])->name('dashboard.stats');
    });
});

// ============= ROUTE FIX FILENAME WORK ORDER =============
Route::middleware(['auth', 'role:admin'])->get('/fix-filenames', function () {
    try {
        $wos = DB::table('trWorkOrder')->get();
        $fixed = 0;
        $errors = [];

        foreach ($wos as $wo) {
            try {
                $oldName = $wo->file_name;
                $oldPath = $wo->file_path;

                if (!preg_match('/[\/\\\\]/', $oldName)) {
                    continue;
                }

                $newName = preg_replace('/[\/\\\\]/', '_', $oldName);
                $newPath = str_replace($oldName, $newName, $oldPath);

                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->move($oldPath, $newPath);
                }

                DB::table('trWorkOrder')->where('id', $wo->id)->update([
                    'file_name' => $newName,
                    'file_path' => $newPath,
                ]);

                $fixed++;
                Log::info("Fixed: {$oldName} -> {$newName}");
            } catch (\Exception $e) {
                $errors[] = "ID {$wo->id}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Fixed ' . $fixed . ' files',
            'fixed' => $fixed,
            'errors' => $errors,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
});

// ============= ROUTE REPORT CENTER =============
Route::middleware(['auth'])->prefix('report')->name('report.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/data', [ReportController::class, 'data'])->name('data');
    Route::get('/export', [ReportController::class, 'export'])->name('export');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('/reports', '/report');
    Route::redirect('/reports-analytics', '/report');
});

// ============= ROUTE AUDIT LOGS =============
Route::middleware(['auth', 'role:admin,approval'])->group(function () {
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/data', [\App\Http\Controllers\AuditLogController::class, 'data'])->name('audit-logs.data');
    Route::get('/audit-logs/export', [\App\Http\Controllers\AuditLogController::class, 'export'])->name('audit-logs.export');
});

require __DIR__ . '/auth.php';
