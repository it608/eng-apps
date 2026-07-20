@extends('layouts.admin')

@section('title', 'Dashboard - Engineering Apps')

@section('content')
@php
    $statusClass = function ($status) {
        return match ($status) {
            'pending', 'submitted', 'draft' => 'bg-yellow-50 text-yellow-700 border-yellow-100',
            'approved', 'completed', 'closed' => 'bg-green-50 text-green-700 border-green-100',
            'rejected' => 'bg-red-50 text-red-700 border-red-100',
            'progress', 'in_progress' => 'bg-blue-50 text-blue-700 border-blue-100',
            default => 'bg-gray-50 text-gray-700 border-gray-100',
        };
    };

    $formatDate = function ($value) {
        if (!$value) return '-';
        try {
            return \Carbon\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return '-';
        }
    };

    $formatNumber = fn ($value) => number_format((float) $value, 0, ',', '.');
    $isAdminDashboard = auth()->user()?->role === 'admin';
@endphp

<div class="space-y-6">
    @if(($dashboardMode ?? 'engineering') === 'warehouse')
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Warehouse Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Monitoring second app untuk fulfillment PB dan pencatatan referensi ERP.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('warehouse.pb.index') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                <span>PB Fulfillment</span>
            </a>
            <a href="{{ route('stock.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                <span>Stock Sparepart</span>
            </a>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Fulfillment Overview</h2>
                <p class="text-sm text-gray-500">PB yang sudah disetujui dan siap diproses oleh gudang.</p>
            </div>
            <div class="text-xs text-gray-400">Updated {{ $lastUpdated }}</div>
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-4">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">PB Siap Proses</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $formatNumber($warehouseSummary['pb_ready'] ?? 0) }}</div>
                <div class="text-xs text-gray-400">Approved / in progress / completed</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Menunggu Gudang</div>
                <div class="mt-1 text-2xl font-semibold text-yellow-600">{{ $formatNumber($warehouseSummary['pb_waiting'] ?? 0) }}</div>
                <div class="text-xs text-gray-400">Masih ada item pending</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Item Sudah Dicek</div>
                <div class="mt-1 text-2xl font-semibold text-green-600">{{ $formatNumber($warehouseSummary['items_checked'] ?? 0) }}</div>
                <div class="text-xs text-gray-400">Fulfillment checked</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Hold / Reject</div>
                <div class="mt-1 text-2xl font-semibold text-red-600">{{ $formatNumber(($warehouseSummary['items_hold'] ?? 0) + ($warehouseSummary['items_rejected'] ?? 0)) }}</div>
                <div class="text-xs text-gray-400">Butuh follow up</div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-1">
            <h2 class="text-lg font-semibold text-gray-900">Trend Fulfillment PB</h2>
            <p class="text-sm text-gray-500">PB approved, selesai fulfillment, dan referensi ERP tercatat.</p>
            <div class="mt-5 flex h-36 items-end justify-between gap-3">
                @foreach(($warehouseTrend['items'] ?? []) as $month)
                    @php
                        $maxTrend = max((int) ($warehouseTrend['max'] ?? 1), 1);
                        $approvedHeight = max(($month['approved'] / $maxTrend) * 100, $month['approved'] > 0 ? 8 : 0);
                        $completedHeight = max(($month['completed'] / $maxTrend) * 100, $month['completed'] > 0 ? 8 : 0);
                        $erpHeight = max(($month['erp_recorded'] / $maxTrend) * 100, $month['erp_recorded'] > 0 ? 8 : 0);
                    @endphp
                    <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                        <div class="flex h-24 w-full items-end justify-center gap-1">
                            <div class="w-2 rounded-t bg-blue-500" style="height: {{ $approvedHeight }}%"></div>
                            <div class="w-2 rounded-t bg-green-500" style="height: {{ $completedHeight }}%"></div>
                            <div class="w-2 rounded-t bg-violet-500" style="height: {{ $erpHeight }}%"></div>
                        </div>
                        <div class="truncate text-[11px] font-medium text-gray-500">{{ $month['label'] }}</div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex flex-wrap gap-3 text-[11px] text-gray-500">
                <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-blue-500"></span>Approved</span>
                <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-green-500"></span>Fulfilled</span>
                <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-violet-500"></span>ERP Ref</span>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Pencatatan ERP</h2>
            <p class="text-sm text-gray-500">Kontrol nomor Good Issue ERP untuk PB yang diproses di gudang.</p>
            <div class="mt-5 grid grid-cols-2 gap-4">
                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Sudah Dicatat</div>
                    <div class="mt-1 text-xl font-semibold text-green-600">{{ $formatNumber($warehouseSummary['erp_recorded'] ?? 0) }}</div>
                </div>
                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Belum Ada Ref</div>
                    <div class="mt-1 text-xl font-semibold text-yellow-600">{{ $formatNumber($warehouseSummary['erp_missing'] ?? 0) }}</div>
                </div>
            </div>
            <p class="mt-4 text-xs leading-5 text-gray-500">Receiving dan issuing tetap dilakukan di ERP. Di sini hanya dicatat referensinya agar PB di e-Request punya jejak fulfillment.</p>
            <a href="{{ route('warehouse.pb.index') }}" class="mt-4 inline-flex text-sm font-medium text-blue-600 hover:text-blue-700">Cek PB fulfillment</a>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Item Fulfillment</h2>
            <p class="text-sm text-gray-500">Status detail item dari PB approved.</p>
            <div class="mt-5 space-y-3 text-sm">
                <div class="flex items-center justify-between"><span class="text-gray-500">Pending</span><span class="font-semibold text-yellow-600">{{ $formatNumber($warehouseSummary['items_pending'] ?? 0) }}</span></div>
                <div class="flex items-center justify-between"><span class="text-gray-500">Checked</span><span class="font-semibold text-green-600">{{ $formatNumber($warehouseSummary['items_checked'] ?? 0) }}</span></div>
                <div class="flex items-center justify-between"><span class="text-gray-500">Hold</span><span class="font-semibold text-orange-600">{{ $formatNumber($warehouseSummary['items_hold'] ?? 0) }}</span></div>
                <div class="flex items-center justify-between"><span class="text-gray-500">Rejected</span><span class="font-semibold text-red-600">{{ $formatNumber($warehouseSummary['items_rejected'] ?? 0) }}</span></div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">PB Menunggu Fulfillment</h2>
                <p class="text-sm text-gray-500">Daftar PB approved terbaru yang menjadi pekerjaan gudang.</p>
            </div>
            <a href="{{ route('warehouse.pb.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-5 py-3">No. PB</th>
                        <th class="px-5 py-3">Diperlukan</th>
                        <th class="px-5 py-3">Tujuan</th>
                        <th class="px-5 py-3">Item</th>
                        <th class="px-5 py-3">Status Gudang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($warehouseRecentPb as $pb)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-gray-900">{{ $pb->nomor_pb }}</div>
                                <div class="text-xs text-gray-400">{{ $formatDate($pb->tanggal_permintaan) }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-700">{{ $formatDate($pb->tanggal_diperlukan) }}</td>
                            <td class="px-5 py-4">
                                <div class="capitalize text-gray-900">{{ $pb->untuk ?: '-' }}</div>
                                <div class="text-xs text-gray-400">{{ $pb->dari_gudang ?: '-' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-900">{{ $formatNumber($pb->total_items) }} item</div>
                                <div class="text-xs text-gray-400">Pending {{ $formatNumber($pb->pending_items) }} · Checked {{ $formatNumber($pb->checked_items) }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full border px-2 py-1 text-xs font-medium {{ ((int) $pb->pending_items > 0) ? 'border-yellow-100 bg-yellow-50 text-yellow-700' : 'border-green-100 bg-green-50 text-green-700' }}">
                                    {{ ((int) $pb->pending_items > 0) ? 'Menunggu Proses' : 'Sudah Dicek' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">Belum ada PB yang perlu diproses gudang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    {{-- Header --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Overview aktivitas Engineering Apps dan pekerjaan operasional.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('transaksi.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                <span>Permintaan Barang</span>
            </a>
            <a href="{{ route('workorder.index') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                <span>Work Order</span>
            </a>
        </div>
    </div>

    {{-- Operational scorecard --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('dashboard') }}"
              class="mb-5 flex flex-col gap-4 rounded-xl border border-blue-100 bg-blue-50/45 px-4 py-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="text-sm font-semibold text-gray-900">Filter Periode PB & WO</div>
                <div class="mt-1 text-xs text-gray-500">
                    Data ditampilkan untuk {{ $dashboardPeriod['label'] ?? 'Year to Date' }}.
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div>
                    <label for="period_mode" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Mode</label>
                    <select id="period_mode"
                            name="period_mode"
                            onchange="document.getElementById('periodMonthWrap').classList.toggle('hidden', this.value !== 'month')"
                            class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="ytd" @selected(($dashboardPeriod['mode'] ?? 'ytd') === 'ytd')>Year to Date</option>
                        <option value="month" @selected(($dashboardPeriod['mode'] ?? 'ytd') === 'month')>Per Bulan</option>
                    </select>
                </div>
                <div id="periodMonthWrap" class="{{ ($dashboardPeriod['mode'] ?? 'ytd') === 'month' ? '' : 'hidden' }}">
                    <label for="period_month" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Bulan</label>
                    <select id="period_month"
                            name="period_month"
                            class="h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @for($monthIndex = 1; $monthIndex <= 12; $monthIndex++)
                            @php
                                $monthValue = now()->format('Y') . '-' . str_pad((string) $monthIndex, 2, '0', STR_PAD_LEFT);
                                $monthLabel = \Carbon\Carbon::create(now()->year, $monthIndex, 1)->translatedFormat('F');
                            @endphp
                            <option value="{{ $monthValue }}" @selected(($dashboardPeriod['month'] ?? now()->format('Y-m')) === $monthValue)>
                                {{ $monthLabel }}
                            </option>
                        @endfor
                    </select>
                </div>
                <button type="submit"
                        class="inline-flex h-10 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Terapkan
                </button>
            </div>
        </form>

        <div class="grid gap-6 xl:grid-cols-[1fr_auto_1fr]">
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Permintaan Barang</h2>
                        <p class="text-xs text-gray-400">Status PB yang sedang berjalan.</p>
                    </div>
                    <a href="{{ route('transaksi.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">Review PB</a>
                </div>
                <div class="grid grid-cols-2 gap-5 sm:grid-cols-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900">{{ $formatNumber($summary['pb_total']) }}</div>
                        <div class="text-xs text-gray-400">Semua PB</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Pending</div>
                        <div class="mt-1 text-xl font-semibold text-yellow-600">{{ $formatNumber($summary['pb_pending']) }}</div>
                        <div class="text-xs text-gray-400">Menunggu proses</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Approved</div>
                        <div class="mt-1 text-xl font-semibold text-green-600">{{ $formatNumber($summary['pb_approved']) }}</div>
                        <div class="text-xs text-gray-400">Disetujui</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Rejected</div>
                        <div class="mt-1 text-xl font-semibold text-red-600">{{ $formatNumber($summary['pb_rejected']) }}</div>
                        <div class="text-xs text-gray-400">Ditolak</div>
                    </div>
                </div>
            </div>

            <div class="hidden w-px bg-gray-200 xl:block"></div>
            <div class="h-px bg-gray-200 xl:hidden"></div>

            <div>
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Work Order</h2>
                        <p class="text-xs text-gray-400">Status WO dan progres pekerjaan.</p>
                    </div>
                    <a href="{{ route('workorder.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">Review WO</a>
                </div>
                <div class="grid grid-cols-2 gap-5 sm:grid-cols-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total</div>
                        <div class="mt-1 text-xl font-semibold text-gray-900">{{ $formatNumber($summary['wo_total']) }}</div>
                        <div class="text-xs text-gray-400">Semua WO</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Open</div>
                        <div class="mt-1 text-xl font-semibold text-blue-600">{{ $formatNumber($summary['wo_open_progress']) }}</div>
                        <div class="text-xs text-gray-400">Open / progress</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Submitted</div>
                        <div class="mt-1 text-xl font-semibold text-yellow-600">{{ $formatNumber($summary['wo_submitted']) }}</div>
                        <div class="text-xs text-gray-400">Menunggu approval</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Completed</div>
                        <div class="mt-1 text-xl font-semibold text-green-600">{{ $formatNumber($summary['wo_completed']) }}</div>
                        <div class="text-xs text-gray-400">Selesai</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Health cards --}}
    <div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Operational Health</h2>
                    <p class="text-sm text-gray-500">Ringkasan status utama PB dan WO.</p>
                </div>
                <div class="text-xs text-gray-400">Updated {{ $lastUpdated }}</div>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-900">PB Approval Rate</div>
                            <div class="text-xs text-gray-400">Approved dibanding total PB</div>
                        </div>
                        <div class="text-lg font-semibold text-green-600">{{ $health['pb_completion_rate'] }}%</div>
                    </div>
                    <div class="mt-4 h-2 rounded-full bg-gray-100">
                        <div class="h-2 rounded-full bg-green-500" style="width: {{ min($health['pb_completion_rate'], 100) }}%"></div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-900">WO Completion Rate</div>
                            <div class="text-xs text-gray-400">Completed dibanding total WO</div>
                        </div>
                        <div class="text-lg font-semibold text-blue-600">{{ $health['wo_completion_rate'] }}%</div>
                    </div>
                    <div class="mt-4 h-2 rounded-full bg-gray-100">
                        <div class="h-2 rounded-full bg-blue-500" style="width: {{ min($health['wo_completion_rate'], 100) }}%"></div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="text-sm font-medium text-gray-900">PB Status</div>
                    <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <div class="font-semibold text-yellow-600">{{ $formatNumber($summary['pb_pending']) }}</div>
                            <div class="text-xs text-gray-400">Pending</div>
                        </div>
                        <div>
                            <div class="font-semibold text-green-600">{{ $formatNumber($summary['pb_approved']) }}</div>
                            <div class="text-xs text-gray-400">Approved</div>
                        </div>
                        <div>
                            <div class="font-semibold text-red-600">{{ $formatNumber($summary['pb_rejected']) }}</div>
                            <div class="text-xs text-gray-400">Rejected</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="text-sm font-medium text-gray-900">WO Status</div>
                    <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $formatNumber($summary['wo_draft']) }}</div>
                            <div class="text-xs text-gray-400">Draft</div>
                        </div>
                        <div>
                            <div class="font-semibold text-yellow-600">{{ $formatNumber($summary['wo_submitted']) }}</div>
                            <div class="text-xs text-gray-400">Submitted</div>
                        </div>
                        <div>
                            <div class="font-semibold text-green-600">{{ $formatNumber($summary['wo_completed']) }}</div>
                            <div class="text-xs text-gray-400">Completed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Quick Access</h2>
            <p class="text-sm text-gray-500">Shortcut modul yang sering dipakai.</p>

            <div class="mt-5 space-y-2">
                <a href="{{ route('master.index') }}" class="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3 text-sm transition hover:bg-gray-50">
                    <span class="font-medium text-gray-800">Master Data</span>
                    <span class="text-gray-400">→</span>
                </a>
                <a href="{{ route('stock.index') }}" class="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3 text-sm transition hover:bg-gray-50">
                    <span class="font-medium text-gray-800">Stock Sparepart</span>
                    <span class="text-gray-400">→</span>
                </a>
                <a href="{{ route('transaksi.index') }}" class="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3 text-sm transition hover:bg-gray-50">
                    <span class="font-medium text-gray-800">Transaksi PB</span>
                    <span class="text-gray-400">→</span>
                </a>
                <a href="{{ route('workorder.index') }}" class="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3 text-sm transition hover:bg-gray-50">
                    <span class="font-medium text-gray-800">Work Order</span>
                    <span class="text-gray-400">→</span>
                </a>
                <a href="{{ url('/admin/logs') }}" class="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3 text-sm transition hover:bg-gray-50">
                    <span class="font-medium text-gray-800">Audit Logs</span>
                    <span class="text-gray-400">→</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Trend --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">6 Month Activity Trend</h2>
                <p class="text-sm text-gray-500">Perbandingan jumlah PB dan WO per bulan.</p>
            </div>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-6">
            @foreach($monthlyTrend['items'] as $month)
                @php
                    $pbHeight = $monthlyTrend['max'] > 0 ? max(($month['pb'] / $monthlyTrend['max']) * 100, $month['pb'] > 0 ? 8 : 0) : 0;
                    $woHeight = $monthlyTrend['max'] > 0 ? max(($month['wo'] / $monthlyTrend['max']) * 100, $month['wo'] > 0 ? 8 : 0) : 0;
                @endphp
                <div class="rounded-xl border border-gray-100 p-3">
                    <div class="flex h-28 items-end justify-center gap-2">
                        <div class="w-5 rounded-t bg-green-500" style="height: {{ $pbHeight }}%"></div>
                        <div class="w-5 rounded-t bg-blue-500" style="height: {{ $woHeight }}%"></div>
                    </div>
                    <div class="mt-3 text-center text-xs font-medium text-gray-700">{{ $month['label'] }}</div>
                    <div class="mt-1 flex justify-center gap-3 text-[11px] text-gray-400">
                        <span class="text-green-600">PB {{ $formatNumber($month['pb']) }}</span>
                        <span class="text-blue-600">WO {{ $formatNumber($month['wo']) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Recent tables --}}
    <div class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Recent Permintaan Barang</h2>
                    <p class="text-sm text-gray-500">PB terbaru dari menu Transaksi.</p>
                </div>
                <a href="{{ route('transaksi.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="px-5 py-3">No. PB</th>
                            <th class="px-5 py-3">Requester</th>
                            <th class="px-5 py-3">Item</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentPb as $pb)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-gray-900">{{ $pb->nomor_pb }}</div>
                                    <div class="text-xs text-gray-400">{{ $formatDate($pb->tanggal_permintaan) }}</div>
                                </td>
                                <td class="px-5 py-4 text-gray-700">{{ $pb->requester }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-gray-900">{{ $formatNumber($pb->total_item) }}</div>
                                    <div class="text-xs text-gray-400">Qty {{ number_format((float) $pb->total_qty, 2, ',', '.') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border px-2 py-1 text-xs font-medium {{ $statusClass($pb->status) }}">
                                        {{ ucwords(str_replace('_', ' ', $pb->status ?? '-')) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-gray-500">Belum ada data PB.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Recent Work Order</h2>
                    <p class="text-sm text-gray-500">WO terbaru dan status pengerjaan.</p>
                </div>
                <a href="{{ route('workorder.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="px-5 py-3">Nomor WO</th>
                            <th class="px-5 py-3">Judul</th>
                            <th class="px-5 py-3">Creator</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentWo as $wo)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-gray-900">{{ $wo->nomor }}</div>
                                    <div class="text-xs text-gray-400">{{ $formatDate($wo->created_at) }}</div>
                                </td>
                                <td class="max-w-xs px-5 py-4">
                                    <div class="truncate text-gray-700">{{ $wo->judul }}</div>
                                    <div class="text-xs text-gray-400">{{ $wo->progress_status ? 'Progress: '.ucwords($wo->progress_status) : 'Progress: -' }}</div>
                                </td>
                                <td class="px-5 py-4 text-gray-700">{{ $wo->creator }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border px-2 py-1 text-xs font-medium {{ $statusClass($wo->status) }}">
                                        {{ ucwords(str_replace('_', ' ', $wo->status ?? '-')) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-gray-500">Belum ada data WO.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Audit snapshot --}}
    @if($isAdminDashboard)
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Latest Audit Activity</h2>
                <p class="text-sm text-gray-500">Aktivitas terakhir yang tercatat di Audit Logs.</p>
            </div>
            <a href="{{ url('/admin/logs') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Open logs</a>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($recentLogs as $log)
                <div class="flex items-center justify-between gap-4 px-5 py-4">
                    <div>
                        <div class="text-sm font-medium text-gray-900">
                            {{ $log->user_name ?: 'System' }} · {{ ucwords(str_replace('_', ' ', $log->action)) }}
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ ucwords(str_replace('_', ' ', $log->module)) }} · {{ $log->description ?: '-' }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-400">{{ $formatDate($log->created_at) }}</div>
                        <span class="mt-1 inline-flex rounded-full border px-2 py-1 text-xs font-medium {{ $log->risk_level === 'high' ? 'bg-red-50 text-red-700 border-red-100' : ($log->risk_level === 'medium' ? 'bg-yellow-50 text-yellow-700 border-yellow-100' : 'bg-green-50 text-green-700 border-green-100') }}">
                            {{ ucwords($log->risk_level ?? 'low') }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-gray-500">Belum ada audit activity.</div>
            @endforelse
        </div>
    </div>
    @endif
    @endif
</div>
@endsection
