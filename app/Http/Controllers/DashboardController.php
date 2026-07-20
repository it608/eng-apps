<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()?->role === 'approval2') {
            return $this->approvalLevelTwoDashboard();
        }

        if (in_array(auth()->user()?->role, ['approval', 'approval_level1'], true)) {
            return $this->approvalLevelOneDashboard($request);
        }

        $user = auth()->user();
        $isWarehouseDashboard = $this->isWarehouseUser($user);

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $dashboardPeriod = $this->dashboardPeriod($request);
        $pbDateColumn = $this->dateColumn('trBPB', ['tanggal_permintaan', 'created_at']);
        $woDateColumn = $this->dateColumn('trWorkOrder', ['created_at']);

        $summary = [
            'pb_total' => $this->countBetween('trBPB', $pbDateColumn, $dashboardPeriod['start'], $dashboardPeriod['end']),
            'pb_pending' => $this->countWhereBetween('trBPB', 'status', 'pending', $pbDateColumn, $dashboardPeriod['start'], $dashboardPeriod['end']),
            'pb_approved' => $this->countWhereBetween('trBPB', 'status', 'approved', $pbDateColumn, $dashboardPeriod['start'], $dashboardPeriod['end']),
            'pb_rejected' => $this->countWhereBetween('trBPB', 'status', 'rejected', $pbDateColumn, $dashboardPeriod['start'], $dashboardPeriod['end']),
            'pb_this_month' => $this->countDateFrom('trBPB', $this->dateColumn('trBPB', ['tanggal_permintaan', 'created_at']), $startOfMonth),

            'wo_total' => $this->countBetween('trWorkOrder', $woDateColumn, $dashboardPeriod['start'], $dashboardPeriod['end']),
            'wo_draft' => $this->countWhereBetween('trWorkOrder', 'status', 'draft', $woDateColumn, $dashboardPeriod['start'], $dashboardPeriod['end']),
            'wo_submitted' => $this->countWhereBetween('trWorkOrder', 'status', 'submitted', $woDateColumn, $dashboardPeriod['start'], $dashboardPeriod['end']),
            'wo_approved' => $this->countWhereBetween('trWorkOrder', 'status', 'approved', $woDateColumn, $dashboardPeriod['start'], $dashboardPeriod['end']),
            'wo_rejected' => $this->countWhereBetween('trWorkOrder', 'status', 'rejected', $woDateColumn, $dashboardPeriod['start'], $dashboardPeriod['end']),
            'wo_completed' => $this->countWoProgressClosed($woDateColumn, $dashboardPeriod['start'], $dashboardPeriod['end']),
            'wo_open_progress' => $this->countWoProgressOpen($woDateColumn, $dashboardPeriod['start'], $dashboardPeriod['end']),

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
            'dashboardMode' => $isWarehouseDashboard ? 'warehouse' : 'engineering',
            'dashboardPeriod' => $dashboardPeriod,
            'summary' => $summary,
            'health' => $health,
            'warehouseSummary' => $this->warehouseSummary(),
            'warehouseRecentPb' => $this->warehouseRecentPb(),
            'warehouseTrend' => $this->warehouseTrend(),
            'recentPb' => $this->recentPb(),
            'recentWo' => $this->recentWo(),
            'recentLogs' => $this->recentLogs(),
            'monthlyTrend' => $this->monthlyTrend(),
            'lastUpdated' => now()->format('H:i:s'),
        ]);
    }

    private function isWarehouseUser($user): bool
    {
        if (!$user) {
            return false;
        }

        $department = strtolower((string) ($user->department_code ?? ''));
        $username = strtolower((string) ($user->username ?? ''));
        $name = strtolower((string) ($user->name ?? ''));
        $role = strtolower((string) ($user->role ?? ''));

        return $role === 'warehouse'
            || $department === 'warehouse'
            || str_contains($username, 'warehouse')
            || str_contains($name, 'warehouse');
    }

    private function dashboardPeriod(Request $request): array
    {
        $mode = $request->get('period_mode') === 'month' ? 'month' : 'ytd';
        $month = $request->get('period_month') ?: now()->format('Y-m');

        try {
            $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable $e) {
            $monthDate = now()->startOfMonth();
            $month = $monthDate->format('Y-m');
        }

        if ($mode === 'month') {
            $start = $monthDate->copy()->startOfMonth();
            $end = $monthDate->copy()->endOfMonth();
            $label = $start->translatedFormat('F Y');
        } else {
            $start = now()->startOfYear();
            $end = now();
            $label = 'Year to Date ' . now()->format('Y');
        }

        return [
            'mode' => $mode,
            'month' => $month,
            'start' => $start,
            'end' => $end,
            'label' => $label,
        ];
    }

    private function approvalLevelOneDashboard(Request $request)
    {
        $userId = auth()->id();
        $today = Carbon::today();
        $dashboardPeriod = $this->dashboardPeriod($request);

        $pbBase = DB::table('trBPB');
        $woBase = DB::table('trWorkOrder');

        $summary = [
            'pending_pb' => (clone $pbBase)
                ->where('status', 'pending')
                ->where('approval_current_level', 1)
                ->count(),
            'pending_wo' => (clone $woBase)
                ->where('status', 'submitted')
                ->count(),
            'approved_today' => (clone $pbBase)
                ->where('approval_level_1_by', $userId)
                ->whereDate('approval_level_1_at', $today)
                ->count()
                + (clone $woBase)
                    ->where('approved_by', $userId)
                    ->whereDate('approved_at', $today)
                    ->count(),
            'rejected_today' => (clone $pbBase)
                ->where('rejected_by', $userId)
                ->whereDate('rejected_at', $today)
                ->count()
                + (clone $woBase)
                    ->where('rejected_by', $userId)
                    ->whereDate('rejected_at', $today)
                    ->count(),
            'high_value_to_l2' => (clone $pbBase)
                ->where('status', 'pending')
                ->where('approval_current_level', 1)
                ->where('has_high_value_item', true)
                ->count(),
        ];

        $budget = [
            'approved_direct_l1' => $this->sumPbBudget(function ($query) use ($dashboardPeriod) {
                $query->where('trBPB.approval_level_required', 1)
                    ->whereIn('trBPB.status', ['approved', 'in_progress', 'completed'])
                    ->where(function ($approval) {
                        $approval->whereNotNull('trBPB.approval_level_1_at')
                            ->orWhereNotNull('trBPB.approved_at');
                    });
                $this->applyBudgetPeriod($query, $dashboardPeriod, ['trBPB.approval_level_1_at', 'trBPB.approved_at']);
            }),
            'waiting_l2' => $this->sumPbBudget(function ($query) use ($dashboardPeriod) {
                $query->where('trBPB.approval_level_required', '>=', 2)
                    ->where('trBPB.status', 'pending')
                    ->where('trBPB.approval_current_level', 2)
                    ->whereNotNull('trBPB.approval_level_1_at');
                $this->applyBudgetPeriod($query, $dashboardPeriod, ['trBPB.approval_level_1_at']);
            }),
            'approved_l2' => $this->sumPbBudget(function ($query) use ($dashboardPeriod) {
                $query->where('trBPB.approval_level_required', '>=', 2)
                    ->whereIn('trBPB.status', ['approved', 'in_progress', 'completed'])
                    ->whereNotNull('trBPB.approval_level_1_at')
                    ->whereNotNull('trBPB.approval_level_2_at');
                $this->applyBudgetPeriod($query, $dashboardPeriod, ['trBPB.approval_level_2_at']);
            }),
            'rejected' => $this->sumPbBudget(function ($query) use ($dashboardPeriod) {
                $query->where('trBPB.status', 'rejected');
                $this->applyBudgetPeriod($query, $dashboardPeriod, ['trBPB.rejected_at']);
            }),
        ];
        $budget['total_used'] = $budget['approved_direct_l1'] + $budget['approved_l2'];
        $budget['max'] = max(
            $budget['approved_direct_l1'],
            $budget['waiting_l2'],
            $budget['approved_l2'],
            $budget['rejected'],
            1
        );
        $budget += [
            'warehouse_issued' => 0,
            'warehouse_pending' => $budget['total_used'],
            'warehouse_issued_items' => 0,
            'warehouse_total_items' => 0,
            'warehouse_pending_items' => 0,
            'warehouse_pending_no_price_items' => 0,
            'warehouse_chart_max' => max($budget['total_used'], 1),
        ];

        $pendingPb = DB::table('trBPB')
            ->leftJoin('trBPBDetail as d', 'trBPB.id', '=', 'd.trBPB_id')
            ->where('trBPB.status', 'pending')
            ->where('trBPB.approval_current_level', 1)
            ->select(
                'trBPB.id',
                'trBPB.nomor_pb',
                'trBPB.tanggal_permintaan',
                'trBPB.tanggal_diperlukan',
                'trBPB.untuk',
                'trBPB.jenis_pekerjaan',
                'trBPB.has_high_value_item',
                'trBPB.approval_level_required',
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
                'trBPB.jenis_pekerjaan',
                'trBPB.has_high_value_item',
                'trBPB.approval_level_required'
            )
            ->orderBy('trBPB.tanggal_diperlukan')
            ->orderByDesc('trBPB.created_at')
            ->limit(8)
            ->get();

        $pendingWo = DB::table('trWorkOrder')
            ->leftJoin('users as creator', 'trWorkOrder.created_by', '=', 'creator.id')
            ->where('trWorkOrder.status', 'submitted')
            ->select(
                'trWorkOrder.id',
                'trWorkOrder.nomor',
                'trWorkOrder.judul',
                'trWorkOrder.deskripsi',
                'trWorkOrder.submitted_at',
                'trWorkOrder.created_at',
                'creator.name as requester'
            )
            ->orderByDesc('trWorkOrder.submitted_at')
            ->orderByDesc('trWorkOrder.created_at')
            ->limit(8)
            ->get();

        $historyPb = DB::table('trBPB')
            ->leftJoin('trBPBDetail as d', 'trBPB.id', '=', 'd.trBPB_id')
            ->where(function ($query) use ($userId) {
                $query->where('trBPB.approval_level_1_by', $userId)
                    ->orWhere('trBPB.rejected_by', $userId);
            })
            ->select(
                'trBPB.id',
                'trBPB.nomor_pb as nomor',
                'trBPB.status',
                'trBPB.jenis_pekerjaan as kategori',
                'trBPB.approval_level_1_at as approved_at',
                'trBPB.rejected_at',
                DB::raw("'PB' as tipe"),
                DB::raw('COUNT(d.id) as total_item')
            )
            ->groupBy(
                'trBPB.id',
                'trBPB.nomor_pb',
                'trBPB.status',
                'trBPB.jenis_pekerjaan',
                'trBPB.approval_level_1_at',
                'trBPB.rejected_at'
            )
            ->orderByRaw('COALESCE(trBPB.approval_level_1_at, trBPB.rejected_at) DESC')
            ->limit(8)
            ->get();

        $historyWo = DB::table('trWorkOrder')
            ->where(function ($query) use ($userId) {
                $query->where('approved_by', $userId)
                    ->orWhere('rejected_by', $userId);
            })
            ->select(
                'id',
                'nomor',
                'status',
                'judul as kategori',
                'approved_at',
                'rejected_at',
                DB::raw("'WO' as tipe"),
                DB::raw('1 as total_item')
            )
            ->orderByRaw('COALESCE(approved_at, rejected_at) DESC')
            ->limit(8)
            ->get();

        $history = $historyPb
            ->merge($historyWo)
            ->sortByDesc(fn ($item) => $item->approved_at ?? $item->rejected_at ?? '')
            ->take(8)
            ->values();

        return view('admin.dashboard-approval1', [
            'summary' => $summary,
            'budget' => $budget,
            'pendingPb' => $pendingPb,
            'pendingWo' => $pendingWo,
            'history' => $history,
            'dashboardPeriod' => $dashboardPeriod,
            'lastUpdated' => now()->format('H:i:s'),
        ]);
    }

    private function applyBudgetPeriod($query, array $period, array $columns): void
    {
        $dateExpression = count($columns) > 1
            ? 'COALESCE(' . implode(', ', $columns) . ')'
            : $columns[0];

        $query->whereRaw("{$dateExpression} BETWEEN ? AND ?", [
            $period['start']->toDateTimeString(),
            $period['end']->toDateTimeString(),
        ]);
    }

    private function sumPbBudget(callable $filter): float
    {
        try {
            if (!Schema::hasTable('trBPB') || !Schema::hasTable('trBPBDetail')) {
                return 0;
            }

            $query = DB::table('trBPB')
                ->leftJoin('trBPBDetail as d', 'trBPB.id', '=', 'd.trBPB_id');

            $filter($query);

            return (float) $query->sum('d.total_price');
        } catch (\Throwable $e) {
            return 0;
        }
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

    private function countWoProgressOpen(?string $dateColumn = null, ?Carbon $start = null, ?Carbon $end = null): int
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

            if ($dateColumn && $start && $end && Schema::hasColumn('trWorkOrder', $dateColumn)) {
                $query->whereBetween($dateColumn, $this->dateTimeBounds($start, $end));
            }

            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countWoProgressClosed(?string $dateColumn = null, ?Carbon $start = null, ?Carbon $end = null): int
    {
        try {
            if (!Schema::hasTable('trWorkOrder')) {
                return 0;
            }

            $query = DB::table('trWorkOrder')->where('status', 'approved');

            if (Schema::hasColumn('trWorkOrder', 'progress_status')) {
                $query->where('progress_status', 'closed');
            } else {
                $query->where('status', 'completed');
            }

            if ($dateColumn && $start && $end && Schema::hasColumn('trWorkOrder', $dateColumn)) {
                $query->whereBetween($dateColumn, $this->dateTimeBounds($start, $end));
            }

            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function warehouseSummary(): array
    {
        $basePb = function () {
            return DB::table('trBPB')
                ->whereIn('status', ['approved', 'in_progress', 'completed'])
                ->where(function ($query) {
                    $query->where('is_legacy', false)
                        ->orWhereNull('is_legacy');
                });
        };

        $itemCount = function (string $status): int {
            try {
                if (!Schema::hasTable('trBPBDetail')) {
                    return 0;
                }

                return (int) DB::table('trBPBDetail as d')
                    ->join('trBPB as pb', 'pb.id', '=', 'd.trBPB_id')
                    ->whereIn('pb.status', ['approved', 'in_progress', 'completed'])
                    ->where(function ($query) {
                        $query->where('pb.is_legacy', false)
                            ->orWhereNull('pb.is_legacy');
                    })
                    ->where('d.fulfillment_status', $status)
                    ->count();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        try {
            $pbReady = Schema::hasTable('trBPB') ? $basePb()->count() : 0;
            $pbWaiting = Schema::hasTable('trBPBDetail') && Schema::hasTable('trBPB')
                ? DB::table('trBPB as pb')
                    ->join('trBPBDetail as d', 'pb.id', '=', 'd.trBPB_id')
                    ->whereIn('pb.status', ['approved', 'in_progress'])
                    ->where(function ($query) {
                        $query->where('pb.is_legacy', false)
                            ->orWhereNull('pb.is_legacy');
                    })
                    ->where('d.fulfillment_status', 'pending')
                    ->distinct('pb.id')
                    ->count('pb.id')
                : 0;

            $stockTotal = $this->countTable('warehouse2_stock');
            $stockQty = Schema::hasTable('warehouse2_stock') && Schema::hasColumn('warehouse2_stock', 'quantity')
                ? (float) DB::table('warehouse2_stock')->sum('quantity')
                : 0;

            return [
                'pb_ready' => (int) $pbReady,
                'pb_waiting' => (int) $pbWaiting,
                'items_pending' => $itemCount('pending'),
                'items_checked' => $itemCount('checked'),
                'items_hold' => $itemCount('hold'),
                'items_rejected' => $itemCount('rejected'),
                'stock_items' => $stockTotal,
                'stock_qty' => $stockQty,
                'erp_recorded' => Schema::hasTable('trBPB') && Schema::hasColumn('trBPB', 'erp_gi_number')
                    ? (clone $basePb())->whereNotNull('erp_gi_number')->where('erp_gi_number', '<>', '')->count()
                    : 0,
                'erp_missing' => Schema::hasTable('trBPB') && Schema::hasColumn('trBPB', 'erp_gi_number')
                    ? (clone $basePb())->where(function ($query) {
                        $query->whereNull('erp_gi_number')
                            ->orWhere('erp_gi_number', '');
                    })->count()
                    : 0,
                'completed_pb' => $this->countWhere('trBPB', 'status', 'completed'),
            ];
        } catch (\Throwable $e) {
            return [
                'pb_ready' => 0,
                'pb_waiting' => 0,
                'items_pending' => 0,
                'items_checked' => 0,
                'items_hold' => 0,
                'items_rejected' => 0,
                'stock_items' => 0,
                'stock_qty' => 0,
                'erp_recorded' => 0,
                'erp_missing' => 0,
                'completed_pb' => 0,
            ];
        }
    }

    private function warehouseRecentPb()
    {
        try {
            if (!Schema::hasTable('trBPB') || !Schema::hasTable('trBPBDetail')) {
                return collect();
            }

            return DB::table('trBPB as pb')
                ->leftJoin('trBPBDetail as d', 'pb.id', '=', 'd.trBPB_id')
                ->select(
                    'pb.id',
                    'pb.nomor_pb',
                    'pb.tanggal_permintaan',
                    'pb.tanggal_diperlukan',
                    'pb.untuk',
                    'pb.dari_gudang',
                    'pb.status',
                    DB::raw('COUNT(d.id) as total_items'),
                    DB::raw("SUM(CASE WHEN d.fulfillment_status = 'pending' THEN 1 ELSE 0 END) as pending_items"),
                    DB::raw("SUM(CASE WHEN d.fulfillment_status = 'checked' THEN 1 ELSE 0 END) as checked_items"),
                    DB::raw("SUM(CASE WHEN d.fulfillment_status = 'hold' THEN 1 ELSE 0 END) as hold_items")
                )
                ->whereIn('pb.status', ['approved', 'in_progress', 'completed'])
                ->where(function ($query) {
                    $query->where('pb.is_legacy', false)
                        ->orWhereNull('pb.is_legacy');
                })
                ->groupBy('pb.id')
                ->orderByDesc('pb.updated_at')
                ->limit(6)
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function percentage(int|float $value, int|float $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0;
    }

    private function warehouseTrend(): array
    {
        $months = [];
        $max = 1;

        for ($i = 5; $i >= 0; $i--) {
            $start = Carbon::now()->subMonths($i)->startOfMonth();
            $end = (clone $start)->endOfMonth();

            try {
                $approved = Schema::hasTable('trBPB')
                    ? DB::table('trBPB')
                        ->whereBetween('tanggal_permintaan', [$start->toDateString(), $end->toDateString()])
                        ->whereIn('status', ['approved', 'in_progress', 'completed'])
                        ->count()
                    : 0;

                $completed = Schema::hasTable('trBPB')
                    ? DB::table('trBPB')
                        ->whereBetween('tanggal_permintaan', [$start->toDateString(), $end->toDateString()])
                        ->where('status', 'completed')
                        ->count()
                    : 0;

                $erpRecorded = Schema::hasTable('trBPB') && Schema::hasColumn('trBPB', 'erp_gi_number')
                    ? DB::table('trBPB')
                        ->whereBetween('tanggal_permintaan', [$start->toDateString(), $end->toDateString()])
                        ->whereIn('status', ['approved', 'in_progress', 'completed'])
                        ->whereNotNull('erp_gi_number')
                        ->where('erp_gi_number', '<>', '')
                        ->count()
                    : 0;
            } catch (\Throwable $e) {
                $approved = 0;
                $completed = 0;
                $erpRecorded = 0;
            }

            $max = max($max, $approved, $completed, $erpRecorded);

            $months[] = [
                'label' => $start->format('M Y'),
                'approved' => $approved,
                'completed' => $completed,
                'erp_recorded' => $erpRecorded,
            ];
        }

        return [
            'max' => $max,
            'items' => $months,
        ];
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
                ->whereBetween($column, $this->dateTimeBounds($start, $end))
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countWhereBetween(string $table, string $whereColumn, mixed $value, ?string $dateColumn, Carbon $start, Carbon $end): int
    {
        try {
            if (
                !$dateColumn
                || !Schema::hasTable($table)
                || !Schema::hasColumn($table, $whereColumn)
                || !Schema::hasColumn($table, $dateColumn)
            ) {
                return 0;
            }

            return (int) DB::table($table)
                ->where($whereColumn, $value)
                ->whereBetween($dateColumn, $this->dateTimeBounds($start, $end))
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function dateTimeBounds(Carbon $start, Carbon $end): array
    {
        return [
            $start->copy()->startOfDay()->toDateTimeString(),
            $end->copy()->endOfDay()->toDateTimeString(),
        ];
    }
}
