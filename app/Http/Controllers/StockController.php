<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
    /**
     * Display a listing of the stock sparepart.
     */
    public function index()
    {
        try {
            // Cache lokasi selama 1 jam karena jarang berubah
            $locations = Cache::remember('stock_locations', 3600, function() {
                return DB::connection('pgsql2')->table('tb_skb008_2dmseg')
                    ->select('lsloc as kode_gudang')
                    ->distinct()
                    ->orderBy('lsloc')
                    ->get();
            });
            
            // Nonaktifkan category filter
            $categories = [];
            
            return view('user.stock', compact('locations', 'categories'));
            
        } catch (\Exception $e) {
            Log::error('Stock index error: ' . $e->getMessage());
            return view('user.stock')->with('error', 'Gagal memuat data filter: ' . $e->getMessage());
        }
    }

    /**
     * Get stock data for DataTables / AJAX.
     * Menggunakan query kompleks dengan pagination di SQL
     */
    public function getStockData(Request $request)
    {
        try {
            $startTime = microtime(true);
            
            // Validasi input
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:100',
                'location' => 'nullable|string|max:10',
                'status' => 'nullable|in:aman,menipis,habis',
                'filter_code' => 'nullable|string|max:50',
                'filter_name' => 'nullable|string|max:50',
                'filter_unit' => 'nullable|string|max:10',
                'filter_status' => 'nullable|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Pagination
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 20);
            $offset = ($page - 1) * $perPage;

            // Build query dengan WHERE dinamis - VERSI OPTIMASI DENGAN CTE
            $sql = $this->buildOptimizedStockQuery($request);
            
            // Hitung total data - gunakan query yang lebih ringan
            $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as count_query";
            $totalResult = DB::connection('pgsql2')->selectOne($countSql);
            $total = $totalResult ? (int) $totalResult->total : 0;

            // Tambahkan ORDER BY, LIMIT dan OFFSET
            $sql .= " ORDER BY code ASC LIMIT " . $perPage . " OFFSET " . $offset;

            // JALANKAN QUERY
            $data = DB::connection('pgsql2')->select($sql);

            // FORMAT DATA
            $formattedData = [];
            foreach ($data as $item) {
                $stock = (float) ($item->end_qty ?? 0);
                $minStock = 5;
                $maxStock = 20;
                
                // Tentukan status
                if ($stock <= 0) {
                    $status = 'habis';
                } elseif ($stock < $minStock) {
                    $status = 'menipis';
                } else {
                    $status = 'aman';
                }
                
                $formattedData[] = [
                    'code' => $this->cleanUtf8($item->code ?? '-'),
                    'name' => $this->cleanUtf8($item->item_name ?? '-'),
                    'unit' => $this->cleanUtf8($item->uom ?? 'PCS'),
                    'stock' => $stock,
                    'min_stock' => $minStock,
                    'max_stock' => $maxStock,
                    'location' => $this->cleanUtf8($item->lsloc ?? '-'),
                    'avg_price' => (float) ($item->avg_price ?? 0),
                    'total_value' => (float) ($item->end_amt ?? 0),
                    'last_update' => $item->last_use_date ?? $item->last_pur_date ?? '-',
                    'status' => $status,
                    'category' => '-',
                ];
            }

            // Hitung summary - gunakan query terpisah yang lebih ringan
            $summary = $this->getOptimizedSummary($request);

            $executionTime = round((microtime(true) - $startTime) * 1000);
            Log::info('Stock data loaded', ['time_ms' => $executionTime, 'page' => $page]);

            return response()->json([
                'success' => true,
                'data' => $formattedData,
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
            Log::error('Stock data error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data stok: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Build stock query dengan CTE (Common Table Expressions) untuk optimasi
     * dan perhitungan avg_price yang benar
     */
    private function buildOptimizedStockQuery(Request $request)
    {
        $search = $request->get('search', '');
        $location = $request->get('location', '');
        $filterCode = $request->get('filter_code', '');
        $filterName = $request->get('filter_name', '');
        $filterUnit = $request->get('filter_unit', '');
        
        // Escape string untuk mencegah SQL injection
        $search = addslashes($search);
        $location = addslashes($location);
        $filterCode = addslashes($filterCode);
        $filterName = addslashes($filterName);
        $filterUnit = addslashes($filterUnit);
        
        // Gunakan CTE untuk menghindari pengulangan subquery yang sama
        $sql = "
            WITH 
            -- Data transaksi periode
            transaksi_periode AS (
                SELECT 
                    matnr,
                    lsloc,
                    lgnum,
                    bwart,
                    menge,
                    wrbtr,
                    cpudt,
                    saknr,
                    lvorm,
                    ypotp,
                    peinh
                FROM PUBLIC.tb_skb008_2dmseg
                WHERE werks = 1 
                    AND cpudt BETWEEN DATE'2026-01-01' AND CURRENT_DATE
            ),
            -- Stok awal
            stok_awal AS (
                SELECT 
                    matnr,
                    lgpla as lsloc,
                    SUBSTRING(lgpla FROM 1 FOR 3) as lgnum,
                    menge,
                    dmbtr
                FROM PUBLIC.tb_skb111_1mbgni
                WHERE werks = 1 
                    AND mjahr = 2026
                    AND lfmon = 1
                    AND ypotp = 'YPO2'
            ),
            -- Aggregasi per jenis transaksi
            pur_agg AS (
                SELECT 
                    matnr, 
                    lsloc, 
                    SUM(menge) as pur_qty, 
                    SUM(wrbtr) as pur_amt, 
                    MAX(cpudt) as last_pur_date,
                    AVG(peinh) as avg_price_pur
                FROM transaksi_periode 
                WHERE bwart = '101' 
                GROUP BY matnr, lsloc
            ),
            ret_agg AS (
                SELECT 
                    matnr, 
                    lsloc, 
                    SUM(menge) as ret_qty, 
                    SUM(wrbtr) as ret_amt
                FROM transaksi_periode 
                WHERE bwart = '921' 
                GROUP BY matnr, lsloc
            ),
            kpl_agg AS (
                SELECT 
                    matnr, 
                    lsloc, 
                    SUM(menge) as kpl_qty, 
                    SUM(wrbtr) as kpl_amt
                FROM transaksi_periode 
                WHERE bwart = '931' 
                GROUP BY matnr, lsloc
            ),
            use_agg AS (
                SELECT 
                    matnr, 
                    lsloc, 
                    SUM(menge) as use_qty, 
                    SUM(wrbtr) as use_amt, 
                    MAX(cpudt) as last_use_date
                FROM transaksi_periode 
                WHERE bwart = '201' AND saknr <> 7755 
                GROUP BY matnr, lsloc
            ),
            nrb_agg AS (
                SELECT 
                    matnr, 
                    lsloc, 
                    SUM(menge) as nrb_qty, 
                    SUM(wrbtr) as nrb_amt
                FROM transaksi_periode 
                WHERE bwart = '122' 
                GROUP BY matnr, lsloc
            ),
            kmn_agg AS (
                SELECT 
                    matnr, 
                    lsloc, 
                    SUM(menge) as kmn_qty, 
                    SUM(wrbtr) as kmn_amt
                FROM transaksi_periode 
                WHERE bwart = '941' 
                GROUP BY matnr, lsloc
            ),
            tkl_agg AS (
                SELECT 
                    matnr, 
                    lsloc, 
                    SUM(menge) as tkl_qty, 
                    SUM(wrbtr) as tkl_amt
                FROM transaksi_periode 
                WHERE bwart = '981' 
                GROUP BY matnr, lsloc
            ),
            tms_agg AS (
                SELECT 
                    matnr, 
                    lsloc, 
                    SUM(menge) as tms_qty, 
                    SUM(wrbtr) as tms_amt
                FROM transaksi_periode 
                WHERE bwart = '971' 
                GROUP BY matnr, lsloc
            ),
            los_agg AS (
                SELECT 
                    matnr, 
                    lsloc, 
                    SUM(menge) as los_qty, 
                    SUM(wrbtr) as los_amt
                FROM transaksi_periode 
                WHERE bwart = '201' AND lvorm = 'U' AND saknr = 7755 
                GROUP BY matnr, lsloc
            ),
            -- Semua kombinasi item dan lokasi
            all_items AS (
                SELECT DISTINCT 
                    t2.id_items,
                    t2.itemno,
                    t2.code,
                    t2.item_name,
                    t2.meins,
                    COALESCE(tp.lgnum, sa.lgnum) as lgnum,
                    COALESCE(tp.lsloc, sa.lsloc) as lsloc
                FROM (
                    SELECT matnr, lgnum, lsloc FROM transaksi_periode
                    UNION
                    SELECT matnr, lgnum, lsloc FROM stok_awal
                ) lok
                JOIN PUBLIC.tb_skb080_1mmara t2 ON lok.matnr = t2.id_items
                LEFT JOIN transaksi_periode tp ON tp.matnr = t2.id_items AND tp.lsloc = lok.lsloc
                LEFT JOIN stok_awal sa ON sa.matnr = t2.id_items AND sa.lsloc = lok.lsloc
                WHERE t2.mtart = 'YSPR'
            )
            SELECT
                ai.itemno,
                ai.code,
                ai.item_name,
                ai.meins AS uom,
                ai.lsloc,
                ai.lgnum,
                COALESCE(sa.menge, 0) AS bgn_qty,
                COALESCE(sa.dmbtr, 0) AS bgn_amt,
                COALESCE(pur.pur_qty, 0) AS pur_qty,
                COALESCE(pur.pur_amt, 0) AS pur_amt,
                COALESCE(ret.ret_qty, 0) AS ret_qty,
                COALESCE(kpl.kpl_qty, 0) AS kpl_qty,
                COALESCE(use.use_qty, 0) AS use_qty,
                COALESCE(nrb.nrb_qty, 0) AS nrb_qty,
                COALESCE(kmn.kmn_qty, 0) AS kmn_qty,
                COALESCE(tkl.tkl_qty, 0) AS tkl_qty,
                COALESCE(tms.tms_qty, 0) AS tms_qty,
                COALESCE(los.los_qty, 0) AS los_qty,
                use.last_use_date,
                pur.last_pur_date,
                -- Perhitungan end_qty
                (COALESCE(sa.menge, 0) + COALESCE(pur.pur_qty, 0) + COALESCE(ret.ret_qty, 0) + COALESCE(kpl.kpl_qty, 0) 
                 - COALESCE(use.use_qty, 0) - COALESCE(nrb.nrb_qty, 0) - COALESCE(kmn.kmn_qty, 0) - COALESCE(tkl.tkl_qty, 0) 
                 + COALESCE(tms.tms_qty, 0) - COALESCE(los.los_qty, 0)) AS end_qty,
                -- Perhitungan end_amt
                (COALESCE(sa.dmbtr, 0) + COALESCE(pur.pur_amt, 0) + COALESCE(ret.ret_amt, 0) + COALESCE(kpl.kpl_amt, 0) 
                 - COALESCE(use.use_amt, 0) - COALESCE(nrb.nrb_amt, 0) - COALESCE(kmn.kmn_amt, 0) - COALESCE(tkl.tkl_amt, 0) 
                 + COALESCE(tms.tms_amt, 0) - COALESCE(los.los_amt, 0)) AS end_amt,
                -- Perhitungan avg_price
                CASE
                    WHEN COALESCE(pur.pur_qty, 0) > 0 THEN ROUND(COALESCE(pur.pur_amt, 0) / COALESCE(pur.pur_qty, 1), 2)
                    WHEN (COALESCE(sa.menge, 0) + COALESCE(pur.pur_qty, 0) + COALESCE(ret.ret_qty, 0) + COALESCE(kpl.kpl_qty, 0) 
                         - COALESCE(use.use_qty, 0) - COALESCE(nrb.nrb_qty, 0) - COALESCE(kmn.kmn_qty, 0) - COALESCE(tkl.tkl_qty, 0) 
                         + COALESCE(tms.tms_qty, 0) - COALESCE(los.los_qty, 0)) > 0 
                         THEN ROUND((COALESCE(sa.dmbtr, 0) + COALESCE(pur.pur_amt, 0) + COALESCE(ret.ret_amt, 0) + COALESCE(kpl.kpl_amt, 0) 
                                   - COALESCE(use.use_amt, 0) - COALESCE(nrb.nrb_amt, 0) - COALESCE(kmn.kmn_amt, 0) - COALESCE(tkl.tkl_amt, 0) 
                                   + COALESCE(tms.tms_amt, 0) - COALESCE(los.los_amt, 0)) / 
                                  (COALESCE(sa.menge, 0) + COALESCE(pur.pur_qty, 0) + COALESCE(ret.ret_qty, 0) + COALESCE(kpl.kpl_qty, 0) 
                                   - COALESCE(use.use_qty, 0) - COALESCE(nrb.nrb_qty, 0) - COALESCE(kmn.kmn_qty, 0) - COALESCE(tkl.tkl_qty, 0) 
                                   + COALESCE(tms.tms_qty, 0) - COALESCE(los.los_qty, 0)), 2)
                    WHEN COALESCE(sa.menge, 0) > 0 THEN ROUND(COALESCE(sa.dmbtr, 0) / COALESCE(sa.menge, 1), 2)
                    ELSE 0
                END AS avg_price,
                -- Perhitungan total_usage_value
                CASE
                    WHEN COALESCE(use.use_qty, 0) > 0 
                    AND (COALESCE(pur.pur_qty, 0) > 0 OR 
                         (COALESCE(sa.menge, 0) + COALESCE(pur.pur_qty, 0) + COALESCE(ret.ret_qty, 0) + COALESCE(kpl.kpl_qty, 0) 
                          - COALESCE(use.use_qty, 0) - COALESCE(nrb.nrb_qty, 0) - COALESCE(kmn.kmn_qty, 0) - COALESCE(tkl.tkl_qty, 0) 
                          + COALESCE(tms.tms_qty, 0) - COALESCE(los.los_qty, 0)) > 0 OR 
                         COALESCE(sa.menge, 0) > 0) THEN
                        ROUND(
                            (CASE
                                WHEN COALESCE(pur.pur_qty, 0) > 0 THEN COALESCE(pur.pur_amt, 0) / COALESCE(pur.pur_qty, 1)
                                WHEN (COALESCE(sa.menge, 0) + COALESCE(pur.pur_qty, 0) + COALESCE(ret.ret_qty, 0) + COALESCE(kpl.kpl_qty, 0) 
                                     - COALESCE(use.use_qty, 0) - COALESCE(nrb.nrb_qty, 0) - COALESCE(kmn.kmn_qty, 0) - COALESCE(tkl.tkl_qty, 0) 
                                     + COALESCE(tms.tms_qty, 0) - COALESCE(los.los_qty, 0)) > 0 
                                     THEN (COALESCE(sa.dmbtr, 0) + COALESCE(pur.pur_amt, 0) + COALESCE(ret.ret_amt, 0) + COALESCE(kpl.kpl_amt, 0) 
                                           - COALESCE(use.use_amt, 0) - COALESCE(nrb.nrb_amt, 0) - COALESCE(kmn.kmn_amt, 0) - COALESCE(tkl.tkl_amt, 0) 
                                           + COALESCE(tms.tms_amt, 0) - COALESCE(los.los_amt, 0)) / 
                                          (COALESCE(sa.menge, 0) + COALESCE(pur.pur_qty, 0) + COALESCE(ret.ret_qty, 0) + COALESCE(kpl.kpl_qty, 0) 
                                           - COALESCE(use.use_qty, 0) - COALESCE(nrb.nrb_qty, 0) - COALESCE(kmn.kmn_qty, 0) - COALESCE(tkl.tkl_qty, 0) 
                                           + COALESCE(tms.tms_qty, 0) - COALESCE(los.los_qty, 0))
                                WHEN COALESCE(sa.menge, 0) > 0 THEN COALESCE(sa.dmbtr, 0) / COALESCE(sa.menge, 1)
                                ELSE 0
                            END) * COALESCE(use.use_qty, 0), 2)
                    ELSE 0 
                END AS total_usage_value
            FROM all_items ai
            LEFT JOIN stok_awal sa ON sa.matnr = ai.id_items AND sa.lsloc = ai.lsloc
            LEFT JOIN pur_agg pur ON pur.matnr = ai.id_items AND pur.lsloc = ai.lsloc
            LEFT JOIN ret_agg ret ON ret.matnr = ai.id_items AND ret.lsloc = ai.lsloc
            LEFT JOIN kpl_agg kpl ON kpl.matnr = ai.id_items AND kpl.lsloc = ai.lsloc
            LEFT JOIN use_agg use ON use.matnr = ai.id_items AND use.lsloc = ai.lsloc
            LEFT JOIN nrb_agg nrb ON nrb.matnr = ai.id_items AND nrb.lsloc = ai.lsloc
            LEFT JOIN kmn_agg kmn ON kmn.matnr = ai.id_items AND kmn.lsloc = ai.lsloc
            LEFT JOIN tkl_agg tkl ON tkl.matnr = ai.id_items AND tkl.lsloc = ai.lsloc
            LEFT JOIN tms_agg tms ON tms.matnr = ai.id_items AND tms.lsloc = ai.lsloc
            LEFT JOIN los_agg los ON los.matnr = ai.id_items AND los.lsloc = ai.lsloc
            WHERE 1=1
        ";

        // Tambahkan filter search
        if (!empty($search)) {
            $sql .= " AND (ai.code ILIKE '%" . $search . "%' 
                         OR ai.item_name ILIKE '%" . $search . "%' 
                         OR ai.itemno ILIKE '%" . $search . "%')";
        }

        // Tambahkan filter location
        if (!empty($location)) {
            $sql .= " AND ai.lsloc = '" . $location . "'";
        }

        // Tambahkan column filters
        if (!empty($filterCode)) {
            $sql .= " AND ai.code ILIKE '%" . $filterCode . "%'";
        }
        if (!empty($filterName)) {
            $sql .= " AND ai.item_name ILIKE '%" . $filterName . "%'";
        }
        if (!empty($filterUnit)) {
            $sql .= " AND ai.meins = '" . $filterUnit . "'";
        }

        return $sql;
    }

    /**
     * Get optimized summary
     */
    private function getOptimizedSummary(Request $request)
    {
        try {
            // Cache key berdasarkan parameter
            $cacheKey = 'stock_summary_' . md5($request->get('search', '') . $request->get('location', ''));
            
            // Cache selama 5 menit
            return Cache::remember($cacheKey, 300, function() use ($request) {
                // Gunakan query yang sama dengan data utama untuk summary
                $sql = $this->buildOptimizedStockQuery($request);
                
                $summarySql = "
                    SELECT 
                        COUNT(*) as total_items,
                        SUM(end_qty) as total_stock,
                        SUM(end_amt) as total_value,
                        SUM(CASE WHEN end_qty > 0 AND end_qty < 5 THEN 1 ELSE 0 END) as low_stock,
                        SUM(CASE WHEN end_qty <= 0 THEN 1 ELSE 0 END) as out_of_stock
                    FROM (" . $sql . ") summary_query
                ";

                $result = DB::connection('pgsql2')->selectOne($summarySql);

                return [
                    'total_items' => (int) ($result->total_items ?? 0),
                    'total_stock' => (float) ($result->total_stock ?? 0),
                    'total_value' => (float) ($result->total_value ?? 0),
                    'low_stock' => (int) ($result->low_stock ?? 0),
                    'out_of_stock' => (int) ($result->out_of_stock ?? 0)
                ];
            });

        } catch (\Exception $e) {
            Log::error('Stock summary optimized error: ' . $e->getMessage());
            
            return [
                'total_items' => 0,
                'total_stock' => 0,
                'total_value' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0
            ];
        }
    }

    /**
     * Export semua data stock - VERSI STREAMING
     */
    public function export(Request $request)
    {
        try {
            set_time_limit(300);
            
            // Build query tanpa pagination
            $sql = $this->buildOptimizedStockQuery($request);
            $sql .= " ORDER BY code ASC";
            
            Log::info('Starting stock export');
            
            $filename = 'stock_sparepart_' . date('Ymd_His') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            $callback = function() use ($sql) {
                $file = fopen('php://output', 'w');
                
                // Tambahkan BOM untuk UTF-8
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Header CSV
                fputcsv($file, [
                    'Kode', 'Nama Sparepart', 'Satuan', 'Lokasi',
                    'Stok Awal', 'Pembelian', 'Pemakaian', 'Stok Akhir',
                    'Harga Rata-rata', 'Nilai Stok', 'Status', 'Terakhir Update'
                ]);
                
                // Gunakan cursor untuk streaming data
                $perPage = 1000;
                $offset = 0;
                $rowCount = 0;
                
                while (true) {
                    $pageSql = $sql . " LIMIT " . $perPage . " OFFSET " . $offset;
                    $data = DB::connection('pgsql2')->select($pageSql);
                    
                    if (empty($data)) {
                        break;
                    }
                    
                    foreach ($data as $item) {
                        $stock = (float) ($item->end_qty ?? 0);
                        
                        // Tentukan status
                        if ($stock <= 0) {
                            $status = 'HABIS';
                        } elseif ($stock < 5) {
                            $status = 'MENIPIS';
                        } else {
                            $status = 'AMAN';
                        }
                        
                        fputcsv($file, [
                            $this->cleanUtf8($item->code ?? '-'),
                            $this->cleanUtf8($item->item_name ?? '-'),
                            $this->cleanUtf8($item->uom ?? 'PCS'),
                            $this->cleanUtf8($item->lsloc ?? '-'),
                            (float) ($item->bgn_qty ?? 0),
                            (float) ($item->pur_qty ?? 0),
                            (float) ($item->use_qty ?? 0),
                            $stock,
                            (float) ($item->avg_price ?? 0),
                            (float) ($item->end_amt ?? 0),
                            $status,
                            $item->last_use_date ?? $item->last_pur_date ?? '-',
                        ]);
                        
                        $rowCount++;
                    }
                    
                    $offset += $perPage;
                    
                    // Flush output setiap 1000 baris
                    if ($rowCount % 1000 == 0) {
                        ob_flush();
                        flush();
                    }
                }
                
                fclose($file);
                Log::info('Export completed', ['rows' => $rowCount]);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Stock export error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal export data: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== METHOD LAINNYA TETAP SAMA ====================

    /**
     * Get stock movement / mutasi stok
     */
    public function getMovement(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'location' => 'nullable|string|max:10',
                'material_id' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sql = "
                SELECT 
                    b.budat as tanggal,
                    b.mblnr as nomor_transaksi,
                    d.matnr as material_id,
                    e.code as kode_material,
                    e.item_name as nama_material,
                    CASE 
                        WHEN d.bwart = '101' THEN 'PEMBELIAN'
                        WHEN d.bwart = '201' AND d.saknr <> 7755 THEN 'PEMAKAIAN'
                        WHEN d.bwart = '201' AND d.saknr = 7755 THEN 'LOSS'
                        WHEN d.bwart = '921' THEN 'RETUR'
                        WHEN d.bwart = '931' THEN 'ADJUST PLUS'
                        WHEN d.bwart = '941' THEN 'ADJUST MINUS'
                        WHEN d.bwart = '122' THEN 'NRB'
                        WHEN d.bwart = '981' THEN 'TKL'
                        WHEN d.bwart = '971' THEN 'TMS'
                        ELSE d.bwart
                    END as tipe,
                    d.menge as quantity,
                    d.meins as satuan,
                    d.lsloc as lokasi,
                    b.bktxt as keterangan,
                    b.usnam as user
                FROM tb_skb008_1mmseg b
                JOIN tb_skb008_2dmseg d ON d.idmse = b.idmse
                JOIN tb_skb080_1mmara e ON e.id_items = d.matnr
                WHERE b.budat BETWEEN ? AND ?
                AND e.mtart = 'YSPR'
            ";

            $params = [$request->start_date, $request->end_date];

            if ($request->filled('location')) {
                $sql .= " AND d.lsloc = ?";
                $params[] = $request->location;
            }
            
            if ($request->filled('material_id')) {
                $sql .= " AND d.matnr = ?";
                $params[] = $request->material_id;
            }

            $sql .= " ORDER BY b.budat DESC, b.idmse DESC LIMIT 500";

            $movements = DB::connection('pgsql2')->select($sql, $params);

            $formatted = [];
            foreach ($movements as $item) {
                $tipe = $item->tipe;
                $tipeBadge = 'info';
                
                if (strpos($tipe, 'PEMBELIAN') !== false) $tipeBadge = 'success';
                if (strpos($tipe, 'PEMAKAIAN') !== false) $tipeBadge = 'warning';
                if (strpos($tipe, 'LOSS') !== false) $tipeBadge = 'danger';
                if (strpos($tipe, 'RETUR') !== false) $tipeBadge = 'secondary';
                
                $formatted[] = [
                    'tanggal' => date('Y-m-d', strtotime($item->tanggal)),
                    'nomor_transaksi' => $item->nomor_transaksi,
                    'kode_material' => $this->cleanUtf8($item->kode_material),
                    'nama_material' => $this->cleanUtf8($item->nama_material),
                    'tipe' => $tipe,
                    'tipe_badge' => $tipeBadge,
                    'quantity' => (float) $item->quantity,
                    'satuan' => $this->cleanUtf8($item->satuan),
                    'lokasi' => $this->cleanUtf8($item->lokasi),
                    'keterangan' => $this->cleanUtf8($item->keterangan ?? '-'),
                    'user' => $this->cleanUtf8($item->user ?? '-')
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'total' => count($movements)
            ]);

        } catch (\Exception $e) {
            Log::error('Stock movement error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data mutasi: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get detail sparepart with history
     */
    public function getDetail($id)
    {
        try {
            $sparepart = DB::connection('pgsql2')->table('tb_skb080_1mmara')
                ->where('id_items', $id)
                ->orWhere('code', $id)
                ->first();

            if (!$sparepart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sparepart tidak ditemukan'
                ], 404);
            }

            $history = DB::connection('pgsql2')->select("
                SELECT 
                    b.budat as tanggal,
                    b.mblnr as nomor,
                    CASE 
                        WHEN d.bwart = '101' THEN 'PEMBELIAN'
                        WHEN d.bwart = '201' AND d.saknr <> 7755 THEN 'PEMAKAIAN'
                        WHEN d.bwart = '201' AND d.saknr = 7755 THEN 'LOSS'
                        WHEN d.bwart = '921' THEN 'RETUR'
                        WHEN d.bwart = '931' THEN 'ADJUST PLUS'
                        WHEN d.bwart = '941' THEN 'ADJUST MINUS'
                        ELSE d.bwart
                    END as tipe,
                    d.menge as qty,
                    d.meins as satuan,
                    d.lsloc as lokasi,
                    b.bktxt as keterangan,
                    b.usnam as user
                FROM tb_skb008_1mmseg b
                JOIN tb_skb008_2dmseg d ON d.idmse = b.idmse
                WHERE d.matnr = ? AND b.budat >= CURRENT_DATE - INTERVAL '30 days'
                ORDER BY b.budat DESC
                LIMIT 50
            ", [$sparepart->id_items]);

            $formattedHistory = [];
            foreach ($history as $item) {
                $tipeBadge = 'info';
                if (strpos($item->tipe, 'PEMBELIAN') !== false) $tipeBadge = 'success';
                if (strpos($item->tipe, 'PEMAKAIAN') !== false) $tipeBadge = 'warning';
                if (strpos($item->tipe, 'LOSS') !== false) $tipeBadge = 'danger';
                
                $formattedHistory[] = [
                    'tanggal' => date('Y-m-d', strtotime($item->tanggal)),
                    'nomor' => $item->nomor,
                    'tipe' => $item->tipe,
                    'tipe_badge' => $tipeBadge,
                    'qty' => (float) $item->qty,
                    'satuan' => $this->cleanUtf8($item->satuan),
                    'lokasi' => $this->cleanUtf8($item->lokasi),
                    'keterangan' => $this->cleanUtf8($item->keterangan ?? '-'),
                    'user' => $this->cleanUtf8($item->user ?? '-')
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'master' => [
                        'id' => $sparepart->id_items,
                        'code' => $this->cleanUtf8($sparepart->code),
                        'name' => $this->cleanUtf8($sparepart->item_name),
                        'unit' => $this->cleanUtf8($sparepart->meins),
                        'category' => $this->cleanUtf8($sparepart->mtart ?? '-'),
                    ],
                    'history' => $formattedHistory
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Stock detail error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail sparepart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save stock opname
     */
    public function saveOpname(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'location' => 'required|string|max:10',
                'items' => 'required|array',
                'items.*.material_id' => 'required',
                'items.*.system_stock' => 'required|numeric|min:0',
                'items.*.physical_stock' => 'required|numeric|min:0',
                'items.*.difference' => 'required|numeric',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data opname tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $opnameId = DB::table('stock_opname')->insertGetId([
                'location' => $request->location,
                'opname_date' => now(),
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $adjustmentCount = 0;
            
            foreach ($request->items as $item) {
                if ($item['difference'] != 0) {
                    DB::table('stock_opname_detail')->insert([
                        'opname_id' => $opnameId,
                        'material_id' => $item['material_id'],
                        'system_stock' => $item['system_stock'],
                        'physical_stock' => $item['physical_stock'],
                        'difference' => $item['difference'],
                        'location' => $request->location,
                        'notes' => $request->notes,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    $adjustmentCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock opname berhasil disimpan',
                'data' => [
                    'opname_id' => $opnameId,
                    'total_items' => count($request->items),
                    'total_adjustments' => $adjustmentCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Stock opname error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan stock opname: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock by location
     */
    public function getByLocation($location = null)
    {
        try {
            $sql = "
                SELECT 
                    d.lsloc as lokasi,
                    e.code as kode_material,
                    e.item_name as nama_material,
                    SUM(CASE WHEN d.shkzg = 'S' THEN d.menge ELSE 0 END) - 
                    SUM(CASE WHEN d.shkzg = 'H' THEN d.menge ELSE 0 END) as stok,
                    d.meins as satuan,
                    MAX(b.budat) as last_movement
                FROM tb_skb008_2dmseg d
                JOIN tb_skb080_1mmara e ON e.id_items = d.matnr
                LEFT JOIN tb_skb008_1mmseg b ON b.idmse = d.idmse
                WHERE e.mtart = 'YSPR'
                GROUP BY d.lsloc, e.code, e.item_name, d.meins
            ";

            $params = [];

            if ($location && $location != 'all') {
                $sql .= " HAVING d.lsloc = ?";
                $params[] = $location;
            }

            $sql .= " ORDER BY d.lsloc, e.code";

            $data = DB::connection('pgsql2')->select($sql, $params);

            $grouped = [];
            foreach ($data as $item) {
                $lokasi = $item->lokasi;
                if (!isset($grouped[$lokasi])) {
                    $grouped[$lokasi] = [];
                }
                $grouped[$lokasi][] = [
                    'kode_material' => $this->cleanUtf8($item->kode_material),
                    'nama_material' => $this->cleanUtf8($item->nama_material),
                    'stok' => (float) $item->stok,
                    'satuan' => $this->cleanUtf8($item->satuan),
                    'last_movement' => $item->last_movement
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $grouped,
                'total_locations' => count($grouped)
            ]);

        } catch (\Exception $e) {
            Log::error('Stock by location error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data per lokasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoint untuk summary cards
     */
    public function summary(Request $request)
    {
        try {
            $summary = $this->getOptimizedSummary($request);
            
            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('Stock summary API error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoint untuk history sparepart
     */
    public function history($id)
    {
        return $this->getDetail($id);
    }

    /**
     * Clean UTF-8 characters
     */
    private function cleanUtf8($string)
    {
        if (is_null($string)) return '';
        
        $string = (string) $string;
        
        if (mb_check_encoding($string, 'UTF-8')) {
            return $string;
        }
        
        return mb_convert_encoding($string, 'UTF-8', 'auto');
    }
}