<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()?->role === 'approval2') {
            return $this->approvalLevelTwoDashboard();
        }

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $summary = [
            'pb_total' => $this->countTable('trBPB'),
            'pb_pending' => $this->countWhere('trBPB', 'status', 'pending'),
            'pb_approved' => $this->countWhere('trBPB', 'status', 'approved'),
            'pb_rejected' => $this->countWhere('trBPB', 'status', 'rejected'),
            'pb_this_month' => $this->countDateFrom('trBPB', $this->dateColumn('trBPB', ['tanggal_permintaan', 'created_at']), $startOfMonth),

            'wo_total' => $this->countTable('trWorkOrder'),
            'wo_draft' => $this->countWhere('trWorkOrder', 'status', 'draft'),
            'wo_submitted' => $this->countWhere('trWorkOrder', 'status', 'submitted'),
            'wo_approved' => $this->countWhere('trWorkOrder', 'status', 'approved'),
            'wo_rejected' => $this->countWhere('trWorkOrder', 'status', 'rejected'),
            'wo_completed' => $this->countWhere('trWorkOrder', 'status', 'completed'),
            'wo_open_progress' => $this->countWoProgressOpen(),

            'users_total' => $this->countTable('users'),
            'users_admin' => $this->countWhere('users', 'role', 'admin'),
            'users_approval' => $this->countWhere('users', 'role', 'approval'),
            'users_user' => $this->countWhere('users', 'role', 'user'),

            'audit_today' => $this->countDateFrom('audit_logs', 'created_at', $today),
            'audit_high_risk' => $this->countWhere('audit_logs', 'risk_level', 'high'),
        ];

        $health = [
            'pb_completion_rate' => $this->percentage($summary['pb_approved'], max($summary['pb_total'], 1)),
            'wo_completion_rate' => $this->percentage($summary['wo_completed'], max($summary['wo_total'], 1)),
            'pending_workload' => $summary['pb_pending'] + $summary['wo_submitted'],
            'need_attention' => $summary['pb_rejected'] + $summary['wo_rejected'] + $summary['audit_high_risk'],
        ];

        return view('admin.dashboard', [
            'summary' => $summary,
            'health' => $health,
            'recentPb' => $this->recentPb(),
            'recentWo' => $this->recentWo(),
            'recentLogs' => $this->recentLogs(),
            'monthlyTrend' => $this->monthlyTrend(),
            'lastUpdated' => now()->format('H:i:s'),
        ]);
    }

    private function approvalLevelTwoDashboard()
    {
        $userId = auth()->id();

        $base = DB::table('trBPB')
            ->where('approval_level_required', '>=', 2)
            ->where('has_high_value_item', true);

        $summary = [
            'pending_l2' => (clone $base)
                ->where('status', 'pending')
                ->where('approval_current_level', 2)
                ->count(),
            'approved_by_me' => (clone $base)
                ->where('approval_level_2_by', $userId)
                ->count(),
            'completed' => (clone $base)
                ->where('approval_level_2_by', $userId)
                ->where('status', 'completed')
                ->count(),
            'rejected_by_me' => (clone $base)
                ->where('status', 'rejected')
                ->where('rejected_by', $userId)
                ->where('approval_current_level', 2)
                ->count(),
        ];

        $pending = (clone $base)
            ->leftJoin('trBPBDetail as d', 'trBPB.id', '=', 'd.trBPB_id')
            ->where('trBPB.status', 'pending')
            ->where('trBPB.approval_current_level', 2)
            ->select(
                'trBPB.id',
                'trBPB.nomor_pb',
                'trBPB.tanggal_permintaan',
                'trBPB.tanggal_diperlukan',
                'trBPB.untuk',
                'trBPB.jenis_pekerjaan',
                DB::raw('COUNT(d.id) as total_item'),
                DB::raw('COALESCE(MAX(d.unit_price), 0) as max_unit_price'),
                DB::raw('COALESCE(SUM(d.total_price), 0) as total_value')
            )
            ->groupBy(
                'trBPB.id',
                'trBPB.nomor_pb',
                'trBPB.tanggal_permintaan',
                'trBPB.tanggal_diperlukan',
                'trBPB.untuk',
                'trBPB.jenis_pekerjaan'
            )
            ->orderByDesc('trBPB.created_at')
            ->limit(6)
            ->get();

        $history = (clone $base)
            ->leftJoin('trBPBDetail as d', 'trBPB.id', '=', 'd.trBPB_id')
            ->where('trBPB.approval_level_2_by', $userId)
            ->select(
                'trBPB.id',
                'trBPB.nomor_pb',
                'trBPB.status',
                'trBPB.tanggal_permintaan',
                'trBPB.approval_level_2_at',
                DB::raw('COUNT(d.id) as total_item'),
                DB::raw('COALESCE(SUM(d.total_price), 0) as total_value')
            )
            ->groupBy(
                'trBPB.id',
                'trBPB.nomor_pb',
                'trBPB.status',
                'trBPB.tanggal_permintaan',
                'trBPB.approval_level_2_at'
            )
            ->orderByDesc('trBPB.approval_level_2_at')
            ->limit(6)
            ->get();

        return view('admin.dashboard-approval2', [
            'summary' => $summary,
            'pending' => $pending,
            'history' => $history,
            'lastUpdated' => now()->format('H:i:s'),
        ]);
    }

    private function countTable(string $table): int
    {
        try {
            return Schema::hasTable($table) ? (int) DB::table($table)->count() : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countWhere(string $table, string $column, mixed $value): int
    {
        try {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                return 0;
            }

            return (int) DB::table($table)->where($column, $value)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countDateFrom(string $table, ?string $column, Carbon $date): int
    {
        try {
            if (!$column || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                return 0;
            }

            return (int) DB::table($table)->whereDate($column, '>=', $date->toDateString())->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function dateColumn(string $table, array $columns): ?string
    {
        try {
            if (!Schema::hasTable($table)) {
                return null;
            }

            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    return $column;
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function countWoProgressOpen(): int
    {
        try {
            if (!Schema::hasTable('trWorkOrder')) {
                return 0;
            }

            $query = DB::table('trWorkOrder')->where('status', 'approved');

            if (Schema::hasColumn('trWorkOrder', 'progress_status')) {
                $query->where(function ($q) {
                    $q->whereNull('progress_status')
                        ->orWhereIn('progress_status', ['open', 'progress', 'in_progress']);
                });
            }

            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function percentage(int|float $value, int|float $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0;
    }

    private function recentPb()
    {
        try {
            if (!Schema::hasTable('trBPB')) {
                return collect();
            }

            $select = ['id'];
            foreach (['nomor_pb', 'tanggal_permintaan', 'tanggal_diperlukan', 'untuk', 'jenis_pekerjaan', 'status', 'user_id', 'created_at'] as $column) {
                if (Schema::hasColumn('trBPB', $column)) {
                    $select[] = $column;
                }
            }

            $items = DB::table('trBPB')
                ->select($select)
                ->orderByDesc(Schema::hasColumn('trBPB', 'created_at') ? 'created_at' : 'id')
                ->limit(6)
                ->get();

            if ($items->isEmpty()) {
                return collect();
            }

            $userMap = collect();
            if (Schema::hasTable('users') && Schema::hasColumn('trBPB', 'user_id')) {
                $userMap = DB::table('users')
                    ->whereIn('id', $items->pluck('user_id')->filter()->unique()->values())
                    ->pluck('name', 'id');
            }

            $detailCount = collect();
            $detailQty = collect();

            if (Schema::hasTable('trBPBDetail')) {
                $detailRows = DB::table('trBPBDetail')
                    ->select(
                        'trbpb_id',
                        DB::raw('COUNT(*) as total_item'),
                        DB::raw('COALESCE(SUM(jumlah), 0) as total_qty')
                    )
                    ->whereIn('trbpb_id', $items->pluck('id'))
                    ->groupBy('trbpb_id')
                    ->get();

                $detailCount = $detailRows->pluck('total_item', 'trbpb_id');
                $detailQty = $detailRows->pluck('total_qty', 'trbpb_id');
            }

            return $items->map(function ($item) use ($userMap, $detailCount, $detailQty) {
                $item->nomor_pb = $item->nomor_pb ?? '-';
                $item->tanggal_permintaan = $item->tanggal_permintaan ?? $item->created_at ?? null;
                $item->status = $item->status ?? '-';
                $item->requester = isset($item->user_id) ? ($userMap[$item->user_id] ?? '-') : '-';
                $item->total_item = (int) ($detailCount[$item->id] ?? 0);
                $item->total_qty = (float) ($detailQty[$item->id] ?? 0);

                return $item;
            });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function recentWo()
    {
        try {
            if (!Schema::hasTable('trWorkOrder')) {
                return collect();
            }

            $select = ['id'];
            foreach (['nomor', 'judul', 'status', 'progress_status', 'created_by', 'created_at', 'updated_at'] as $column) {
                if (Schema::hasColumn('trWorkOrder', $column)) {
                    $select[] = $column;
                }
            }

            $items = DB::table('trWorkOrder')
                ->select($select)
                ->orderByDesc(Schema::hasColumn('trWorkOrder', 'created_at') ? 'created_at' : 'id')
                ->limit(6)
                ->get();

            if ($items->isEmpty()) {
                return collect();
            }

            $userMap = collect();
            if (Schema::hasTable('users') && Schema::hasColumn('trWorkOrder', 'created_by')) {
                $userMap = DB::table('users')
                    ->whereIn('id', $items->pluck('created_by')->filter()->unique()->values())
                    ->pluck('name', 'id');
            }

            return $items->map(function ($item) use ($userMap) {
                $item->nomor = $item->nomor ?? '-';
                $item->judul = $item->judul ?? '-';
                $item->status = $item->status ?? '-';
                $item->progress_status = $item->progress_status ?? null;
                $item->created_at = $item->created_at ?? null;
                $item->creator = isset($item->created_by) ? ($userMap[$item->created_by] ?? '-') : '-';

                return $item;
            });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function recentLogs()
    {
        try {
            if (!Schema::hasTable('audit_logs')) {
                return collect();
            }

            $select = ['id'];
            foreach (['user_name', 'module', 'action', 'description', 'risk_level', 'created_at'] as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $select[] = $column;
                }
            }

            return DB::table('audit_logs')
                ->select($select)
                ->orderByDesc(Schema::hasColumn('audit_logs', 'created_at') ? 'created_at' : 'id')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    $item->user_name = $item->user_name ?? 'System';
                    $item->module = $item->module ?? '-';
                    $item->action = $item->action ?? '-';
                    $item->description = $item->description ?? '-';
                    $item->risk_level = $item->risk_level ?? 'low';
                    $item->created_at = $item->created_at ?? null;

                    return $item;
                });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function monthlyTrend(): array
    {
        $months = [];
        $max = 1;

        for ($i = 5; $i >= 0; $i--) {
            $start = Carbon::now()->subMonths($i)->startOfMonth();
            $end = (clone $start)->endOfMonth();

            $pbDateColumn = $this->dateColumn('trBPB', ['tanggal_permintaan', 'created_at']);
            $woDateColumn = $this->dateColumn('trWorkOrder', ['created_at']);

            $pbCount = $this->countBetween('trBPB', $pbDateColumn, $start, $end);
            $woCount = $this->countBetween('trWorkOrder', $woDateColumn, $start, $end);

            $max = max($max, $pbCount, $woCount);

            $months[] = [
                'label' => $start->format('M Y'),
                'pb' => $pbCount,
                'wo' => $woCount,
            ];
        }

        return [
            'max' => $max,
            'items' => $months,
        ];
    }

    private function countBetween(string $table, ?string $column, Carbon $start, Carbon $end): int
    {
        try {
            if (!$column || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                return 0;
            }

            return (int) DB::table($table)
                ->whereBetween($column, [$start->toDateString(), $end->toDateString()])
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
