<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
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

    private function countTable(string $table): int
    {
        try {
            if (!Schema::hasTable($table)) {
                return 0;
            }

            return (int) DB::table($table)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countWhere(string $table, string $column, $value): int
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

            return null;
        } catch (\Throwable $e) {
            return null;
        }
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
                        ->orWhereIn('progress_status', ['open', 'progress']);
                });
            }

            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function percentage(int|float $value, int|float $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($value / $total) * 100, 1);
    }

    private function recentPb()
    {
        try {
            if (!Schema::hasTable('trBPB')) {
                return collect();
            }

            $items = DB::table('trBPB')
                ->select('id', 'nomor_pb', 'tanggal_permintaan', 'tanggal_diperlukan', 'untuk', 'jenis_pekerjaan', 'status', 'user_id', 'created_at')
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
                    ->select('trbpb_id', DB::raw('COUNT(*) as total_item'), DB::raw('COALESCE(SUM(jumlah), 0) as total_qty'))
                    ->whereIn('trbpb_id', $items->pluck('id'))
                    ->groupBy('trbpb_id')
                    ->get();

                $detailCount = $detailRows->pluck('total_item', 'trbpb_id');
                $detailQty = $detailRows->pluck('total_qty', 'trbpb_id');
            }

            return $items->map(function ($item) use ($userMap, $detailCount, $detailQty) {
                $item->requester = $userMap[$item->user_id] ?? '-';
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

            $items = DB::table('trWorkOrder')
                ->select('id', 'nomor', 'judul', 'status', 'progress_status', 'created_by', 'created_at', 'updated_at')
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
                $item->creator = $userMap[$item->created_by] ?? '-';
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

            return DB::table('audit_logs')
                ->select('id', 'user_name', 'module', 'action', 'description', 'risk_level', 'created_at')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
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
