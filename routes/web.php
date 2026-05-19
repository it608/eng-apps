<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\MesinController;
use App\Http\Controllers\BangunanController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\WorkOrderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('landing');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

// ============= ROUTE ADMIN =============
Route::get('/admin', function () {
    return view('admin.dashboard');
})->middleware(['auth', 'role:admin,approval']);

Route::middleware(['auth', 'role:admin,approval'])
    ->prefix('admin')
    ->group(function () {
        // User Management (hanya admin)
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
            Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        });

        // Update Master Mesin (via AJAX modal)
        Route::put('/mesin/{id}', [MasterController::class, 'updateMesin'])
            ->name('admin.mesin.update');
        
        // Update Master Bangunan (via AJAX modal)
        Route::put('/bangunan/{id}', [MasterController::class, 'updateBangunan'])
            ->name('admin.bangunan.update');
    });

// ============= ROUTE MASTER DATA =============
Route::middleware(['auth'])->group(function () {
    Route::get('/master', [MasterController::class, 'index'])
        ->name('master.index');
    
    // API routes untuk master data (AJAX)
    Route::prefix('master')->name('master.')->group(function () {
        Route::get('/sparepart/data', [MasterController::class, 'getSparepartData'])
            ->name('sparepart.data');
        Route::get('/mesin/data', [MasterController::class, 'getMesinData'])
            ->name('mesin.data');
        Route::get('/bangunan/data', [MasterController::class, 'getBangunanData'])
            ->name('bangunan.data');
    });        
});

// ============= ROUTE STOCK SPAREPART =============
Route::middleware(['auth'])->prefix('stock')->name('stock.')->group(function () {
    Route::get('/', [StockController::class, 'index'])->name('index');
    Route::get('/data', [StockController::class, 'getStockData'])->name('data');
    Route::get('/detail/{id}', [StockController::class, 'detail'])->name('detail');
    Route::get('/movement', [StockController::class, 'movement'])->name('movement');
    Route::post('/opname', [StockController::class, 'opname'])->name('opname');
    Route::get('/export', [StockController::class, 'export'])->name('export');
    Route::get('/by-location/{location?}', [StockController::class, 'byLocation'])->name('by-location');
});

// ============= ROUTE WORK ORDER =============
Route::middleware(['auth'])->prefix('workorder')->name('workorder.')->group(function () {
    // Basic CRUD
    Route::get('/', [WorkOrderController::class, 'index'])->name('index');
    Route::get('/data', [WorkOrderController::class, 'getData'])->name('data');
    Route::post('/store', [WorkOrderController::class, 'store'])->name('store');
    Route::get('/download/{id}', [WorkOrderController::class, 'download'])->name('download');
    Route::delete('/delete/{id}', [WorkOrderController::class, 'destroy'])->name('delete');
    
    // Approval Routes
    Route::post('/submit/{id}', [WorkOrderController::class, 'submit'])->name('submit');
    Route::post('/approve/{id}', [WorkOrderController::class, 'approve'])->name('approve');
    Route::post('/reject/{id}', [WorkOrderController::class, 'reject'])->name('reject');
    
    // Progress Routes
    Route::get('/progress-data', [WorkOrderController::class, 'progressData'])->name('progress.data');
    Route::post('/progress/{id}', [WorkOrderController::class, 'updateProgress'])->name('progress.update');
    Route::get('/timeline/{id}', [WorkOrderController::class, 'getTimeline'])->name('timeline');
});

// ============= ROUTE TRANSAKSI =============
Route::middleware(['auth'])->group(function () {
    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    Route::post('/transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
    Route::get('/transaksi/{id}', [TransaksiController::class, 'show'])->name('transaksi.show');
    Route::get('/transaksi/generate-nomor', [TransaksiController::class, 'generateNomor'])->name('transaksi.generate-nomor');
});

// ============= ROUTE UNTUK API =============
Route::prefix('api')->middleware(['auth'])->group(function () {
    Route::get('/barang/search', [BarangController::class, 'search'])->name('api.barang.search');
    Route::get('/mesin/list', [MesinController::class, 'list'])->name('api.mesin.list');
    Route::get('/mesin/search', [MesinController::class, 'search'])->name('api.mesin.search');
    Route::get('/mesin/{id}', [MesinController::class, 'show'])->name('api.mesin.show');
    Route::get('/mesin/{id}/spare-parts', [MesinController::class, 'spareParts'])->name('api.mesin.spare-parts');
    Route::get('/bangunan/list', [BangunanController::class, 'list'])->name('api.bangunan.list');
    Route::get('/bangunan/search', [BangunanController::class, 'search'])->name('api.bangunan.search');
    Route::get('/bangunan/{id}', [BangunanController::class, 'show'])->name('api.bangunan.show');
    Route::get('/stock/summary', [StockController::class, 'summary'])->name('api.stock.summary');
    Route::get('/stock/history/{id}', [StockController::class, 'history'])->name('api.stock.history');
});

// ============= ROUTE UNTUK SEARCH =============
Route::get('/cari-barang', [BarangController::class, 'search'])
    ->name('barang.search')
    ->middleware('auth');

// ============= ROUTE UNTUK APPROVAL =============
Route::middleware(['auth', 'role:approval,admin'])
    ->prefix('approval')
    ->name('approval.')
    ->group(function () {
        Route::get('/pending', [ApprovalController::class, 'index'])->name('index');
        Route::post('/{id}/approve', [ApprovalController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [ApprovalController::class, 'reject'])->name('reject');
        Route::post('/bulk-approve', [ApprovalController::class, 'bulkApprove'])->name('bulk');
        Route::get('/statistics', [ApprovalController::class, 'statistics'])->name('stats');
    });

// ============= ROUTE WAREHOUSE 2 (LENGKAP) =============
Route::middleware(['auth'])->prefix('warehouse2')->name('warehouse2.')->group(function () {
    
    // ===== STOCK MANAGEMENT =====
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/', [App\Http\Controllers\Warehouse2\StockController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\Warehouse2\StockController::class, 'getData'])->name('data');
        Route::get('/export', [App\Http\Controllers\Warehouse2\StockController::class, 'export'])->name('export');
        Route::get('/{id}', [App\Http\Controllers\Warehouse2\StockController::class, 'show'])->name('show');
        
        // Tambahan fitur stock
        Route::post('/adjust/{id}', [App\Http\Controllers\Warehouse2\StockController::class, 'adjust'])->name('adjust');
        Route::get('/movement/{id}', [App\Http\Controllers\Warehouse2\StockController::class, 'movement'])->name('movement');
        Route::get('/summary', [App\Http\Controllers\Warehouse2\StockController::class, 'summary'])->name('summary');
    });
    
    // ===== RECEIVING (TERIMA BARANG) =====
    Route::prefix('receiving')->name('receiving.')->group(function () {
        Route::get('/', [App\Http\Controllers\Warehouse2\ReceivingController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\Warehouse2\ReceivingController::class, 'getData'])->name('data');
        Route::get('/create', [App\Http\Controllers\Warehouse2\ReceivingController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Warehouse2\ReceivingController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Warehouse2\ReceivingController::class, 'show'])->name('show');
        
        // Route untuk cetak BTB
        Route::get('/print/{id}', [App\Http\Controllers\Warehouse2\ReceivingController::class, 'print'])->name('print');
        Route::get('/download-pdf/{id}', [App\Http\Controllers\Warehouse2\ReceivingController::class, 'downloadPdf'])->name('download-pdf');
    });
    
    // ===== ISSUING (KELUAR BARANG) DENGAN ROUTE PRINT =====
    Route::prefix('issuing')->name('issuing.')->group(function () {
        Route::get('/', [App\Http\Controllers\Warehouse2\IssuingController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\Warehouse2\IssuingController::class, 'getData'])->name('data');
        Route::get('/create', [App\Http\Controllers\Warehouse2\IssuingController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Warehouse2\IssuingController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Warehouse2\IssuingController::class, 'show'])->name('show');
        
        // ===== ROUTE PRINT BKB (BUKTI KELUAR BARANG) =====
        Route::get('/print/{id}', [App\Http\Controllers\Warehouse2\IssuingController::class, 'print'])->name('print');
        Route::get('/download-pdf/{id}', [App\Http\Controllers\Warehouse2\IssuingController::class, 'downloadPdf'])->name('download-pdf');
    });
    
    // ===== API INTERNAL WAREHOUSE 2 =====
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/items/search', [App\Http\Controllers\Warehouse2\ItemController::class, 'search'])->name('items.search');
        Route::get('/items/{id}', [App\Http\Controllers\Warehouse2\ItemController::class, 'show'])->name('items.show');
        Route::get('/stock/by-item/{id}', [App\Http\Controllers\Warehouse2\StockController::class, 'getByItem'])->name('stock.by-item');
        Route::get('/dashboard/stats', [App\Http\Controllers\Warehouse2\DashboardController::class, 'stats'])->name('dashboard.stats');
    });
});

// ============= ROUTE DASHBOARD WAREHOUSE 2 =============
Route::middleware(['auth'])->prefix('warehouse2')->name('warehouse2.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Warehouse2\DashboardController::class, 'index'])->name('dashboard');
});

// ============= ROUTE FIX FILENAME WORK ORDER =============
Route::get('/fix-filenames', function() {
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
                    
                    DB::table('trWorkOrder')
                        ->where('id', $wo->id)
                        ->update([
                            'file_name' => $newName,
                            'file_path' => $newPath
                        ]);
                    
                    $fixed++;
                    
                    Log::info("Fixed: {$oldName} -> {$newName}");
                } else {
                    DB::table('trWorkOrder')
                        ->where('id', $wo->id)
                        ->update([
                            'file_name' => $newName,
                            'file_path' => $newPath
                        ]);
                    
                    $fixed++;
                    
                    Log::warning("File not found, but DB updated: {$oldPath}");
                }
                
            } catch (\Exception $e) {
                $errors[] = "ID {$wo->id}: " . $e->getMessage();
            }
        }
        
        $message = "? Fixed {$fixed} files";
        if (!empty($errors)) {
            $message .= "\n\n? Errors:\n" . implode("\n", $errors);
        }
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'fixed' => $fixed,
            'errors' => $errors
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

require __DIR__.'/auth.php';