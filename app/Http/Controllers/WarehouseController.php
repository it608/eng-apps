<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index()
    {
        return view('warehouse.pb.index');
    }

    public function data(Request $request)
    {
        try {
            $query = DB::table('trBPB as pb')
                ->leftJoin('trBPBDetail as d', 'pb.id', '=', 'd.trBPB_id')
                ->select(
                    'pb.id',
                    'pb.nomor_pb',
                    'pb.tanggal_permintaan',
                    'pb.tanggal_diperlukan',
                    'pb.untuk',
                    'pb.dari_gudang',
                    'pb.jenis_pekerjaan',
                    'pb.status',
                    'pb.approval_level_required',
                    'pb.has_high_value_item',
                    'pb.erp_gi_number',
                    'pb.erp_gi_recorded_at',
                    DB::raw('COUNT(d.id) as total_items'),
                    DB::raw("SUM(CASE WHEN d.fulfillment_status = 'pending' THEN 1 ELSE 0 END) as pending_items"),
                    DB::raw("SUM(CASE WHEN d.fulfillment_status = 'checked' THEN 1 ELSE 0 END) as checked_items"),
                    DB::raw("SUM(CASE WHEN d.fulfillment_status = 'hold' THEN 1 ELSE 0 END) as hold_items"),
                    DB::raw("SUM(CASE WHEN d.fulfillment_status = 'rejected' THEN 1 ELSE 0 END) as rejected_items")
                )
                ->whereIn('pb.status', ['approved', 'in_progress', 'completed'])
                ->where(function ($q) {
                    $q->where('pb.is_legacy', false)
                        ->orWhereNull('pb.is_legacy');
                })
                ->groupBy('pb.id');

            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('pb.nomor_pb', 'like', "%{$search}%")
                        ->orWhere('pb.untuk', 'like', "%{$search}%")
                        ->orWhere('pb.dari_gudang', 'like', "%{$search}%")
                        ->orWhere('d.nama_barang', 'like', "%{$search}%");
                });
            }

            $data = $query
                ->orderBy('pb.updated_at', 'desc')
                ->orderBy('pb.id', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Warehouse PB data error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data PB warehouse',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $pb = DB::table('trBPB')
                ->where('id', $id)
                ->first();

            if (!$pb) {
                return response()->json([
                    'success' => false,
                    'message' => 'PB tidak ditemukan',
                ], 404);
            }

            if ((bool) ($pb->is_legacy ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PB ini sudah diarsipkan sebagai data legacy',
                ], 422);
            }

            $detail = DB::table('trBPBDetail as d')
                ->leftJoin('users as u', 'd.fulfilled_by', '=', 'u.id')
                ->where('d.trBPB_id', $id)
                ->select('d.*', 'u.name as fulfilled_by_name')
                ->orderBy('d.id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'header' => $pb,
                    'detail' => $detail,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Warehouse PB show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail PB',
            ], 500);
        }
    }

    public function updateItem(Request $request, $id, $detailId)
    {
        $request->validate([
            'status' => ['required', Rule::in(['checked', 'hold', 'rejected'])],
            'note' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $pb = DB::table('trBPB')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (
                !$pb
                || (bool) ($pb->is_legacy ?? false)
                || !in_array($pb->status, ['approved', 'in_progress', 'completed'], true)
            ) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'PB belum siap diproses warehouse',
                ], 422);
            }

            $detail = DB::table('trBPBDetail')
                ->where('id', $detailId)
                ->where('trBPB_id', $id)
                ->lockForUpdate()
                ->first();

            if (!$detail) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Item PB tidak ditemukan',
                ], 404);
            }

            DB::table('trBPBDetail')
                ->where('id', $detailId)
                ->update([
                    'fulfillment_status' => $request->status,
                    'fulfillment_note' => $request->note,
                    'fulfilled_by' => auth()->id(),
                    'fulfilled_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->syncHeaderStatus($id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status pemenuhan item berhasil diperbarui',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Warehouse PB update item error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal update item: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateReference(Request $request, $id)
    {
        $request->validate([
            'erp_gi_number' => 'nullable|string|max:100',
        ]);

        try {
            $pb = DB::table('trBPB')
                ->where('id', $id)
                ->whereIn('status', ['approved', 'in_progress', 'completed'])
                ->where(function ($q) {
                    $q->where('is_legacy', false)
                        ->orWhereNull('is_legacy');
                })
                ->first();

            if (!$pb) {
                return response()->json([
                    'success' => false,
                    'message' => 'PB belum siap diproses warehouse',
                ], 422);
            }

            $number = trim((string) $request->erp_gi_number);

            DB::table('trBPB')
                ->where('id', $id)
                ->update([
                    'erp_gi_number' => $number !== '' ? $number : null,
                    'erp_gi_recorded_by' => $number !== '' ? auth()->id() : null,
                    'erp_gi_recorded_at' => $number !== '' ? now() : null,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Nomor Good Issue ERP berhasil disimpan',
            ]);
        } catch (\Exception $e) {
            Log::error('Warehouse PB update ERP reference error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan nomor Good Issue ERP',
            ], 500);
        }
    }

    private function syncHeaderStatus(int $pbId): void
    {
        $summary = DB::table('trBPBDetail')
            ->where('trBPB_id', $pbId)
            ->select(
                DB::raw('COUNT(*) as total_items'),
                DB::raw("SUM(CASE WHEN fulfillment_status = 'checked' THEN 1 ELSE 0 END) as checked_items"),
                DB::raw("SUM(CASE WHEN fulfillment_status <> 'pending' THEN 1 ELSE 0 END) as processed_items")
            )
            ->first();

        $totalItems = (int) ($summary->total_items ?? 0);
        $checkedItems = (int) ($summary->checked_items ?? 0);
        $processedItems = (int) ($summary->processed_items ?? 0);

        $status = 'approved';

        if ($totalItems > 0 && $checkedItems === $totalItems) {
            $status = 'completed';
        } elseif ($processedItems > 0) {
            $status = 'in_progress';
        }

        DB::table('trBPB')
            ->where('id', $pbId)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
    }
}
