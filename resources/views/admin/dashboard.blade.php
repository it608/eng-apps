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
@endphp

<div class="space-y-6">
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

    {{-- Plain metric row, aligned with Stock Sparepart style --}}
    <div class="grid grid-cols-2 gap-5 md:grid-cols-3 xl:grid-cols-6">
        <div>
            <div class="text-sm font-medium text-gray-900">Total PB</div>
            <div class="mt-1 text-xl font-semibold text-gray-900">{{ $formatNumber($summary['pb_total']) }}</div>
            <div class="text-xs text-gray-400">Permintaan barang</div>
        </div>
        <div>
            <div class="text-sm font-medium text-gray-900">PB Pending</div>
            <div class="mt-1 text-xl font-semibold text-yellow-600">{{ $formatNumber($summary['pb_pending']) }}</div>
            <div class="text-xs text-gray-400">Menunggu proses</div>
        </div>
        <div>
            <div class="text-sm font-medium text-gray-900">Total WO</div>
            <div class="mt-1 text-xl font-semibold text-gray-900">{{ $formatNumber($summary['wo_total']) }}</div>
            <div class="text-xs text-gray-400">Semua work order</div>
        </div>
        <div>
            <div class="text-sm font-medium text-gray-900">WO Open</div>
            <div class="mt-1 text-xl font-semibold text-blue-600">{{ $formatNumber($summary['wo_open_progress']) }}</div>
            <div class="text-xs text-gray-400">Open / progress</div>
        </div>
        <div>
            <div class="text-sm font-medium text-gray-900">Data Change</div>
            <div class="mt-1 text-xl font-semibold text-green-600">{{ $formatNumber($summary['audit_today']) }}</div>
            <div class="text-xs text-gray-400">Aktivitas hari ini</div>
        </div>
        <div>
            <div class="text-sm font-medium text-gray-900">Need Attention</div>
            <div class="mt-1 text-xl font-semibold text-red-600">{{ $formatNumber($health['need_attention']) }}</div>
            <div class="text-xs text-gray-400">Rejected / high risk</div>
        </div>
    </div>

    {{-- Health cards --}}
    <div class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
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

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
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
                        <div class="w-5 rounded-t bg-blue-500" style="height: {{ $pbHeight }}%"></div>
                        <div class="w-5 rounded-t bg-green-500" style="height: {{ $woHeight }}%"></div>
                    </div>
                    <div class="mt-3 text-center text-xs font-medium text-gray-700">{{ $month['label'] }}</div>
                    <div class="mt-1 flex justify-center gap-3 text-[11px] text-gray-400">
                        <span>PB {{ $formatNumber($month['pb']) }}</span>
                        <span>WO {{ $formatNumber($month['wo']) }}</span>
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
</div>
@endsection
