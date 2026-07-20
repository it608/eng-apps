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
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0.25rem 0;
        transition: none;
    }
    .report-summary-card:hover {
        box-shadow: none;
        transform: none;
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
    .cost-chart-panel {
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        background: #fff;
    }
    .cost-chart-svg {
        width: 100%;
        min-height: 320px;
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

                    <button
                        type="button"
                        @click="changeTab('costcenter')"
                        :class="activeTab === 'costcenter' ? 'text-blue-600 border-blue-600 bg-blue-50/50' : 'text-gray-600 border-transparent hover:text-blue-600'"
                        class="report-tab-btn">
                        Cost Center
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

                <div x-show="activeTab === 'costcenter'" class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Mode Trend</label>
                        <select
                            x-model="filters.cost_grouping"
                            @change="loadData()"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="auto">Auto</option>
                            <option value="daily">Harian</option>
                            <option value="monthly">Bulanan</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Auto memakai harian untuk range pendek, bulanan untuk range panjang.</p>
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
                    <span x-show="activeTab === 'costcenter'">Sumber: ERP Good Issue read-only</span>
                    <span x-show="!pagination && activeTab !== 'costcenter'">Ringkasan berdasarkan filter periode aktif</span>
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

            {{-- Cost Center Analytics --}}
            <div x-show="activeTab === 'costcenter'" class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <template x-for="serie in costCenter.series" :key="serie.key">
                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="serie.label"></div>
                                    <div class="mt-1 font-mono text-lg font-semibold text-gray-900" x-text="formatCurrency(serie.total_value)"></div>
                                </div>
                                <span class="h-3 w-3 rounded-full" :style="`background:${serie.color}`"></span>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                <span x-text="formatNumber(serie.documents)"></span> dokumen GI ·
                                <span x-text="formatNumber(serie.items)"></span> item
                            </div>
                        </div>
                    </template>

                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Total Engineering</div>
                        <div class="mt-1 font-mono text-lg font-semibold text-emerald-900" x-text="formatCurrency(costCenter.totals.total_value)"></div>
                        <div class="mt-2 text-xs text-emerald-700">
                            <span x-text="formatNumber(costCenter.totals.documents)"></span> dokumen GI
                        </div>
                    </div>
                </div>

                <div class="cost-chart-panel p-5">
                    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Trend Nilai GI per Cost Center Engineering</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Perbandingan <span x-text="(costCenter.grouping_label || 'Bulanan').toLowerCase()"></span> Civil, Maintenance, dan Repair berdasarkan transaksi Good Issue ERP.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs">
                            <template x-for="serie in costCenter.series" :key="`legend-${serie.key}`">
                                <div class="inline-flex items-center gap-2 rounded-full border border-gray-200 px-3 py-1.5">
                                    <span class="h-2.5 w-2.5 rounded-full" :style="`background:${serie.color}`"></span>
                                    <span class="font-medium text-gray-700" x-text="serie.label"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="min-w-[760px]" x-html="chartSvg()"></div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">Cost Center</th>
                                <th class="px-4 py-3 text-right">Total Nilai</th>
                                <th class="px-4 py-3 text-right">Dokumen GI</th>
                                <th class="px-4 py-3 text-right">Item</th>
                                <th class="px-4 py-3 text-right">Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="serie in costCenter.series" :key="`row-${serie.key}`">
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="mr-2 inline-block h-2.5 w-2.5 rounded-full" :style="`background:${serie.color}`"></span>
                                        <span class="font-semibold text-gray-900" x-text="serie.label"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-semibold" x-text="formatCurrency(serie.total_value)"></td>
                                    <td class="px-4 py-3 text-right" x-text="formatNumber(serie.documents)"></td>
                                    <td class="px-4 py-3 text-right" x-text="formatNumber(serie.items)"></td>
                                    <td class="px-4 py-3 text-right" x-text="formatNumber(serie.quantity)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="pagination && !['overview', 'costcenter'].includes(activeTab)" class="px-6 py-4 border-t border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
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
        costCenter: {
            period: { start: '-', end: '-' },
            grouping: 'monthly',
            grouping_label: 'Bulanan',
            labels: [],
            series: [],
            max_value: 1,
            totals: {
                documents: 0,
                items: 0,
                quantity: 0,
                total_value: 0
            }
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
            cost_grouping: 'auto',
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
            ],
            costcenter: [
                { value: 'all', label: 'Semua Status' }
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
                    untuk: this.filters.untuk,
                    cost_grouping: this.filters.cost_grouping
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
                } else if (this.activeTab === 'costcenter') {
                    this.costCenter = result.data || this.costCenter;
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
                untuk: this.filters.untuk,
                cost_grouping: this.filters.cost_grouping
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
                cost_grouping: 'auto',
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

        formatCurrency(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value || 0));
        },

        formatCurrencyShort(value) {
            const number = Number(value || 0);

            if (number >= 1000000000) {
                return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(number / 1000000000) + ' M';
            }

            if (number >= 1000000) {
                return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(number / 1000000) + ' jt';
            }

            if (number >= 1000) {
                return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(number / 1000) + ' rb';
            }

            return 'Rp ' + this.formatNumber(number);
        },

        chartMax() {
            return Math.max(Number(this.costCenter.max_value || 0), 1);
        },

        chartX(index) {
            const total = Math.max((this.costCenter.labels || []).length - 1, 1);
            return 72 + (index * (818 / total));
        },

        chartY(value) {
            return 270 - ((Number(value || 0) / this.chartMax()) * 220);
        },

        chartPointList(serie) {
            return (serie.values || []).map((value, index) => ({
                index,
                value,
                x: this.chartX(index),
                y: this.chartY(value)
            }));
        },

        chartPoints(serie) {
            return this.chartPointList(serie)
                .map(point => `${point.x},${point.y}`)
                .join(' ');
        },

        chartTicks() {
            return [0, 1, 2, 3, 4].map(index => {
                const value = this.chartMax() * ((4 - index) / 4);
                return {
                    index,
                    value,
                    y: 50 + (index * 55)
                };
            });
        },

        chartSvg() {
            const labels = this.costCenter.labels || [];
            const series = this.costCenter.series || [];

            if (!labels.length || !series.length) {
                return `
                    <div class="flex h-[320px] items-center justify-center rounded-lg border border-dashed border-gray-200 text-sm text-gray-500">
                        Belum ada data cost center untuk periode ini.
                    </div>
                `;
            }

            const ticks = this.chartTicks().map(tick => `
                <g>
                    <line x1="72" x2="890" y1="${tick.y}" y2="${tick.y}" stroke="#e5e7eb" stroke-width="1"></line>
                    <text x="62" y="${tick.y + 4}" text-anchor="end" fill="#6b7280" font-size="11">${this.escapeSvg(this.formatCurrencyShort(tick.value))}</text>
                </g>
            `).join('');

            const lines = series.map(serie => {
                const points = this.chartPoints(serie);
                const circles = this.chartPointList(serie).map(point => `
                    <circle cx="${point.x}" cy="${point.y}" r="4" fill="#ffffff" stroke="${serie.color}" stroke-width="2">
                        <title>${this.escapeSvg(`${serie.label} ${labels[point.index]}: ${this.formatCurrency(point.value)}`)}</title>
                    </circle>
                `).join('');

                return `
                    <g>
                        <polyline fill="none" stroke="${serie.color}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="${points}"></polyline>
                        ${circles}
                    </g>
                `;
            }).join('');

            const monthLabels = labels.map((label, index) => `
                <text x="${this.chartX(index)}" y="304" text-anchor="middle" fill="#6b7280" font-size="11">${this.escapeSvg(label)}</text>
            `).join('');

            return `
                <svg class="cost-chart-svg" viewBox="0 0 920 330" role="img" aria-label="Line chart nilai GI cost center Engineering">
                    ${ticks}
                    <line x1="72" x2="890" y1="270" y2="270" stroke="#cbd5e1" stroke-width="1.2"></line>
                    <line x1="72" x2="72" y1="50" y2="270" stroke="#cbd5e1" stroke-width="1.2"></line>
                    ${lines}
                    ${monthLabels}
                </svg>
            `;
        },

        escapeSvg(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
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
