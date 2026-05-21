<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\BangunanController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\MesinController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TransaksiController;
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

// ============= DASHBOARD =============
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/admin', [DashboardController::class, 'index'])
        ->middleware('role:admin,approval')
        ->name('admin.dashboard');
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
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
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
        Route::get('/bangunan/data', [MasterController::class, 'getBangunanData'])->name('bangunan.data');
    });
});

// ============= ROUTE STOCK SPAREPART =============
Route::middleware(['auth'])->prefix('stock')->name('stock.')->group(function () {
    Route::get('/', [StockController::class, 'index'])->name('index');
    Route::get('/data', [StockController::class, 'getStockData'])->name('data');
    Route::get('/detail/{id}', [StockController::class, 'getDetail'])->name('detail');
    Route::get('/movement', [StockController::class, 'getMovement'])->name('movement');
    Route::post('/opname', [StockController::class, 'saveOpname'])->name('opname');
    Route::get('/export', [StockController::class, 'export'])->name('export');
    Route::get('/by-location/{location?}', [StockController::class, 'getByLocation'])->name('by-location');
});

// ============= ROUTE WORK ORDER =============
Route::middleware(['auth'])->prefix('workorder')->name('workorder.')->group(function () {
    Route::get('/', [WorkOrderController::class, 'index'])->name('index');
    Route::get('/data', [WorkOrderController::class, 'getData'])->name('data');
    Route::post('/store', [WorkOrderController::class, 'store'])->name('store');
    Route::get('/download/{id}', [WorkOrderController::class, 'download'])->whereNumber('id')->name('download');
    Route::delete('/delete/{id}', [WorkOrderController::class, 'destroy'])->whereNumber('id')->name('delete');
    Route::post('/submit/{id}', [WorkOrderController::class, 'submit'])->whereNumber('id')->name('submit');
    Route::post('/approve/{id}', [WorkOrderController::class, 'approve'])->whereNumber('id')->name('approve');
    Route::post('/reject/{id}', [WorkOrderController::class, 'reject'])->whereNumber('id')->name('reject');
    Route::get('/progress-data', [WorkOrderController::class, 'progressData'])->name('progress.data');
    Route::post('/progress/{id}', [WorkOrderController::class, 'updateProgress'])->whereNumber('id')->name('progress.update');
    Route::get('/timeline/{id}', [WorkOrderController::class, 'getTimeline'])->whereNumber('id')->name('timeline');
});

// ============= ROUTE TRANSAKSI =============
Route::middleware(['auth'])->group(function () {
    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    Route::post('/transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
    Route::get('/transaksi/generate-nomor', [TransaksiController::class, 'generateNomor'])->name('transaksi.generate-nomor');
    Route::get('/transaksi/{id}', [TransaksiController::class, 'show'])->whereNumber('id')->name('transaksi.show');
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

// ============= ROUTE UNTUK APPROVAL =============
Route::middleware(['auth', 'role:approval,admin'])
    ->prefix('approval')
    ->name('approval.')
    ->group(function () {
        Route::get('/pending', [ApprovalController::class, 'index'])->name('index');
        Route::post('/bulk-approve', [ApprovalController::class, 'bulkApprove'])->name('bulk');
        Route::get('/statistics', [ApprovalController::class, 'statistics'])->name('stats');
        Route::post('/{id}/approve', [ApprovalController::class, 'approve'])->whereNumber('id')->name('approve');
        Route::post('/{id}/reject', [ApprovalController::class, 'reject'])->whereNumber('id')->name('reject');
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
        Route::post('/adjust/{id}', [Warehouse2StockController::class, 'adjust'])->whereNumber('id')->name('adjust');
        Route::get('/{id}', [Warehouse2StockController::class, 'show'])->whereNumber('id')->name('show');
    });

    Route::prefix('receiving')->name('receiving.')->group(function () {
        Route::get('/', [Warehouse2ReceivingController::class, 'index'])->name('index');
        Route::get('/data', [Warehouse2ReceivingController::class, 'getData'])->name('data');
        Route::get('/create', [Warehouse2ReceivingController::class, 'create'])->name('create');
        Route::post('/', [Warehouse2ReceivingController::class, 'store'])->name('store');
        Route::get('/print/{id}', [Warehouse2ReceivingController::class, 'print'])->whereNumber('id')->name('print');
        Route::get('/download-pdf/{id}', [Warehouse2ReceivingController::class, 'downloadPdf'])->whereNumber('id')->name('download-pdf');
        Route::get('/{id}', [Warehouse2ReceivingController::class, 'show'])->whereNumber('id')->name('show');
    });

    Route::prefix('issuing')->name('issuing.')->group(function () {
        Route::get('/', [Warehouse2IssuingController::class, 'index'])->name('index');
        Route::get('/data', [Warehouse2IssuingController::class, 'getData'])->name('data');
        Route::get('/create', [Warehouse2IssuingController::class, 'create'])->name('create');
        Route::post('/', [Warehouse2IssuingController::class, 'store'])->name('store');
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
