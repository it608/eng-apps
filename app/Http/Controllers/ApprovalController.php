<?php
// app/Http/Controllers/ApprovalController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalController extends Controller
{
    public function index()
    {
        $pendingRequests = DB::table('trBPB')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('approval.index', compact('pendingRequests'));
    }

    public function approve($id)
    {
        try {
            DB::beginTransaction();

            // Update status
            DB::table('trBPB')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permintaan berhasil disetujui'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approve error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject($id, Request $request)
    {
        try {
            $request->validate([
                'alasan' => 'required|string|max:255'
            ]);

            DB::beginTransaction();

            // Update status
            DB::table('trBPB')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'rejection_reason' => $request->alasan,
                    'rejected_at' => now(),
                    'rejected_by' => auth()->id(),
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permintaan ditolak'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reject error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menolak: ' . $e->getMessage()
            ], 500);
        }
    }

    public function statistics()
    {
        try {
            $stats = [
                'total_pending' => DB::table('trBPB')->where('status', 'pending')->count(),
                'total_approved' => DB::table('trBPB')->where('status', 'approved')->count(),
                'total_rejected' => DB::table('trBPB')->where('status', 'rejected')->count(),
                'total_completed' => DB::table('trBPB')->where('status', 'completed')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik'
            ], 500);
        }
    }

    public function bulkApprove(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:trBPB,id'
            ]);

            DB::beginTransaction();

            DB::table('trBPB')
                ->whereIn('id', $request->ids)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($request->ids) . ' permintaan berhasil disetujui'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal approve massal: ' . $e->getMessage()
            ], 500);
        }
    }
}