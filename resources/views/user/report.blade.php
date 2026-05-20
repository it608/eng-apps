@extends('layouts.admin')

@section('title', 'Reports & Analytics - Engineering Apps')



@push('styles')
<style>
    .report-tab-btn{
        padding: 0.75rem 1.25rem;
        font-size: 0.95rem;
        font-weight: 600;
        border-bottom: 2px solid transparent;
        color: #6b7280;
        transition: all .2s ease;
    }
    .report-tab-btn:hover{
        color: #374151;
        border-bottom-color: #d1d5db;
    }
    .report-tab-btn.active{
        color: #2563eb;
        border-bottom-color: #2563eb;
        background: linear-gradient(to bottom, #eff6ff, transparent);
    }

    .report-summary-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
    }
    .report-summary-card:hover {
        border-left-color: #3b82f6;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transform: translateY(-2px);
    }
    .report-summary-card .label {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .report-summary-card .value {
        font-size: 1.5rem;
        line-height: 2rem;
        font-weight: 600;
        margin-top: 0.25rem;
    }

    .report-table-bordered{
        border-collapse: collapse;
    }
    .report-table-bordered th,
    .report-table-bordered td{
        border: 1px solid #d1d5db;
        padding: 0.75rem 1rem;
        font-size: 0.75rem;
        line-height: 1.25rem;
    }
</style>
@endpush

@section('content')
<div x-data="reportCenter()" x-init="init()">
    <div
        x-show="tooltip.visible"
        x-transition.opacity.duration.150ms
        class="fixed z-[9999] w-80 rounded-xl border border-gray-200 bg-white p-4 shadow-xl pointer-events-none"
        :style="`left: ${tooltip.x}px; top: ${tooltip.y}px;`">
        <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-2">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Detail Tujuan</p>
                <p class="mt-1 text-sm font-semibold text-gray-900" x-text="tooltip.untuk || '-'"></p>
            </div>
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z"/>
                </svg>
            </span>
        </div>

        <div class="mt-3 space-y-3">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">No. PB</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-900" x-text="tooltip.nomor || '-'"></p>
            </div>
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Tujuan Detail</p>
                <p class="mt-0.5 break-words text-sm leading-5 text-gray-700" x-text="tooltip.tujuan || '-'"></p>
            </div>
        </div>
    </div>

    <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Reports & Analytics</h1>
            <p class="mt-1 text-sm text-gray-500">Rekap, audit, dan export data Engineering Apps</p>
        </div>

        {{-- Metric Row --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
            <div class="report-summary-card">
                <div class="label">Total PB</div>
                <div class="value text-blue-600" x-text="formatNumber(summary.pb_total)">0</div>
                <div class="text-xs text-gray-500 mt-1">Permintaan barang</div>
            </div>

            <div class="report-summary-card">
                <div class="label">PB Pending</div>
                <div class="value text-amber-600" x-text="formatNumber(summary.pb_pending)">0</div>
                <div class="text-xs text-gray-500 mt-1">Menunggu approval</div>
            </div>

            <div class="report-summary-card">
                <div class="label">PB Approved</div>
                <div class="value text-green-600" x-text="formatNumber(summary.pb_approved)">0</div>
                <div class="text-xs text-gray-500 mt-1">Disetujui</div>
            </div>

            <div class="report-summary-card">
                <div class="label">Total WO</div>
                <div class="value text-blue-600" x-text="formatNumber(summary.wo_total)">0</div>
                <div class="text-xs text-gray-500 mt-1">Work order</div>
            </div>

            <div class="report-summary-card">
                <div class="label">WO Progress</div>
                <div class="value text-orange-600" x-text="formatNumber(summary.wo_progress)">0</div>
                <div class="text-xs text-gray-500 mt-1">Sedang dikerjakan</div>
            </div>

            <div class="report-summary-card">
                <div class="label">WO Closed</div>
                <div class="value text-purple-600" x-text="formatNumber(summary.wo_closed)">0</div>
                <div class="text-xs text-gray-500 mt-1">Selesai / closed</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="mb-4 border-b border-gray-200 flex justify-between items-center">
                <div class="flex -mb-px text-sm font-medium">
                    <button
                        type="button"
                        @click="changeTab('overview')"
                        :class="activeTab === 'overview' ? 'text-blue-600 border-blue-600 bg-blue-50/50' : 'text-gray-600 border-transparent hover:text-blue-600'"
                        class="report-tab-btn">
                        Overview
                    </button>

                    <button
                        type="button"
                        @click="changeTab('transaksi')"
                        :class="activeTab === 'transaksi' ? 'text-blue-600 border-blue-600 bg-blue-50/50' : 'text-gray-600 border-transparent hover:text-blue-600'"
                        class="report-tab-btn">
                        Transaksi
                    </button>

                    <button
                        type="button"
                        @click="changeTab('workorder')"
                        :class="activeTab === 'workorder' ? 'text-blue-600 border-blue-600 bg-blue-50/50' : 'text-gray-600 border-transparent hover:text-blue-600'"
                        class="report-tab-btn">
                        Work Order
                    </button>
                </div>

                <button
                    type="button"
                    @click="exportXlsx()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/>
                    </svg>
                    Export XLSX
                </button>
            </div>

            <div class="mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3">
                    <div class="xl:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/>
                            </svg>
                            <input
                                type="text"
                                x-model.debounce.500ms="filters.search"
                                @input="loadData()"
                                placeholder="Cari nomor, judul, requester..."
                                class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Dari Tanggal</label>
                        <input
                            type="date"
                            x-model="filters.date_from"
                            @change="loadData()"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sampai Tanggal</label>
                        <input
                            type="date"
                            x-model="filters.date_to"
                            @change="loadData()"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select
                            x-model="filters.status"
                            @change="loadData()"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <template x-for="option in statusOptions" :key="option.value">
                                <option :value="option.value" x-text="option.label"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Per Halaman</label>
                        <div class="flex gap-2">
                            <select
                                x-model="filters.per_page"
                                @change="loadData()"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>

                            <button
                                type="button"
                                @click="resetFilters()"
                                class="px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'transaksi'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 mt-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Jenis Pekerjaan</label>
                        <select
                            x-model="filters.jenis_pekerjaan"
                            @change="loadData()"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">Semua Jenis</option>
                            <option value="repair">Repair</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="project">Project</option>
                            <option value="overhaul">Overhaul</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Untuk</label>
                        <select
                            x-model="filters.untuk"
                            @change="loadData()"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">Semua Tujuan</option>
                            <option value="mesin">Mesin</option>
                            <option value="bangunan">Bangunan</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-4 px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg flex items-center justify-between text-xs text-gray-500">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M5 19A9 9 0 0 0 19 5M19 5v6h-6"/>
                    </svg>
                    <span>Terakhir diperbarui: <strong x-text="lastUpdated">-</strong></span>
                </div>

                <div>
                    <span x-show="pagination" x-text="`${formatNumber(pagination?.total || 0)} data ditemukan`"></span>
                    <span x-show="!pagination">Ringkasan berdasarkan filter periode aktif</span>
                </div>
            </div>

            {{-- Overview --}}
            <div x-show="activeTab === 'overview'" class="pt-0">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-semibold text-gray-900">Status Permintaan Barang</h3>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <template x-for="row in overview.pb_status" :key="row.raw">
                                <div class="px-4 py-3 flex items-center justify-between">
                                    <span class="text-sm text-gray-700" x-text="row.label"></span>
                                    <span class="text-sm font-semibold text-gray-900" x-text="formatNumber(row.total)"></span>
                                </div>
                            </template>
                            <div x-show="overview.pb_status.length === 0" class="px-4 py-8 text-center text-sm text-gray-500">
                                Belum ada data PB.
                            </div>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-semibold text-gray-900">Status Work Order</h3>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <template x-for="row in overview.wo_status" :key="row.raw">
                                <div class="px-4 py-3 flex items-center justify-between">
                                    <span class="text-sm text-gray-700" x-text="row.label"></span>
                                    <span class="text-sm font-semibold text-gray-900" x-text="formatNumber(row.total)"></span>
                                </div>
                            </template>
                            <div x-show="overview.wo_status.length === 0" class="px-4 py-8 text-center text-sm text-gray-500">
                                Belum ada data WO.
                            </div>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-semibold text-gray-900">PB Berdasarkan Jenis Pekerjaan</h3>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <template x-for="row in overview.pb_jenis" :key="row.label">
                                <div class="px-4 py-3 flex items-center justify-between">
                                    <span class="text-sm text-gray-700" x-text="row.label"></span>
                                    <span class="text-sm font-semibold text-gray-900" x-text="formatNumber(row.total)"></span>
                                </div>
                            </template>
                            <div x-show="overview.pb_jenis.length === 0" class="px-4 py-8 text-center text-sm text-gray-500">
                                Belum ada data jenis pekerjaan.
                            </div>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h3 class="font-semibold text-gray-900">PB Berdasarkan Tujuan</h3>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <template x-for="row in overview.pb_untuk" :key="row.label">
                                <div class="px-4 py-3 flex items-center justify-between">
                                    <span class="text-sm text-gray-700" x-text="row.label"></span>
                                    <span class="text-sm font-semibold text-gray-900" x-text="formatNumber(row.total)"></span>
                                </div>
                            </template>
                            <div x-show="overview.pb_untuk.length === 0" class="px-4 py-8 text-center text-sm text-gray-500">
                                Belum ada data tujuan.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transaksi --}}
            <div x-show="activeTab === 'transaksi'" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wide text-gray-600">
                            <th class="px-4 py-3 text-left w-14">No</th>
                            <th class="px-4 py-3 text-left">No. PB</th>
                            <th class="px-4 py-3 text-left">Tanggal</th>
                            <th class="px-4 py-3 text-left">Requester</th>
                            <th class="px-4 py-3 text-left">Tujuan</th>
                            <th class="px-4 py-3 text-left">Jenis</th>
                            <th class="px-4 py-3 text-right">Item</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(row, index) in rows" :key="row.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-gray-700" x-text="rowNumber(index)"></td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-gray-900" x-text="row.nomor"></div>
                                    <div class="text-xs text-gray-500" x-text="row.gudang"></div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-gray-900" x-text="row.tanggal"></div>
                                    <div class="text-xs text-gray-500">Diperlukan: <span x-text="row.tanggal_diperlukan"></span></div>
                                </td>
                                <td class="px-4 py-4 text-gray-700" x-text="row.requester"></td>
                                <td class="px-4 py-4">
                                    <div
                                        class="inline-block max-w-sm cursor-help rounded-lg px-2 py-1 -mx-2 transition hover:bg-blue-50 focus:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        tabindex="0"
                                        :title="row.tujuan || '-'"
                                        @mouseenter="showTujuanTooltip($event, row)"
                                        @mousemove="positionTooltip($event)"
                                        @mouseleave="hideTooltip()"
                                        @focus="showTujuanTooltip($event, row)"
                                        @blur="hideTooltip()">
                                        <div class="text-gray-900" x-text="row.untuk"></div>
                                        <div class="text-xs text-gray-500 max-w-xs truncate" x-text="row.tujuan"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-gray-700" x-text="row.jenis_pekerjaan"></td>
                                <td class="px-4 py-4 text-right">
                                    <div class="font-semibold text-gray-900" x-text="formatNumber(row.jumlah_barang)"></div>
                                    <div class="text-xs text-gray-500">Qty: <span x-text="formatNumber(row.total_jumlah)"></span></div>
                                </td>
                                <td class="px-4 py-4">
                                    <span :class="statusClass(row.status)" class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold" x-text="row.status_label"></span>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="!loading && rows.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada data transaksi untuk filter ini.
                            </td>
                        </tr>

                        <tr x-show="loading">
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                Memuat data...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Work Order --}}
            <div x-show="activeTab === 'workorder'" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-wide text-gray-600">
                            <th class="px-4 py-3 text-left w-14">No</th>
                            <th class="px-4 py-3 text-left">Nomor WO</th>
                            <th class="px-4 py-3 text-left">Judul</th>
                            <th class="px-4 py-3 text-left">Dibuat Oleh</th>
                            <th class="px-4 py-3 text-left">Tanggal</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Progress</th>
                            <th class="px-4 py-3 text-left">Lead Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(row, index) in rows" :key="row.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-gray-700" x-text="rowNumber(index)"></td>
                                <td class="px-4 py-4 font-semibold text-gray-900" x-text="row.nomor"></td>
                                <td class="px-4 py-4">
                                    <div class="font-medium text-gray-900" x-text="row.judul"></div>
                                    <div class="text-xs text-gray-500 max-w-sm truncate" x-text="row.deskripsi"></div>
                                </td>
                                <td class="px-4 py-4 text-gray-700" x-text="row.created_by"></td>
                                <td class="px-4 py-4 text-gray-700" x-text="row.tanggal_dibuat"></td>
                                <td class="px-4 py-4">
                                    <span :class="statusClass(row.status)" class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold" x-text="row.status_label"></span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-gray-700" x-text="row.progress_label"></span>
                                </td>
                                <td class="px-4 py-4 text-gray-700" x-text="row.lead_time"></td>
                            </tr>
                        </template>

                        <tr x-show="!loading && rows.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada data work order untuk filter ini.
                            </td>
                        </tr>

                        <tr x-show="loading">
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                Memuat data...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div x-show="pagination && activeTab !== 'overview'" class="px-6 py-4 border-t border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="text-sm text-gray-500">
                    Halaman <span x-text="pagination?.current_page || 1"></span> dari <span x-text="pagination?.last_page || 1"></span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="prevPage()"
                        :disabled="!pagination || pagination.current_page <= 1"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                        Prev
                    </button>

                    <button
                        type="button"
                        @click="nextPage()"
                        :disabled="!pagination || pagination.current_page >= pagination.last_page"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                        Next
                    </button>
                </div>
            </div>
        </div>
</div>
@endsection

@push('scripts')
<script>
function reportCenter() {
    return {
        activeTab: 'overview',
        loading: false,
        rows: [],
        overview: {
            pb_status: [],
            wo_status: [],
            pb_jenis: [],
            pb_untuk: []
        },
        pagination: null,
        summary: {
            pb_total: 0,
            pb_pending: 0,
            pb_approved: 0,
            pb_rejected: 0,
            pb_items: 0,
            pb_qty: 0,
            wo_total: 0,
            wo_draft: 0,
            wo_submitted: 0,
            wo_approved: 0,
            wo_rejected: 0,
            wo_completed: 0,
            wo_open: 0,
            wo_progress: 0,
            wo_closed: 0
        },
        filters: {
            search: '',
            date_from: '',
            date_to: '',
            status: 'all',
            jenis_pekerjaan: 'all',
            untuk: 'all',
            per_page: '20',
            page: 1
        },
        lastUpdated: '-',
        tooltip: {
            visible: false,
            x: 0,
            y: 0,
            nomor: '',
            untuk: '',
            tujuan: ''
        },
        statusSets: {
            overview: [
                { value: 'all', label: 'Semua Status' }
            ],
            transaksi: [
                { value: 'all', label: 'Semua Status' },
                { value: 'pending', label: 'Pending' },
                { value: 'approved', label: 'Approved' },
                { value: 'rejected', label: 'Rejected' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'completed', label: 'Completed' }
            ],
            workorder: [
                { value: 'all', label: 'Semua Status' },
                { value: 'draft', label: 'Draft' },
                { value: 'submitted', label: 'Submitted' },
                { value: 'approved', label: 'Approved' },
                { value: 'rejected', label: 'Rejected' },
                { value: 'completed', label: 'Completed' },
                { value: 'open', label: 'Progress: Open' },
                { value: 'progress', label: 'Progress: Progress' },
                { value: 'closed', label: 'Progress: Closed' }
            ]
        },

        init() {
            this.loadData();
        },

        get statusOptions() {
            return this.statusSets[this.activeTab] || this.statusSets.overview;
        },

        changeTab(tab) {
            this.activeTab = tab;
            this.rows = [];
            this.pagination = null;
            this.filters.status = 'all';
            this.filters.page = 1;
            this.loadData();
        },

        async loadData() {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    tab: this.activeTab,
                    page: this.filters.page,
                    per_page: this.filters.per_page,
                    status: this.filters.status,
                    search: this.filters.search,
                    date_from: this.filters.date_from,
                    date_to: this.filters.date_to,
                    jenis_pekerjaan: this.filters.jenis_pekerjaan,
                    untuk: this.filters.untuk
                });

                const response = await fetch(`/report/data?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Gagal memuat report.');
                }

                this.summary = result.summary || this.summary;

                if (this.activeTab === 'overview') {
                    this.overview = result.data || this.overview;
                    this.rows = [];
                    this.pagination = null;
                } else {
                    this.rows = result.data || [];
                    this.pagination = result.pagination || null;
                }

                this.lastUpdated = new Date().toLocaleTimeString('id-ID');
            } catch (error) {
                console.error('Report load error:', error);
                alert('Gagal memuat report: ' + error.message);
            } finally {
                this.loading = false;
            }
        },

        exportXlsx() {
            const params = new URLSearchParams({
                tab: this.activeTab,
                status: this.filters.status,
                search: this.filters.search,
                date_from: this.filters.date_from,
                date_to: this.filters.date_to,
                jenis_pekerjaan: this.filters.jenis_pekerjaan,
                untuk: this.filters.untuk
            });

            window.location.href = `/report/export?${params.toString()}`;
        },

        resetFilters() {
            this.filters = {
                search: '',
                date_from: '',
                date_to: '',
                status: 'all',
                jenis_pekerjaan: 'all',
                untuk: 'all',
                per_page: '20',
                page: 1
            };

            this.loadData();
        },

        prevPage() {
            if (!this.pagination || this.pagination.current_page <= 1) return;
            this.filters.page = this.pagination.current_page - 1;
            this.loadData();
        },

        nextPage() {
            if (!this.pagination || this.pagination.current_page >= this.pagination.last_page) return;
            this.filters.page = this.pagination.current_page + 1;
            this.loadData();
        },

        showTujuanTooltip(event, row) {
            this.tooltip.nomor = row?.nomor || '-';
            this.tooltip.untuk = row?.untuk || '-';
            this.tooltip.tujuan = row?.tujuan || '-';
            this.tooltip.visible = true;
            this.positionTooltip(event);
        },

        positionTooltip(event) {
            if (!event) return;

            const tooltipWidth = 320;
            const tooltipHeight = 190;
            const gap = 16;
            let x = event.clientX + gap;
            let y = event.clientY + gap;

            if (x + tooltipWidth > window.innerWidth - 12) {
                x = event.clientX - tooltipWidth - gap;
            }

            if (y + tooltipHeight > window.innerHeight - 12) {
                y = event.clientY - tooltipHeight - gap;
            }

            this.tooltip.x = Math.max(12, x);
            this.tooltip.y = Math.max(12, y);
        },

        hideTooltip() {
            this.tooltip.visible = false;
        },

        rowNumber(index) {
            const currentPage = this.pagination?.current_page || 1;
            const perPage = this.pagination?.per_page || parseInt(this.filters.per_page, 10) || 20;

            return ((currentPage - 1) * perPage) + index + 1;
        },

        formatNumber(value) {
            const number = Number(value || 0);
            return new Intl.NumberFormat('id-ID').format(number);
        },

        statusClass(status) {
            const map = {
                pending: 'bg-yellow-100 text-yellow-700',
                approved: 'bg-green-100 text-green-700',
                rejected: 'bg-red-100 text-red-700',
                completed: 'bg-purple-100 text-purple-700',
                submitted: 'bg-blue-100 text-blue-700',
                draft: 'bg-gray-100 text-gray-700',
                in_progress: 'bg-orange-100 text-orange-700',
                open: 'bg-blue-100 text-blue-700',
                progress: 'bg-orange-100 text-orange-700',
                closed: 'bg-purple-100 text-purple-700'
            };

            return map[String(status || '').toLowerCase()] || 'bg-gray-100 text-gray-700';
        }
    };
}
</script>
@endpush
