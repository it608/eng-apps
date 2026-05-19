<?php

namespace App\Http\Controllers\Warehouse2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
    /**
     * Display a listing of stock items.
     */
    public function index()
    {
        try {
            // Ambil daftar lokasi untuk filter
            $locations = DB::table('warehouse2_stock')
                ->select('location')
                ->distinct()
                ->orderBy('location')
                ->get();

            return view('warehouse2.stock.index', compact('locations'));
            
        } catch (\Exception $e) {
            Log::error('Warehouse2 Stock index error: ' . $e->getMessage());
            return view('warehouse2.stock.index')->with('error', 'Gagal memuat data: ' . $e->getMessage());
        }
    }

    /**
     * Get stock data for DataTables / AJAX.
     */
    public function getData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:100',
                'location' => 'nullable|string|max:50',
                'status' => 'nullable|in:aman,menipis,habis',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 20);
            $offset = ($page - 1) * $perPage;

            // Query utama dengan join ke items
            $query = DB::table('warehouse2_stock as s')
                ->join('warehouse2_items as i', 's.item_id', '=', 'i.id')
                ->select(
                    'i.id',
                    'i.code',
                    'i.name',
                    'i.category',
                    'i.unit',
                    'i.min_stock',
                    'i.max_stock',
                    's.quantity as stock',
                    's.location',
                    's.last_updated'
                );

            // Apply search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('i.code', 'like', "%{$search}%")
                      ->orWhere('i.name', 'like', "%{$search}%")
                      ->orWhere('i.category', 'like', "%{$search}%");
                });
            }

            // Apply location filter
            if ($request->filled('location')) {
                $query->where('s.location', $request->location);
            }

            // Hitung total sebelum pagination
            $total = $query->count();

            // Get data dengan pagination
            $data = $query->orderBy('i.code')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            // Format data dan hitung status
            $formattedData = [];
            foreach ($data as $item) {
                $stock = (float) ($item->stock ?? 0);
                $minStock = (float) ($item->min_stock ?? 0);
                $maxStock = (float) ($item->max_stock ?? 0);
                
                // Tentukan status
                if ($stock <= 0) {
                    $status = 'habis';
                } elseif ($stock < $minStock) {
                    $status = 'menipis';
                } else {
                    $status = 'aman';
                }
                
                $formattedData[] = [
                    'id' => $item->id,
                    'code' => $item->code ?? '-',
                    'name' => $item->name ?? '-',
                    'category' => $item->category ?? '-',
                    'unit' => $item->unit ?? 'PCS',
                    'stock' => $stock,
                    'min_stock' => $minStock,
                    'max_stock' => $maxStock,
                    'location' => $item->location ?? '-',
                    'last_update' => $item->last_updated ?? '-',
                    'status' => $status,
                ];
            }

            // Filter berdasarkan status (jika ada)
            if ($request->filled('status')) {
                $formattedData = array_filter($formattedData, function($item) use ($request) {
                    return $item['status'] === $request->status;
                });
                $formattedData = array_values($formattedData);
                $total = count($formattedData);
            }

            // Summary
            $summary = [
                'total_items' => DB::table('warehouse2_items')->count(),
                'total_stock' => DB::table('warehouse2_stock')->sum('quantity'),
                'low_stock' => $this->getLowStockCount(),
                'out_of_stock' => $this->getOutOfStockCount()
            ];

            return response()->json([
                'success' => true,
                'data' => array_values($formattedData),
                'summary' => $summary,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Warehouse2 Stock data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data stok: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail stock item by ID.
     */
    public function show($id)
    {
        try {
            $item = DB::table('warehouse2_items as i')
                ->leftJoin('warehouse2_stock as s', 'i.id', '=', 's.item_id')
                ->select(
                    'i.*',
                    's.quantity as stock',
                    's.location',
                    's.last_updated'
                )
                ->where('i.id', $id)
                ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan'
                ], 404);
            }

            // Ambil history transaksi
            $receivingHistory = DB::table('warehouse2_receiving_detail as rd')
                ->join('warehouse2_receiving as r', 'rd.receiving_id', '=', 'r.id')
                ->select(
                    'r.receipt_number as document',
                    'r.receipt_date as date',
                    DB::raw("'TERIMA' as type"),
                    'rd.quantity',
                    'rd.unit_price',
                    'rd.total_price',
                    'r.supplier',
                    'r.notes'
                )
                ->where('rd.item_id', $id)
                ->orderBy('r.receipt_date', 'desc')
                ->limit(50)
                ->get();

            $issuingHistory = DB::table('warehouse2_issuing_detail as id')
                ->join('warehouse2_issuing as i', 'id.issuing_id', '=', 'i.id')
                ->select(
                    'i.issue_number as document',
                    'i.issue_date as date',
                    DB::raw("'KELUAR' as type"),
                    'id.quantity',
                    DB::raw("NULL as unit_price"),
                    DB::raw("NULL as total_price"),
                    'i.department',
                    'i.purpose as notes'
                )
                ->where('id.item_id', $id)
                ->orderBy('i.issue_date', 'desc')
                ->limit(50)
                ->get();

            // Gabungkan dan urutkan
            $history = $receivingHistory->concat($issuingHistory)
                ->sortByDesc('date')
                ->values()
                ->take(50);

            return response()->json([
                'success' => true,
                'data' => [
                    'item' => $item,
                    'history' => $history
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Warehouse2 Stock detail error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get low stock count.
     */
    private function getLowStockCount()
    {
        return DB::table('warehouse2_stock as s')
            ->join('warehouse2_items as i', 's.item_id', '=', 'i.id')
            ->where('s.quantity', '>', 0)
            ->where('s.quantity', '<', DB::raw('i.min_stock'))
            ->count();
    }

    /**
     * Get out of stock count.
     */
    private function getOutOfStockCount()
    {
        return DB::table('warehouse2_stock')
            ->where('quantity', '<=', 0)
            ->count();
    }

    /**
     * Export stock data to CSV.
     */
    public function export(Request $request)
    {
        try {
            set_time_limit(300);
            
            $query = DB::table('warehouse2_stock as s')
                ->join('warehouse2_items as i', 's.item_id', '=', 'i.id')
                ->select(
                    'i.code',
                    'i.name',
                    'i.category',
                    'i.unit',
                    'i.min_stock',
                    'i.max_stock',
                    's.quantity as stock',
                    's.location',
                    's.last_updated'
                );

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('i.code', 'like', "%{$search}%")
                      ->orWhere('i.name', 'like', "%{$search}%");
                });
            }

            if ($request->filled('location')) {
                $query->where('s.location', $request->location);
            }

            $data = $query->orderBy('i.code')->get();

            $filename = 'warehouse2_stock_' . date('Ymd_His') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"$filename\""
            ];

            $callback = function() use ($data) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8
                
                fputcsv($file, [
                    'Kode', 'Nama Barang', 'Kategori', 'Satuan',
                    'Stok', 'Min Stok', 'Max Stok', 'Lokasi',
                    'Status', 'Terakhir Update'
                ]);
                
                foreach ($data as $item) {
                    $stock = (float) ($item->stock ?? 0);
                    $minStock = (float) ($item->min_stock ?? 0);
                    
                    if ($stock <= 0) {
                        $status = 'HABIS';
                    } elseif ($stock < $minStock) {
                        $status = 'MENIPIS';
                    } else {
                        $status = 'AMAN';
                    }
                    
                    fputcsv($file, [
                        $item->code ?? '-',
                        $item->name ?? '-',
                        $item->category ?? '-',
                        $item->unit ?? 'PCS',
                        $stock,
                        $minStock,
                        $item->max_stock ?? 0,
                        $item->location ?? '-',
                        $status,
                        $item->last_updated ?? '-'
                    ]);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Warehouse2 Stock export error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal export data: ' . $e->getMessage()
            ], 500);
        }
    }
}