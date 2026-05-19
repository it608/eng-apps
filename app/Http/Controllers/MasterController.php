<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MasterController extends Controller
{
    /**
     * ===============================
     * HALAMAN UTAMA MASTER DATA
     * ===============================
     */
    public function index()
    {
        try {
            /**
             * ===============================
             * MASTER SPAREPART (PostgreSQL)
             * ===============================
             */
            $barang = DB::connection('pgsql2')
                ->table('tb_skb080_1mmara')
                ->select(
                    'id_items',
                    'code',
                    'mtart',
                    'meins',
                    'item_name'
                )
                ->where('mtart', 'YSPR')
                ->orderBy('code')
                ->get();

            /**
             * ===============================
             * MASTER MESIN (MySQL)
             * ===============================
             */
            $mesin = DB::table('mtMesin as m')
                ->join('mtZona as z', 'm.znID', '=', 'z.znID')
                ->join('mtArea as a', 'm.areaID', '=', 'a.areaID')
                ->select(
                    'm.msnID',
                    'm.msnCode',
                    'm.msnName',
                    'z.znName',
                    'a.areaName'
                )
                ->orderBy('m.msnCode')
                ->get();

            /**
             * ===============================
             * MASTER BANGUNAN (MySQL)
             * ===============================
             */
            $bangunan = DB::table('mtBangunan')
                ->select('buildID as id', 'buildCode as code', 'buildName as name', 'znID as zona')
                ->orderBy('code')
                ->get();

            // Konversi znID ke nama zona untuk bangunan
            $bangunan = $bangunan->map(function($item) {
                $zona = DB::table('mtZona')->where('znID', $item->zona)->first();
                $item->zona = $zona ? $zona->znName : '-';
                return $item;
            });

            // Ambil semua Zona & Area untuk dropdown modal
            $zonas = DB::table('mtZona')->orderBy('znName')->get();
            $areas = DB::table('mtArea')->orderBy('areaName')->get();

            return view('user.master', compact('barang', 'mesin', 'bangunan', 'zonas', 'areas'));

        } catch (\Exception $e) {
            Log::error('Master index error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memuat data master: ' . $e->getMessage());
        }
    }

    /**
     * ===============================
     * UPDATE MASTER MESIN VIA AJAX (DENGAN DEBUG)
     * ===============================
     */
    public function updateMesin(Request $request, $id)
    {
        try {
            // Log request untuk debugging
            Log::info('Update mesin request received', [
                'id' => $id,
                'data' => $request->all()
            ]);

            // Validasi input
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|max:50',
                'name' => 'required|string|max:100',
                'zona' => 'required|string|max:100',
                'area' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                Log::warning('Validasi gagal', ['errors' => $validator->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cari zona
            $zona = DB::table('mtZona')->where('znName', $request->zona)->first();
            if (!$zona) {
                Log::warning('Zona tidak ditemukan', ['zona' => $request->zona]);
                return response()->json([
                    'success' => false,
                    'message' => 'Zona tidak ditemukan: ' . $request->zona
                ], 404);
            }

            // Cari area
            $area = DB::table('mtArea')->where('areaName', $request->area)->first();
            if (!$area) {
                Log::warning('Area tidak ditemukan', ['area' => $request->area]);
                return response()->json([
                    'success' => false,
                    'message' => 'Area tidak ditemukan: ' . $request->area
                ], 404);
            }

            // Cek apakah mesin dengan ID tersebut ada
            $mesin = DB::table('mtMesin')->where('msnID', $id)->first();
            if (!$mesin) {
                Log::warning('Mesin tidak ditemukan', ['id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Mesin dengan ID ' . $id . ' tidak ditemukan'
                ], 404);
            }

            // Update database
            $updated = DB::table('mtMesin')
                ->where('msnID', $id)
                ->update([
                    'msnCode' => $request->code,
                    'msnName' => $request->name,
                    'znID' => $zona->znID,
                    'areaID' => $area->areaID,
                ]);

            Log::info('Update result', ['updated' => $updated]);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data mesin berhasil diupdate'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada perubahan data'
                ]);
            }

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error: ' . $e->getMessage());
            Log::error('SQL: ' . $e->getSql());
            Log::error('Bindings: ' . json_encode($e->getBindings()));
            
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Update mesin error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ===============================
     * UPDATE MASTER BANGUNAN VIA AJAX
     * ===============================
     */
    public function updateBangunan(Request $request, $id)
    {
        try {
            // Log request untuk debugging
            Log::info('Update bangunan request received', [
                'id' => $id,
                'data' => $request->all()
            ]);

            $validator = Validator::make($request->all(), [
                'code' => 'required|string|max:50',
                'name' => 'required|string|max:100',
                'zona' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cari znID berdasarkan nama zona
            $zona = DB::table('mtZona')->where('znName', $request->zona)->first();

            if (!$zona) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zona tidak ditemukan: ' . $request->zona
                ], 404);
            }

            // Cek apakah bangunan dengan ID tersebut ada
            $bangunan = DB::table('mtBangunan')->where('buildID', $id)->first();
            if (!$bangunan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bangunan dengan ID ' . $id . ' tidak ditemukan'
                ], 404);
            }

            $updated = DB::table('mtBangunan')
                ->where('buildID', $id)
                ->update([
                    'buildCode' => $request->code,
                    'buildName' => $request->name,
                    'znID' => $zona->znID,
                ]);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data bangunan berhasil diupdate'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada perubahan data'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Update bangunan error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ===============================
     * GET SPAREPART DATA FOR AJAX
     * ===============================
     */
    public function getSparepartData(Request $request)
    {
        try {
            $query = DB::connection('pgsql2')
                ->table('tb_skb080_1mmara')
                ->where('mtart', 'YSPR')
                ->select('code', 'item_name as name', 'meins as unit');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('item_name', 'like', "%{$search}%");
                });
            }

            // Filter kolom
            if ($request->filled('filter_code')) {
                $query->where('code', 'like', "%{$request->filter_code}%");
            }
            if ($request->filled('filter_name')) {
                $query->where('item_name', 'like', "%{$request->filter_name}%");
            }
            if ($request->filled('filter_unit')) {
                $query->where('meins', $request->filter_unit);
            }

            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);

            $total = $query->count();
            $data = $query->orderBy('code')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $total,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage)
            ]);

        } catch (\Exception $e) {
            Log::error('Get sparepart data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data sparepart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ===============================
     * GET MESIN DATA FOR AJAX
     * ===============================
     */
    public function getMesinData(Request $request)
    {
        try {
            $query = DB::table('mtMesin as m')
                ->leftJoin('mtZona as z', 'm.znID', '=', 'z.znID')
                ->leftJoin('mtArea as a', 'm.areaID', '=', 'a.areaID')
                ->select('m.msnID as id', 'm.msnCode as code', 'm.msnName as name', 
                         'z.znName as zona', 'a.areaName as area');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('m.msnCode', 'like', "%{$search}%")
                      ->orWhere('m.msnName', 'like', "%{$search}%")
                      ->orWhere('z.znName', 'like', "%{$search}%")
                      ->orWhere('a.areaName', 'like', "%{$search}%");
                });
            }

            // Filter kolom
            if ($request->filled('filter_code')) {
                $query->where('m.msnCode', 'like', "%{$request->filter_code}%");
            }
            if ($request->filled('filter_name')) {
                $query->where('m.msnName', 'like', "%{$request->filter_name}%");
            }
            if ($request->filled('filter_zona')) {
                $query->where('z.znName', $request->filter_zona);
            }
            if ($request->filled('filter_area')) {
                $query->where('a.areaName', $request->filter_area);
            }

            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);

            $total = $query->count();
            $data = $query->orderBy('code')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $total,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage)
            ]);

        } catch (\Exception $e) {
            Log::error('Get mesin data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data mesin: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ===============================
     * GET BANGUNAN DATA FOR AJAX
     * ===============================
     */
    public function getBangunanData(Request $request)
    {
        try {
            $query = DB::table('mtBangunan')
                ->select('buildID as id', 'buildCode as code', 'buildName as name', 'znID as zona');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('buildCode', 'like', "%{$search}%")
                      ->orWhere('buildName', 'like', "%{$search}%");
                });
            }

            // Filter kolom
            if ($request->filled('filter_code')) {
                $query->where('buildCode', 'like', "%{$request->filter_code}%");
            }
            if ($request->filled('filter_name')) {
                $query->where('buildName', 'like', "%{$request->filter_name}%");
            }
            if ($request->filled('filter_zona')) {
                // Filter berdasarkan znID (integer)
                $zona = DB::table('mtZona')->where('znName', $request->filter_zona)->first();
                if ($zona) {
                    $query->where('znID', $zona->znID);
                }
            }

            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);

            $total = $query->count();
            $data = $query->orderBy('code')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            // Konversi znID ke nama zona untuk response
            $data = $data->map(function($item) {
                $zona = DB::table('mtZona')->where('znID', $item->zona)->first();
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'zona' => $zona ? $zona->znName : '-'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $total,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage)
            ]);

        } catch (\Exception $e) {
            Log::error('Get bangunan data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data bangunan: ' . $e->getMessage()
            ], 500);
        }
    }
}