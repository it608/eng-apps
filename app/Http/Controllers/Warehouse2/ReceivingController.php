<?php

namespace App\Http\Controllers\Warehouse2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ReceivingController extends Controller
{
    /**
     * Display a listing of receiving transactions.
     */
    public function index()
    {
        try {
            return view('warehouse2.receiving.index');
        } catch (\Exception $e) {
            Log::error('Warehouse2 Receiving index error: ' . $e->getMessage());
            return view('warehouse2.receiving.index')->with('error', 'Gagal memuat data');
        }
    }

    /**
     * Get receiving data for DataTables / AJAX.
     */
    public function getData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:100',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter tidak valid'
                ], 422);
            }

            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 20);
            $offset = ($page - 1) * $perPage;

            $query = DB::table('warehouse2_receiving as r')
                ->select(
                    'r.id',
                    'r.receipt_number',
                    'r.receipt_date',
                    'r.supplier',
                    'r.notes',
                    'r.created_by',
                    'r.created_at',
                    'u.name as created_by_name',
                    DB::raw('(SELECT COUNT(*) FROM warehouse2_receiving_detail WHERE receiving_id = r.id) as total_items'),
                    DB::raw('(SELECT SUM(quantity) FROM warehouse2_receiving_detail WHERE receiving_id = r.id) as total_quantity')
                )
                ->leftJoin('users as u', 'r.created_by', '=', 'u.id');

            // Apply search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('r.receipt_number', 'like', "%{$search}%")
                      ->orWhere('r.supplier', 'like', "%{$search}%");
                });
            }

            // Apply date filter
            if ($request->filled('start_date')) {
                $query->whereDate('r.receipt_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('r.receipt_date', '<=', $request->end_date);
            }

            $total = $query->count();
            $data = $query->orderBy('r.receipt_date', 'desc')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Warehouse2 Receiving data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show form for creating new receiving.
     */
    public function create()
    {
        try {
            // Generate receipt number
            $receiptNumber = $this->generateReceiptNumber();
            
            // Get items with stock information using JOIN
            $items = DB::table('warehouse2_items as i')
                ->leftJoin('warehouse2_stock as s', 'i.id', '=', 's.item_id')
                ->select(
                    'i.id',
                    'i.code',
                    'i.name',
                    'i.unit',
                    DB::raw('COALESCE(s.quantity, 0) as stock')
                )
                ->orderBy('i.code')
                ->get();

            // DEBUG: Cek apakah data items ada
            if ($items->isEmpty()) {
                Log::warning('Warehouse2 items is empty!');
            } else {
                Log::info('Warehouse2 items loaded: ' . $items->count() . ' items');
            }

            return view('warehouse2.receiving.create', compact('receiptNumber', 'items'));
            
        } catch (\Exception $e) {
            Log::error('Warehouse2 Receiving create error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memuat form: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created receiving.
     */
    public function store(Request $request)
    {
        try {
            // Log request data untuk debug
            Log::info('Warehouse2 Receiving store request:', $request->all());

            $validator = Validator::make($request->all(), [
                'receipt_number' => 'nullable|string|max:50',
                'receipt_date' => 'required|date',
                'supplier' => 'required|string|max:255',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|integer|exists:warehouse2_items,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                Log::error('Validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Create receiving header
            $receivingId = DB::table('warehouse2_receiving')->insertGetId([
                'receipt_number' => $request->receipt_number ?? $this->generateReceiptNumber(),
                'receipt_date' => $request->receipt_date,
                'supplier' => $request->supplier,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('Receiving header created with ID: ' . $receivingId);

            // Create receiving details and update stock
            foreach ($request->items as $index => $item) {
                // Insert detail
                DB::table('warehouse2_receiving_detail')->insert([
                    'receiving_id' => $receivingId,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info("Detail item {$index} inserted: item_id={$item['item_id']}, quantity={$item['quantity']}");

                // Update stock
                $stock = DB::table('warehouse2_stock')
                    ->where('item_id', $item['item_id'])
                    ->first();

                if ($stock) {
                    DB::table('warehouse2_stock')
                        ->where('item_id', $item['item_id'])
                        ->update([
                            'quantity' => DB::raw('quantity + ' . $item['quantity']),
                            'last_updated' => now(),
                            'updated_at' => now()
                        ]);
                    
                    Log::info("Stock updated for item_id: {$item['item_id']}, added: {$item['quantity']}");
                } else {
                    DB::table('warehouse2_stock')->insert([
                        'item_id' => $item['item_id'],
                        'quantity' => $item['quantity'],
                        'location' => 'MAIN',
                        'last_updated' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    Log::info("New stock created for item_id: {$item['item_id']}, quantity: {$item['quantity']}");
                }
            }

            DB::commit();
            Log::info('Receiving transaction committed successfully');

            return response()->json([
                'success' => true,
                'message' => 'Transaksi penerimaan berhasil disimpan',
                'receiving_id' => $receivingId
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Warehouse2 Receiving store error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified receiving.
     */
    public function show($id)
    {
        try {
            $receiving = DB::table('warehouse2_receiving as r')
                ->select(
                    'r.*',
                    'u.name as created_by_name'
                )
                ->leftJoin('users as u', 'r.created_by', '=', 'u.id')
                ->where('r.id', $id)
                ->first();

            if (!$receiving) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan'
                ], 404);
            }

            $details = DB::table('warehouse2_receiving_detail as rd')
                ->join('warehouse2_items as i', 'rd.item_id', '=', 'i.id')
                ->select(
                    'rd.*',
                    'i.code as item_code',
                    'i.name as item_name',
                    'i.unit'
                )
                ->where('rd.receiving_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'header' => $receiving,
                    'details' => $details
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Warehouse2 Receiving show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print receiving document (BTB).
     */
    public function print($id)
    {
        try {
            $receiving = DB::table('warehouse2_receiving as r')
                ->select('r.*', 'u.name as created_by_name')
                ->leftJoin('users as u', 'r.created_by', '=', 'u.id')
                ->where('r.id', $id)
                ->first();

            if (!$receiving) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan'
                ], 404);
            }

            $details = DB::table('warehouse2_receiving_detail as rd')
                ->join('warehouse2_items as i', 'rd.item_id', '=', 'i.id')
                ->select(
                    'rd.*',
                    'i.code as item_code',
                    'i.name as item_name',
                    'i.unit'
                )
                ->where('rd.receiving_id', $id)
                ->get();

            return view('warehouse2.receiving.print', compact('receiving', 'details'));

        } catch (\Exception $e) {
            Log::error('Warehouse2 Receiving print error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mencetak: ' . $e->getMessage());
        }
    }

    /**
     * Download receiving as PDF (optional - if you want PDF version)
     */
    public function downloadPdf($id)
    {
        try {
            $receiving = DB::table('warehouse2_receiving as r')
                ->select('r.*', 'u.name as created_by_name')
                ->leftJoin('users as u', 'r.created_by', '=', 'u.id')
                ->where('r.id', $id)
                ->first();

            if (!$receiving) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan'
                ], 404);
            }

            $details = DB::table('warehouse2_receiving_detail as rd')
                ->join('warehouse2_items as i', 'rd.item_id', '=', 'i.id')
                ->select(
                    'rd.*',
                    'i.code as item_code',
                    'i.name as item_name',
                    'i.unit'
                )
                ->where('rd.receiving_id', $id)
                ->get();

            // Load HTML content
            $html = view('warehouse2.receiving.print', compact('receiving', 'details'))->render();
            
            // Generate PDF (you need to install barryvdh/laravel-dompdf)
            // $pdf = PDF::loadHTML($html);
            // return $pdf->download('BTB-'.$receiving->receipt_number.'.pdf');
            
            // For now, just return the print view
            return view('warehouse2.receiving.print', compact('receiving', 'details'));

        } catch (\Exception $e) {
            Log::error('Warehouse2 Receiving download PDF error: ' . $e->getMessage());
            return back()->with('error', 'Gagal download PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate receipt number.
     */
    private function generateReceiptNumber()
    {
        $year = date('Y');
        $month = date('m');
        
        $lastReceipt = DB::table('warehouse2_receiving')
            ->whereYear('receipt_date', $year)
            ->whereMonth('receipt_date', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReceipt) {
            $lastNumber = intval(substr($lastReceipt->receipt_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "WH2-RCV-{$year}{$month}-{$newNumber}";
    }
}