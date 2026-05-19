<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TransaksiController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request)
    {
        try {
            // Ambil data transaksi - URUTKAN DARI TERBARU
            $transaksi = DB::table('trBPB')
                ->leftJoin('trBPBDetail', 'trBPB.id', '=', 'trBPBDetail.trbpb_id')
                ->select(
                    'trBPB.*',
                    DB::raw('COUNT(trBPBDetail.id) as jumlah_barang'),
                    DB::raw('COALESCE(SUM(trBPBDetail.jumlah), 0) as total_jumlah')
                )
                ->groupBy('trBPB.id')
                ->orderBy('trBPB.created_at', 'desc')
                ->orderBy('trBPB.id', 'desc')
                ->get();

            // CEK APAKAH REQUEST AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $transaksi
                ]);
            }

            // Return view untuk request biasa
            return view('user.transaksi', [
                'nomorPB' => $this->generateNomorPB(),
                'transaksi' => $transaksi
            ]);
            
        } catch (\Exception $e) {
            // Log error untuk debugging
            \Log::error('Transaksi index error: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data',
                    'data' => []
                ], 500);
            }
            
            return view('user.transaksi', [
                'nomorPB' => $this->generateNomorPB(),
                'transaksi' => collect([])
            ]);
        }
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request)
    {
        try {
            // Validasi
            $rules = [
                'nomor_pb' => 'required|string|unique:trBPB,nomor_pb',
                'untuk' => 'required|string',
                'untuk_id' => 'nullable|numeric',
                'dari_gudang' => 'required|string',
                'jenis_pekerjaan' => 'required|string|in:repair,maintenance,project,overhaul',
                'tanggal_diperlukan' => 'required|date|after_or_equal:today',
                'barang' => 'required|array|min:1',
                'barang.*.nama_barang' => 'required|string|max:255',
                'barang.*.jumlah' => 'required|numeric|min:0.01',
                'barang.*.satuan' => 'required|string',
            ];

            // Tambah validasi khusus untuk mesin/bangunan
            if (in_array($request->untuk, ['mesin', 'bangunan'])) {
                $rules['untuk_id'] = 'required|numeric';
            }

            $request->validate($rules);

            DB::beginTransaction();
            
            // Insert header
            $insertData = [
                'nomor_pb' => $request->nomor_pb,
                'tanggal_permintaan' => now(),
                'bagian' => 'Engineering',
                'untuk' => $request->untuk,
                'untuk_id' => $request->untuk_id, // Simpan ID mesin/bangunan
                'dari_gudang' => $request->dari_gudang,
                'tanggal_diperlukan' => $request->tanggal_diperlukan,
                'jenis_pekerjaan' => $request->jenis_pekerjaan,
                'status' => 'pending',
                'keterangan' => $request->keterangan,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Set field spesifik untuk backward compatibility
            if ($request->untuk === 'mesin') {
                $insertData['mesin_id'] = $request->untuk_id;
            } elseif ($request->untuk === 'bangunan') {
                $insertData['bangunan_id'] = $request->untuk_id;
            }

            if (auth()->check()) {
                $insertData['user_id'] = auth()->id();
            }

            // Log untuk debugging
            \Log::info('Menyimpan transaksi:', [
                'untuk' => $request->untuk,
                'untuk_id' => $request->untuk_id,
                'insertData' => $insertData
            ]);

            $trbpbId = DB::table('trBPB')->insertGetId($insertData);

            // Insert detail barang
            $details = [];
            foreach ($request->barang as $item) {
                if (!empty($item['nama_barang']) && !empty($item['jumlah'])) {
                    $details[] = [
                        'trbpb_id' => $trbpbId,
                        'nama_barang' => $item['nama_barang'],
                        'jumlah' => $item['jumlah'],
                        'satuan' => $item['satuan'],
                        'keterangan' => $item['keterangan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (empty($details)) {
                throw new \Exception('Data barang tidak valid');
            }

            DB::table('trBPBDetail')->insert($details);
            DB::commit();

            // AMBIL DATA TERBARU - URUTKAN DARI TERBARU
            $transaksiBaru = DB::table('trBPB')
                ->leftJoin('trBPBDetail', 'trBPB.id', '=', 'trBPBDetail.trbpb_id')
                ->select(
                    'trBPB.*',
                    DB::raw('COUNT(trBPBDetail.id) as jumlah_barang'),
                    DB::raw('COALESCE(SUM(trBPBDetail.jumlah), 0) as total_jumlah')
                )
                ->groupBy('trBPB.id')
                ->orderBy('trBPB.created_at', 'desc')
                ->orderBy('trBPB.id', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Permintaan barang berhasil disimpan',
                'data' => $transaksiBaru
            ]);
            
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving transaksi: ' . $e->getMessage());
            \Log::error('Request data: ', $request->all());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show($id)
    {
        try {
            $transaksi = DB::table('trBPB')
                ->where('id', $id)
                ->first();

            if (!$transaksi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $detail = DB::table('trBPBDetail')
                ->where('trbpb_id', $id)
                ->get();

            // Log untuk debugging
            \Log::info('Show transaksi - ID: ' . $id, [
                'untuk' => $transaksi->untuk,
                'untuk_id' => $transaksi->untuk_id
            ]);

            // Tambah info mesin/bangunan jika ada
            $untukInfo = null;
            
            if ($transaksi->untuk === 'mesin' && $transaksi->untuk_id) {
                $untukInfo = DB::table('mtMesin')
                    ->where('msnID', $transaksi->untuk_id)
                    ->first([
                        'msnID as id',
                        'msnName as nama',
                        'msnCode as kode'
                    ]);
                    
                \Log::info('Mesin info:', ['data' => $untukInfo]);
                
            } elseif ($transaksi->untuk === 'bangunan' && $transaksi->untuk_id) {
                $untukInfo = DB::table('mtBangunan')
                    ->where('buildID', $transaksi->untuk_id)
                    ->first([
                        'buildID as id',
                        'buildName as nama',
                        'buildCode as kode'
                    ]);
                    
                \Log::info('Bangunan info:', ['data' => $untukInfo]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'header' => $transaksi,
                    'detail' => $detail,
                    'untuk_info' => $untukInfo
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate nomor PB untuk keperluan AJAX
     */
    public function generateNomor()
    {
        try {
            $nomorPB = $this->generateNomorPB();
            
            return response()->json([
                'success' => true,
                'nomor_pb' => $nomorPB
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate nomor PB'
            ], 500);
        }
    }

    /**
     * Format: PB-ENG-YYYYMMDD-XXX (XXX = nomor urut 3 digit)
     */
    private function generateNomorPB()
    {
        $today = Carbon::today();
        $dateFormatted = $today->format('Ymd');

        // Cari nomor terakhir hari ini
        $lastPB = DB::table('trBPB')
            ->where('nomor_pb', 'like', 'PB-ENG-' . $dateFormatted . '-%')
            ->orderBy('nomor_pb', 'desc')
            ->first();

        if ($lastPB) {
            $parts = explode('-', $lastPB->nomor_pb);
            $lastSeq = intval(end($parts));
            $seq = str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $seq = '001';
        }

        return 'PB-ENG-' . $dateFormatted . '-' . $seq;
    }

    /**
     * Update data transaksi (untuk approval/reject)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:approved,rejected,in_progress,completed',
                'alasan' => 'required_if:status,rejected|string|nullable'
            ]);

            $updateData = [
                'status' => $request->status,
                'updated_at' => now()
            ];

            if ($request->status === 'approved') {
                $updateData['approved_at'] = now();
                $updateData['approved_by'] = auth()->id();
            } elseif ($request->status === 'rejected') {
                $updateData['rejected_at'] = now();
                $updateData['rejected_by'] = auth()->id();
                $updateData['rejection_reason'] = $request->alasan;
            }

            DB::table('trBPB')
                ->where('id', $id)
                ->update($updateData);

            // Ambil data terbaru
            $transaksiBaru = DB::table('trBPB')
                ->leftJoin('trBPBDetail', 'trBPB.id', '=', 'trBPBDetail.trbpb_id')
                ->select(
                    'trBPB.*',
                    DB::raw('COUNT(trBPBDetail.id) as jumlah_barang'),
                    DB::raw('COALESCE(SUM(trBPBDetail.jumlah), 0) as total_jumlah')
                )
                ->groupBy('trBPB.id')
                ->orderBy('trBPB.created_at', 'desc')
                ->orderBy('trBPB.id', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diupdate',
                'data' => $transaksiBaru
            ]);

        } catch (\Exception $e) {
            \Log::error('Error update status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for dashboard
     */
    public function statistics()
    {
        try {
            $stats = [
                'total' => DB::table('trBPB')->count(),
                'pending' => DB::table('trBPB')->where('status', 'pending')->count(),
                'approved' => DB::table('trBPB')->where('status', 'approved')->count(),
                'rejected' => DB::table('trBPB')->where('status', 'rejected')->count(),
                'in_progress' => DB::table('trBPB')->where('status', 'in_progress')->count(),
                'completed' => DB::table('trBPB')->where('status', 'completed')->count(),
            ];

            $byJenisPekerjaan = DB::table('trBPB')
                ->select('jenis_pekerjaan', DB::raw('count(*) as total'))
                ->groupBy('jenis_pekerjaan')
                ->get();

            $byUntuk = DB::table('trBPB')
                ->select('untuk', DB::raw('count(*) as total'))
                ->groupBy('untuk')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'by_jenis_pekerjaan' => $byJenisPekerjaan,
                    'by_untuk' => $byUntuk
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik'
            ], 500);
        }
    }
}