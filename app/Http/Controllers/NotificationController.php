<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function approvalLevelTwo()
    {
        if (auth()->user()?->role !== 'approval2') {
            return response()->json([
                'success' => true,
                'count' => 0,
                'items' => [],
            ]);
        }

        $items = DB::table('trBPB')
            ->leftJoin('trBPBDetail as d', 'trBPB.id', '=', 'd.trBPB_id')
            ->where('trBPB.status', 'pending')
            ->where('trBPB.approval_current_level', 2)
            ->where('trBPB.approval_level_required', '>=', 2)
            ->where('trBPB.has_high_value_item', true)
            ->select(
                'trBPB.id',
                'trBPB.nomor_pb',
                'trBPB.tanggal_diperlukan',
                'trBPB.untuk',
                'trBPB.jenis_pekerjaan',
                DB::raw('COALESCE(MAX(d.unit_price), 0) as max_unit_price'),
                DB::raw('COALESCE(SUM(d.total_price), 0) as total_value')
            )
            ->groupBy(
                'trBPB.id',
                'trBPB.nomor_pb',
                'trBPB.tanggal_diperlukan',
                'trBPB.untuk',
                'trBPB.jenis_pekerjaan'
            )
            ->orderBy('trBPB.tanggal_diperlukan')
            ->orderByDesc('trBPB.created_at')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $dueDate = $item->tanggal_diperlukan ? now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($item->tanggal_diperlukan)->startOfDay(), false) : null;

                return [
                    'id' => $item->id,
                    'nomor_pb' => $item->nomor_pb,
                    'title' => 'Menunggu Approval L2',
                    'message' => trim(ucwords($item->jenis_pekerjaan ?? '-') . ' untuk ' . ucwords($item->untuk ?? '-')),
                    'tanggal_diperlukan' => $item->tanggal_diperlukan,
                    'due_days' => $dueDate,
                    'max_unit_price' => (float) $item->max_unit_price,
                    'total_value' => (float) $item->total_value,
                    'url' => route('transaksi.index'),
                ];
            });

        $count = DB::table('trBPB')
            ->where('status', 'pending')
            ->where('approval_current_level', 2)
            ->where('approval_level_required', '>=', 2)
            ->where('has_high_value_item', true)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
            'items' => $items,
        ]);
    }
}
