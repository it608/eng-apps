<?php

namespace App\Http\Controllers\Warehouse2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
    /**
     * Halaman stock Warehouse2.
     */
    public function index()
    {
        try {
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
     * Data stock untuk AJAX/DataTables.
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
                    'errors' => $validator->errors(),
                    'data' => [],
                ], 422);
            }

            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 20);
            $offset = ($page - 1) * $perPage;

            $query = $this->buildStockQuery($request);
            $total = (clone $query)->count();

            $rows = $query
                ->orderBy('i.code')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            $data = $rows->map(function ($row) {
                return $this->formatStockRow($row);
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'summary' => $this->buildSummary($request),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
                    'from' => $total > 0 ? $offset + 1 : 0,
                    'to' => min($offset + $perPage, $total),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Warehouse2 Stock data error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data stok: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Detail stock berdasarkan ID row warehouse2_stock.
     */
    public function show($id)
    {
        try {
            $stock = DB::table('warehouse2_stock as s')
                ->join('warehouse2_items as i', 's.item_id', '=', 'i.id')
                ->select(
                    's.id',
                    's.item_id',
                    's.quantity',
                    's.location',
                    's.last_updated',
                    's.created_at',
                    's.updated_at',
                    'i.code',
                    'i.name',
                    'i.category',
                    'i.unit',
                    'i.min_stock',
                    'i.max_stock'
                )
                ->where('s.id', $id)
                ->first();

            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data stok tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatStockRow($stock),
            ]);
        } catch (\Exception $e) {
            Log::error('Warehouse2 Stock show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail stok: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export stock ke CSV.
     */
    public function export(Request $request)
    {
        try {
            $filename = 'warehouse2_stock_' . date('Ymd_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($request) {
                $file = fopen('php://output', 'w');

                // BOM UTF-8 supaya aman dibuka di Excel.
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                fputcsv($file, [
                    'Kode Item',
                    'Nama Item',
                    'Kategori',
                    'Satuan',
                    'Lokasi',
                    'Qty',
                    'Min Stock',
                    'Max Stock',
                    'Status',
                    'Last Updated',
                ]);

                $this->buildStockQuery($request)
                    ->orderBy('i.code')
                    ->chunk(500, function ($rows) use ($file) {
                        foreach ($rows as $row) {
                            $formatted = $this->formatStockRow($row);

                            fputcsv($file, [
                                $formatted['code'],
                                $formatted['name'],
                                $formatted['category'],
                                $formatted['unit'],
                                $formatted['location'],
                                $formatted['stock'],
                                $formatted['min_stock'],
                                $formatted['max_stock'],
                                strtoupper($formatted['status']),
                                $formatted['last_updated'],
                            ]);
                        }
                    });

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Warehouse2 Stock export error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal export stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Summary stock Warehouse2.
     */
    public function summary(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->buildSummary($request),
            ]);
        } catch (\Exception $e) {
            Log::error('Warehouse2 Stock summary error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil summary stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update quantity stock manual.
     */
    public function adjust(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data adjustment tidak valid',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $stock = DB::table('warehouse2_stock')->where('id', $id)->first();

            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data stok tidak ditemukan',
                ], 404);
            }

            DB::table('warehouse2_stock')
                ->where('id', $id)
                ->update([
                    'quantity' => $request->quantity,
                    'last_updated' => now(),
                    'updated_at' => now(),
                ]);

            Log::info('Warehouse2 stock adjusted', [
                'stock_id' => $id,
                'item_id' => $stock->item_id,
                'location' => $stock->location,
                'old_quantity' => $stock->quantity,
                'new_quantity' => $request->quantity,
                'notes' => $request->notes,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock berhasil disesuaikan',
                'data' => [
                    'id' => (int) $id,
                    'old_quantity' => (float) $stock->quantity,
                    'new_quantity' => (float) $request->quantity,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Warehouse2 Stock adjust error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyesuaikan stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Movement item dari receiving dan issuing.
     * ID yang diterima bisa stock_id atau item_id.
     */
    public function movement($id)
    {
        try {
            $stock = DB::table('warehouse2_stock')->where('id', $id)->first();
            $itemId = $stock ? $stock->item_id : $id;

            $item = DB::table('warehouse2_items')->where('id', $itemId)->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan',
                    'data' => [],
                ], 404);
            }

            $movements = collect();

            if (Schema::hasTable('warehouse2_receiving') && Schema::hasTable('warehouse2_receiving_detail')) {
                $receivings = DB::table('warehouse2_receiving_detail as d')
                    ->join('warehouse2_receiving as h', 'd.receiving_id', '=', 'h.id')
                    ->where('d.item_id', $itemId)
                    ->select(
                        'h.receipt_date as tanggal',
                        'h.receipt_number as nomor',
                        DB::raw("'IN' as tipe"),
                        'd.quantity',
                        'h.supplier as keterangan',
                        'd.created_at'
                    )
                    ->get();

                $movements = $movements->merge($receivings);
            }

            if (Schema::hasTable('warehouse2_issuing') && Schema::hasTable('warehouse2_issuing_detail')) {
                $issuings = DB::table('warehouse2_issuing_detail as d')
                    ->join('warehouse2_issuing as h', 'd.issuing_id', '=', 'h.id')
                    ->where('d.item_id', $itemId)
                    ->select(
                        'h.issue_date as tanggal',
                        'h.issue_number as nomor',
                        DB::raw("'OUT' as tipe"),
                        'd.quantity',
                        'h.purpose as keterangan',
                        'd.created_at'
                    )
                    ->get();

                $movements = $movements->merge($issuings);
            }

            $data = $movements
                ->sortByDesc('tanggal')
                ->take(100)
                ->values()
                ->map(function ($row) use ($item) {
                    return [
                        'tanggal' => $row->tanggal,
                        'nomor' => $row->nomor,
                        'tipe' => $row->tipe,
                        'tipe_badge' => $row->tipe === 'IN' ? 'success' : 'warning',
                        'quantity' => (float) $row->quantity,
                        'unit' => $item->unit,
                        'keterangan' => $row->keterangan,
                        'created_at' => $row->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $data,
                'item' => [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'unit' => $item->unit,
                ],
                'total' => $data->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Warehouse2 Stock movement error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil movement stock: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Ambil stock berdasarkan item_id untuk API internal.
     */
    public function getByItem($id)
    {
        try {
            $item = DB::table('warehouse2_items')->where('id', $id)->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan',
                ], 404);
            }

            $stocks = DB::table('warehouse2_stock')
                ->where('item_id', $id)
                ->orderBy('location')
                ->get()
                ->map(function ($stock) use ($item) {
                    return [
                        'id' => $stock->id,
                        'item_id' => $stock->item_id,
                        'location' => $stock->location,
                        'quantity' => (float) $stock->quantity,
                        'unit' => $item->unit,
                        'last_updated' => $stock->last_updated,
                        'status' => $this->getStockStatus((float) $stock->quantity, (float) $item->min_stock),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'item' => [
                        'id' => $item->id,
                        'code' => $item->code,
                        'name' => $item->name,
                        'category' => $item->category,
                        'unit' => $item->unit,
                        'min_stock' => (float) $item->min_stock,
                        'max_stock' => (float) $item->max_stock,
                    ],
                    'stocks' => $stocks,
                    'total_stock' => (float) $stocks->sum('quantity'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Warehouse2 Stock getByItem error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil stock item: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function buildStockQuery(Request $request)
    {
        $query = DB::table('warehouse2_stock as s')
            ->join('warehouse2_items as i', 's.item_id', '=', 'i.id')
            ->select(
                's.id',
                's.item_id',
                's.quantity',
                's.location',
                's.last_updated',
                's.created_at',
                's.updated_at',
                'i.code',
                'i.name',
                'i.category',
                'i.unit',
                'i.min_stock',
                'i.max_stock'
            );

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('i.code', 'like', "%{$search}%")
                    ->orWhere('i.name', 'like', "%{$search}%")
                    ->orWhere('i.category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('location') && $request->location !== 'all') {
            $query->where('s.location', $request->location);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'habis') {
                $query->where('s.quantity', '<=', 0);
            } elseif ($request->status === 'menipis') {
                $query->where('s.quantity', '>', 0)
                    ->whereColumn('s.quantity', '<', 'i.min_stock');
            } elseif ($request->status === 'aman') {
                $query->whereColumn('s.quantity', '>=', 'i.min_stock');
            }
        }

        return $query;
    }

    private function buildSummary(Request $request): array
    {
        $query = $this->buildStockQuery($request);
        $rows = $query->get();

        return [
            'total_items' => $rows->count(),
            'total_stock' => (float) $rows->sum('quantity'),
            'low_stock' => $rows->filter(function ($row) {
                return (float) $row->quantity > 0 && (float) $row->quantity < (float) $row->min_stock;
            })->count(),
            'out_of_stock' => $rows->filter(function ($row) {
                return (float) $row->quantity <= 0;
            })->count(),
            'safe_stock' => $rows->filter(function ($row) {
                return (float) $row->quantity >= (float) $row->min_stock;
            })->count(),
        ];
    }

    private function formatStockRow($row): array
    {
        $quantity = (float) $row->quantity;
        $minStock = (float) $row->min_stock;
        $maxStock = (float) $row->max_stock;

        return [
            'id' => $row->id,
            'stock_id' => $row->id,
            'item_id' => $row->item_id,
            'code' => $row->code,
            'name' => $row->name,
            'category' => $row->category,
            'unit' => $row->unit,
            'stock' => $quantity,
            'quantity' => $quantity,
            'min_stock' => $minStock,
            'max_stock' => $maxStock,
            'location' => $row->location,
            'last_updated' => $row->last_updated,
            'status' => $this->getStockStatus($quantity, $minStock),
        ];
    }

    private function getStockStatus(float $quantity, float $minStock): string
    {
        if ($quantity <= 0) {
            return 'habis';
        }

        if ($quantity < $minStock) {
            return 'menipis';
        }

        return 'aman';
    }
}
