@extends('layouts.admin')

@section('title', 'Transaksi')

@section('content')

<style>
/* ============ EXISTING STYLES ============ */
.pb-detail-section {
    margin-top: -32px;
}

/* Style untuk scrollbar dropdown */
.max-h-80 {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
}

.max-h-80::-webkit-scrollbar {
    width: 6px;
}

.max-h-80::-webkit-scrollbar-track {
    background: #f7fafc;
    border-radius: 3px;
}

.max-h-80::-webkit-scrollbar-thumb {
    background-color: #cbd5e0;
    border-radius: 3px;
}

/* Animasi untuk dropdown */
[x-cloak] { display: none !important; }

/* Sticky header untuk dropdown */
.sticky {
    position: -webkit-sticky;
    position: sticky;
}

/* High z-index untuk dropdown */
.z-9999 {
    z-index: 9999;
}

/* PERBAIKAN SPACING MODAL */
.modal-content {
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.modal-form-section {
    gap: 1.5rem !important;
}

.form-grid-compact {
    gap: 1rem !important;
}

.compact-input {
    padding-top: 0.5rem !important;
    padding-bottom: 0.5rem !important;
}

.compact-label {
    margin-bottom: 0.5rem !important;
}

/* Perbaikan untuk radio buttons */
.radio-group-compact {
    padding: 1rem !important;
}

.radio-option {
    margin-right: 1.5rem !important;
}

/* Perbaikan untuk table */
.table-header-compact {
    padding-top: 0.75rem !important;
    padding-bottom: 0.75rem !important;
}

.table-cell-compact {
    padding-top: 0.5rem !important;
    padding-bottom: 0.5rem !important;
}

/* Perbaikan untuk action buttons */
.action-buttons-compact {
    padding-top: 1.5rem !important;
}

/* Reduce modal padding */
.modal-padding-compact {
    padding: 1.5rem !important;
}

/* Style untuk detail modal */
.backdrop-blur-sm {
    backdrop-filter: blur(4px);
}

.bg-gradient-to-r {
    background-image: linear-gradient(to right, var(--tw-gradient-stops));
}

.from-primary-600 {
    --tw-gradient-from: #2563eb;
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(37, 99, 235, 0));
}

.to-primary-700 {
    --tw-gradient-to: #1d4ed8;
}

/* Hover effects */
.hover\:shadow-md:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.transition {
    transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.duration-200 {
    transition-duration: 200ms;
}

.shadow-2xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Animation spin */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
.animate-spin {
    animation: spin 1s linear infinite;
}

/* Search dropdown positioning */
.search-dropdown {
    position: absolute;
    z-index: 9999;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    max-height: 16rem;
    overflow-y: auto;
}

/* Notifikasi */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 99999;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.notification-success {
    background: linear-gradient(to right, #10b981, #059669);
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
}

.notification-error {
    background: linear-gradient(to right, #ef4444, #dc2626);
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
}

.notification-info {
    background: linear-gradient(to right, #3b82f6, #2563eb);
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
}

/* Refresh button animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}
.animate-pulse-slow {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Last refresh indicator */
.last-refresh {
    font-size: 0.75rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
</style>

{{-- PASS MASTER BARANG KE JAVASCRIPT UNTUK FALLBACK --}}
@php
    $transaksiData = $transaksi ?? [];
@endphp

<script>
    window.masterBarang = @json($barang ?? []);
    window.csrfToken = '{{ csrf_token() }}';
    window.baseUrl = '{{ url('/') }}';
    window.userRole = '{{ auth()->user()->role }}';    
</script>

{{-- Alpine JS --}}
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

{{-- PAGE HEADER --}}
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-800">
        Transaksi
    </h1>
    <p class="text-sm text-gray-500 mt-1">
        Kelola dan lihat transaksi anda
    </p>
</div>

{{-- NOTIFICATION COMPONENT --}}
<div x-data="notificationApp()" x-show="show" x-cloak class="notification" @click="show = false">
    <div :class="'notification-' + type">
        <svg x-show="type === 'success'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <svg x-show="type === 'error'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <svg x-show="type === 'info'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="flex-1">
            <p class="font-medium" x-text="message"></p>
        </div>
        <button class="text-white/80 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>

{{-- MAIN APP --}}
<div x-data="transaksiApp()" x-init="init()" @click.away="closeAllDropdowns" class="relative">

    {{-- TAB BUTTON --}}
    <div class="flex gap-2 mb-4 border-b">
        <button
            @click="tab = 'all'"
            :class="tab === 'all'
                ? 'border-b-2 border-primary-600 text-primary-600'
                : 'text-gray-500'"
            class="px-4 py-2 text-sm font-medium">
            Permintaan Barang (PB)
        </button>

        <button
            @click="tab = 'done'"
            :class="tab === 'done'
                ? 'border-b-2 border-primary-600 text-primary-600'
                : 'text-gray-500'"
            class="px-4 py-2 text-sm font-medium">
            Riwayat
        </button>
    </div>

    {{-- TAB CONTENT --}}
    <div class="bg-white rounded-xl shadow-sm border">

        {{-- TAB: TRANSAKSI PERMINTAAN BARANG --}}
        <div x-show="tab === 'all'" x-cloak>

            {{-- HEADER SECTION --}}
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold text-gray-800">
                    Transaksi Permintaan Barang
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Daftar permintaan barang yang perlu diproses
                </p>
            </div>

            {{-- FILTER SECTION --}}
            <div class="p-6 border-b">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    {{-- SEARCH INPUT --}}
                    <div class="relative w-full md:w-80">
                        <div class="relative">
                            <input
                                type="text"
                                x-model="searchQuery"
                                @input.debounce.500ms="searchTransaksi()"
                                @focus="isSearchFocused = true"
                                @keydown.escape="isSearchFocused = false"
                                placeholder="Cari nomor PB, untuk, gudang, status..."
                                class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            >
                            <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            
                            {{-- CLEAR SEARCH BUTTON --}}
                            <button 
                                x-show="searchQuery"
                                @click="searchQuery = ''; searchTransaksi()"
                                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            
                            {{-- LOADING INDICATOR --}}
                            <div x-show="isSearchingTransaksi" class="absolute right-3 top-2.5">
                                <svg class="animate-spin h-5 w-5 text-primary-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- FILTER BUTTONS --}}
                    <div class="flex items-center gap-3 flex-wrap">
                        {{-- STATUS FILTER --}}
                        <div class="relative">
                            <select x-model="statusFilter" @change="filterTransaksi()" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent min-w-[150px]">
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Disetujui</option>
                                <option value="rejected">Ditolak</option>
                                <option value="in_progress">Diproses</option>
                                <option value="completed">Selesai</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                                </svg>
                            </div>
                        </div>

                        {{-- DATE FILTER --}}
                        <div class="relative">
                            <select x-model="dateFilter" @change="filterTransaksi()" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent min-w-[150px]">
                                <option value="">Semua Tanggal</option>
                                <option value="today">Hari Ini</option>
                                <option value="week">Minggu Ini</option>
                                <option value="month">Bulan Ini</option>
                                <option value="year">Tahun Ini</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                                </svg>
                            </div>
                        </div>

                        {{-- PERPAGE FILTER --}}
                        <div class="relative">
                            <select x-model="perPage" @change="currentPage = 1" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="5">5 / halaman</option>
                                <option value="10">10 / halaman</option>
                                <option value="25">25 / halaman</option>
                                <option value="50">50 / halaman</option>
                                <option value="100">100 / halaman</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                                </svg>
                            </div>
                        </div>

                        {{-- REFRESH BUTTON --}}
                        <button 
                            @click="refreshData()"
                            :disabled="isRefreshing"
                            class="bg-blue-50 hover:bg-blue-100 text-blue-600 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition duration-200 border border-blue-200 hover:border-blue-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Refresh Data (Ctrl+R)">
                            <svg x-show="!isRefreshing" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg x-show="isRefreshing" class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-show="!isRefreshing">Refresh</span>
                            <span x-show="isRefreshing">Memperbarui...</span>
                        </button>

                        {{-- ADD BUTTON --}}
                        <button 
                            @click="showCreateModal = true"
                            class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition duration-200 shadow-sm hover:shadow">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Buat Permintaan
                        </button>
                        
                        {{-- RESET FILTER BUTTON --}}
                        <button 
                            x-show="searchQuery || statusFilter || dateFilter || perPage !== 10"
                            @click="resetFilters()"
                            class="text-gray-600 hover:text-gray-800 px-3 py-2 text-sm flex items-center gap-1 border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reset
                        </button>
                    </div>
                </div>
                
                {{-- ACTIVE FILTERS --}}
                <div x-show="searchQuery || statusFilter || dateFilter" x-cloak class="flex items-center gap-2 mt-3">
                    <span class="text-xs text-gray-500">Filter aktif:</span>
                    <div class="flex flex-wrap gap-2">
                        <span x-show="searchQuery" class="inline-flex items-center gap-1 px-2 py-1 bg-primary-50 text-primary-700 rounded-md text-xs">
                            Pencarian: "<span x-text="searchQuery"></span>"
                            <button @click="searchQuery = ''; searchTransaksi()" class="hover:text-primary-900">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                        
                        <span x-show="statusFilter" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 rounded-md text-xs">
                            Status: <span x-text="statusFilter.replace('_', ' ')"></span>
                            <button @click="statusFilter = ''; filterTransaksi()" class="hover:text-blue-900">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                        
                        <span x-show="dateFilter" class="inline-flex items-center gap-1 px-2 py-1 bg-green-50 text-green-700 rounded-md text-xs">
                            Tanggal: 
                            <span x-show="dateFilter === 'today'">Hari Ini</span>
                            <span x-show="dateFilter === 'week'">Minggu Ini</span>
                            <span x-show="dateFilter === 'month'">Bulan Ini</span>
                            <span x-show="dateFilter === 'year'">Tahun Ini</span>
                            <button @click="dateFilter = ''; filterTransaksi()" class="hover:text-green-900">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    </div>
                </div>
            </div>

            {{-- LAST REFRESH INDICATOR --}}
            <div x-show="lastRefresh" x-cloak class="flex items-center justify-between px-6 py-2 bg-gray-50 border-b text-xs">
                <div class="flex items-center gap-2 text-gray-500">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Terakhir diperbarui: <span x-text="formatTime(lastRefresh)" class="font-medium text-gray-700"></span></span>
                    <span x-show="autoRefreshEnabled" class="ml-2 px-1.5 py-0.5 bg-green-100 text-green-700 rounded-full text-[10px] font-medium">
                        Auto-refresh ON
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-gray-400" x-text="`${filteredTransaksi.length} data ditemukan`"></span>
                    <button @click="toggleAutoRefresh()" 
                            class="px-2 py-0.5 rounded text-[10px] font-medium transition duration-200"
                            :class="autoRefreshEnabled ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                        <span x-show="autoRefreshEnabled">Auto-refresh ON</span>
                        <span x-show="!autoRefreshEnabled">Auto-refresh OFF</span>
                    </button>
                </div>
            </div>

            {{-- TABLE SECTION --}}
            <div class="overflow-x-auto relative">
                {{-- LOADING OVERLAY --}}
                <div x-show="isSearchingTransaksi || isRefreshing" 
                     x-cloak
                     class="absolute inset-0 bg-white bg-opacity-75 z-40 flex items-center justify-center">
                    <div class="flex flex-col items-center gap-2">
                        <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm text-gray-600">
                            <span x-show="isRefreshing">Memperbarui data...</span>
                            <span x-show="isSearchingTransaksi">Memuat data...</span>
                        </span>
                    </div>
                </div>

                {{-- NO DATA MESSAGE --}}
                <div x-show="!isSearchingTransaksi && !isRefreshing && filteredTransaksi.length === 0" 
                     x-cloak
                     class="py-12 px-6 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <svg class="h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <p class="text-gray-600 font-medium text-lg">Tidak ada data ditemukan</p>
                        <p class="text-sm text-gray-500 max-w-md">
                            <span x-show="searchQuery">Tidak ada permintaan barang dengan kata kunci "<span x-text="searchQuery" class="font-semibold"></span>"</span>
                            <span x-show="!searchQuery && (statusFilter || dateFilter)">Tidak ada permintaan barang dengan filter yang dipilih</span>
                            <span x-show="!searchQuery && !statusFilter && !dateFilter">Belum ada data permintaan barang</span>
                        </p>
                        <button 
                            x-show="searchQuery || statusFilter || dateFilter"
                            @click="resetFilters()"
                            class="mt-2 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition duration-200">
                            Reset Filter
                        </button>
                    </div>
                </div>

                {{-- TABLE --}}
                <table x-show="!isSearchingTransaksi && !isRefreshing && filteredTransaksi.length > 0" class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                <div class="flex items-center gap-1">
                                    No.
                                </div>
                            </th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center gap-1">
                                    No. PB
                                    <button @click="sortBy('nomor_pb')" class="hover:text-gray-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                        </svg>
                                    </button>
                                </div>
                            </th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="flex items-center gap-1">
                                    Tanggal
                                    <button @click="sortBy('tanggal_permintaan')" class="hover:text-gray-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                        </svg>
                                    </button>
                                </div>
                            </th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Untuk
                            </th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Gudang
                            </th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Barang
                            </th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="py-4 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="(tr, index) in paginatedTransaksi" :key="tr.id">
                            <tr class="hover:bg-gray-50 transition duration-150" 
                                :class="{'bg-yellow-50': index % 2 === 0}">
                                {{-- NOMOR URUT --}}
                                <td class="py-4 px-6">
                                    <div class="text-sm font-medium text-gray-900" x-text="((currentPage - 1) * perPage) + (index + 1)"></div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-sm font-medium text-gray-900" x-text="tr.nomor_pb"></div>
                                    <div class="text-xs text-gray-500" x-text="tr.jenis_pekerjaan || '-'"></div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-sm text-gray-900" x-text="formatDateForDisplay(tr.tanggal_permintaan)"></div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <span class="font-medium">Diperlukan:</span> 
                                        <span x-text="formatDateForDisplay(tr.tanggal_diperlukan)"></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-sm text-gray-900 capitalize" x-text="tr.untuk || '-'"></div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-sm text-gray-900" x-text="formatGudang(tr.dari_gudang)"></div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-sm text-gray-900" x-text="tr.jumlah_barang + ' item'"></div>
                                    <div class="text-xs text-gray-500" x-text="'Total: ' + formatNumber(tr.total_jumlah)"></div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-3 py-1 text-xs font-medium rounded-full inline-flex items-center gap-1"
                                          :class="{
                                              'bg-yellow-100 text-yellow-800': tr.status === 'pending',
                                              'bg-blue-100 text-blue-800': tr.status === 'approved',
                                              'bg-red-100 text-red-800': tr.status === 'rejected',
                                              'bg-purple-100 text-purple-800': tr.status === 'in_progress',
                                              'bg-green-100 text-green-800': tr.status === 'completed',
                                              'bg-gray-100 text-gray-800': !tr.status
                                          }">
                                        <span class="w-1.5 h-1.5 rounded-full" 
                                              :class="{
                                                  'bg-yellow-500': tr.status === 'pending',
                                                  'bg-blue-500': tr.status === 'approved',
                                                  'bg-red-500': tr.status === 'rejected',
                                                  'bg-purple-500': tr.status === 'in_progress',
                                                  'bg-green-500': tr.status === 'completed',
                                                  'bg-gray-500': !tr.status
                                              }"></span>
                                        <span x-text="tr.status ? tr.status.charAt(0).toUpperCase() + tr.status.slice(1).replace('_', ' ') : '-'"></span>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        {{-- TOMBOL DETAIL (SEMUA ROLE) --}}
                                        <button @click="showDetail(tr.id)" 
                                                class="text-primary-600 hover:text-primary-800 p-1.5 rounded-lg hover:bg-primary-50 transition duration-200"
                                                title="Lihat Detail">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        
                                        {{-- TOMBOL CETAK (SEMUA ROLE) --}}
                                        <button @click="printTransaksi(tr)" 
                                                class="text-gray-600 hover:text-gray-800 p-1.5 rounded-lg hover:bg-gray-100 transition duration-200"
                                                title="Cetak">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </button>
                                        
                                        {{-- TOMBOL APPROVAL - HANYA UNTUK ROLE APPROVAL DAN STATUS PENDING --}}
                                        <template x-if="userRole === 'approval' && tr.status === 'pending'">
                                            <div class="flex items-center gap-1 border-l pl-2 ml-1">
                                                {{-- Approve Button --}}
                                                <button @click="approveRequest(tr)" 
                                                        class="text-green-600 hover:text-green-800 p-1.5 rounded-lg hover:bg-green-50 transition duration-200"
                                                        title="Setujui">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                                
                                                {{-- Reject Button --}}
                                                <button @click="showRejectModal(tr)" 
                                                        class="text-red-600 hover:text-red-800 p-1.5 rounded-lg hover:bg-red-50 transition duration-200"
                                                        title="Tolak">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                        
                                        {{-- Status badge untuk yang sudah di-approve/reject --}}
                                        <template x-if="tr.status === 'approved'">
                                            <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full flex items-center gap-1">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-5m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Disetujui
                                            </span>
                                        </template>
                                        
                                        <template x-if="tr.status === 'rejected'">
                                            <span class="text-xs text-red-600 bg-red-50 px-2 py-1 rounded-full flex items-center gap-1">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Ditolak
                                            </span>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div x-show="filteredTransaksi.length > 0" class="p-6 border-t">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="text-sm text-gray-500">
                        <span class="font-medium" x-text="filteredTransaksi.length"></span> data ditemukan
                        <span x-show="searchQuery || statusFilter || dateFilter" class="text-gray-400">
                            (filter aktif)
                        </span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500">
                            Halaman <span x-text="currentPage"></span> dari <span x-text="totalPages"></span>
                        </span>
                        
                        <div class="flex items-center gap-1">
                            <button 
                                @click="currentPage = 1"
                                :disabled="currentPage === 1"
                                class="px-2 py-1 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Halaman Pertama">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                </svg>
                            </button>
                            
                            <button 
                                @click="currentPage > 1 ? currentPage-- : null"
                                :disabled="currentPage === 1"
                                class="px-2 py-1 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Previous
                            </button>
                            
                            <template x-for="page in getPaginationPages()" :key="page">
                                <button 
                                    x-show="page !== '...'"
                                    @click="currentPage = page"
                                    :class="{
                                        'bg-primary-600 text-white hover:bg-primary-700': currentPage === page,
                                        'border border-gray-300 text-gray-700 hover:bg-gray-50': currentPage !== page
                                    }"
                                    class="px-3 py-1 rounded text-sm min-w-[32px]">
                                    <span x-text="page"></span>
                                </button>
                                <span x-show="page === '...'" class="px-2 py-1 text-gray-500">...</span>
                            </template>
                            
                            <button 
                                @click="currentPage < totalPages ? currentPage++ : null"
                                :disabled="currentPage === totalPages"
                                class="px-2 py-1 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Next
                            </button>
                            
                            <button 
                                @click="currentPage = totalPages"
                                :disabled="currentPage === totalPages"
                                class="px-2 py-1 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Halaman Terakhir">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB: RIWAYAT --}}
        <div x-show="tab === 'done'" x-cloak>
            {{-- HEADER SECTION --}}
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold text-gray-800">
                    Riwayat Transaksi
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Daftar transaksi yang telah selesai
                </p>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b text-left bg-gray-50">
                                <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase w-16">No.</th>
                                <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase">No. PB</th>
                                <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase">Untuk</th>
                                <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase">Barang</th>
                                <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="py-3 px-4 text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(tr, index) in completedTransaksi" :key="tr.id">
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4 text-sm font-medium text-gray-900" x-text="index + 1"></td>
                                    <td class="py-3 px-4 text-sm" x-text="tr.nomor_pb"></td>
                                    <td class="py-3 px-4 text-sm" x-text="formatDateForDisplay(tr.tanggal_permintaan)"></td>
                                    <td class="py-3 px-4 text-sm capitalize" x-text="tr.untuk"></td>
                                    <td class="py-3 px-4 text-sm" x-text="tr.jumlah_barang + ' item'"></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">
                                            Selesai
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <button @click="showDetail(tr.id)" class="text-primary-600 hover:text-primary-800">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="completedTransaksi.length === 0">
                                <td colspan="7" class="py-8 px-4 text-center text-gray-500">
                                    Belum ada riwayat transaksi selesai
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL BUAT PERMINTAAN --}}
    <div x-show="showCreateModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true"
         @keydown.escape.window="showCreateModal = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            {{-- BACKGROUND OVERLAY --}}
            <div x-show="showCreateModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                 aria-hidden="true">
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- MODAL CONTENT --}}
            <div x-show="showCreateModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="modal-content modal-padding-compact inline-block w-full max-w-7xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-lg shadow-xl">
                
                {{-- MODAL HEADER --}}
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">
                            BON PERMINTAAN BARANG
                        </h3>
                        <div class="mt-1 flex items-center gap-4 text-xs text-gray-600">
                            <div class="flex items-center gap-1">
                                <span class="font-medium">Bagian:</span>
                                <span class="bg-gray-100 px-2 py-0.5 rounded text-xs">Engineering</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="font-medium">Tanggal Permintaan:</span>
                                <span x-text="tanggalFormat" class="bg-gray-100 px-2 py-0.5 rounded text-xs"></span>
                            </div>
                        </div>
                    </div>
                    <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-500 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- MODAL FORM --}}
                <form @submit.prevent="submitForm">
                    <div class="space-y-6 modal-form-section">
                        {{-- FORM HEADER SECTION --}}
                        <div class="grid grid-cols-1 xl:grid-cols-4 gap-4 form-grid-compact">
                        
                            {{-- NOMOR --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1 compact-label">
                                    Nomor
                                </label>
                                <input type="text"
                                       x-model="formData.nomor_pb"
                                       readonly
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-800 font-mono text-sm tracking-wide compact-input">
                            </div> 
                        
                            {{-- UNTUK (JENIS) --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1 compact-label">
                                    Untuk
                                </label>
                                <select x-model="formData.untuk" 
                                        @change="handleUntukChange()"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input">
                                    <option value="">-- Pilih --</option>
                                    <option value="mesin">Mesin</option>
                                    <option value="bangunan">Bangunan (Building)</option>
                                    
                                    
                                </select>
                            </div>

                            {{-- DROPDOWN MESIN - TAMPIL KALAU PILIH MESIN --}}
                            <div x-show="formData.untuk === 'mesin'" x-cloak class="col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1 compact-label">
                                    Pilih Mesin
                                </label>
                                <div class="relative">
                                    <select x-model="formData.untuk_id" 
                                            @change="handleUntukIdChange()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input appearance-none bg-white">
                                        <option value="">-- Pilih Mesin --</option>
                                        <template x-for="item in untukList" :key="item.id">
                                            <option :value="item.id" x-text="item.nama + (item.kode ? ' (' + item.kode + ')' : '')"></option>
                                        </template>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                    
                                    {{-- Loading indicator --}}
                                    <div x-show="isLoadingUntuk" class="absolute right-8 top-1/2 transform -translate-y-1/2">
                                        <svg class="animate-spin h-4 w-4 text-primary-600" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {{-- DROPDOWN BANGUNAN - TAMPIL KALAU PILIH BANGUNAN --}}
                            <div x-show="formData.untuk === 'bangunan'" x-cloak class="col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1 compact-label">
                                    Pilih Bangunan
                                </label>
                                <div class="relative">
                                    <select x-model="formData.untuk_id" 
                                            @change="handleUntukIdChange()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input appearance-none bg-white">
                                        <option value="">-- Pilih Bangunan --</option>
                                        <template x-for="item in untukList" :key="item.id">
                                            <option :value="item.id" x-text="item.nama + (item.kode ? ' (' + item.kode + ')' : '')"></option>
                                        </template>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                    
                                    {{-- Loading indicator --}}
                                    <div x-show="isLoadingUntuk" class="absolute right-8 top-1/2 transform -translate-y-1/2">
                                        <svg class="animate-spin h-4 w-4 text-primary-600" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {{-- KALAU BUKAN MESIN ATAU BANGUNAN, KOSONGKAN 2 KOLOM --}}
                            <div x-show="formData.untuk !== 'mesin' && formData.untuk !== 'bangunan'" class="col-span-2"></div>
                        </div>

                        {{-- DARI GUDANG - DIPATENKAN --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 form-grid-compact mt-4 items-start">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1 compact-label">
                                    Dari Gudang
                                </label>
                                <input type="hidden" x-model="formData.dari_gudang">
                                <div class="w-full h-[38px] px-3 border border-gray-300 rounded-md bg-gray-50 text-[12px] leading-tight compact-input flex items-center overflow-hidden"
                                     :title="fixedGudangLabel">
                                    <span class="text-gray-900 font-medium truncate" x-text="fixedGudangLabel"></span>
                                </div>
                            </div>
                            
                            {{-- TANGGAL DIPERLUKAN --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1 compact-label">
                                    Tanggal Diperlukan
                                </label>
                                <input type="date"
                                       x-model="formData.tanggal_diperlukan"
                                       :min="tanggalHariIni"
                                       class="w-full h-[38px] px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input">
                                <p class="mt-0.5 text-xs text-gray-500">
                                    Pilih tanggal kapan barang diperlukan
                                </p>
                            </div>
                            
                            <div></div>
                            <div></div>
                        </div>

                        {{-- JENIS PEKERJAAN SECTION --}}                                              
                        <div class="bg-gray-50 p-4 rounded-lg radio-group-compact">
                            <label class="block text-xs font-medium text-gray-700 mb-2">
                                Jenis Pekerjaan:
                            </label>
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center radio-option">
                                    <input type="radio" x-model="formData.jenis_pekerjaan" value="repair" class="h-4 w-4 rounded-full border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700">Repair (Perbaikan)</span>
                                </label>
                                <label class="inline-flex items-center radio-option">
                                    <input type="radio" x-model="formData.jenis_pekerjaan" value="maintenance" class="h-4 w-4 rounded-full border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700">Maintenance (Perawatan)</span>
                                </label>
                                <label class="inline-flex items-center radio-option">
                                    <input type="radio" x-model="formData.jenis_pekerjaan" value="project" class="h-4 w-4 rounded-full border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700">Project (Proyek)</span>
                                </label>
  
                            </div>
                        </div>

                        {{-- KETERANGAN TAMBAHAN --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1 compact-label">
                                Keterangan (Mohon di sertakan No. WO di kolom ini)
                            </label>
                            <textarea x-model="formData.keterangan" 
                                      rows="2" 
                                      placeholder="Tambahkan keterangan tambahan jika diperlukan..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm compact-input"></textarea>
                        </div>

                        {{-- TABLE HEADER DENGAN TOMBOL TAMBAH --}}
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-base font-semibold text-gray-800">Daftar Barang</h4>
                            <button type="button" 
                                    @click="addBarangItem"
                                    class="text-primary-600 hover:text-primary-800 text-sm font-medium flex items-center gap-1 px-3 py-1.5 border border-primary-600 rounded hover:bg-primary-50 transition duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Baris Barang
                            </button>
                        </div>

                        {{-- TABLE BARANG DENGAN SEARCH --}}
                        <div class="overflow-x-auto border border-gray-300 rounded-lg">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50 border-b">
                                        <th class="px-4 py-2 border-r text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-12 table-header-compact">No</th>
                                        <th class="px-4 py-2 border-r text-left text-xs font-medium text-gray-700 uppercase tracking-wider min-w-80 table-header-compact">Nama Barang</th>
                                        <th class="px-4 py-2 border-r text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-32 table-header-compact">Jumlah</th>
                                        <th class="px-4 py-2 border-r text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-40 table-header-compact">Satuan</th>
                                        <th class="px-4 py-2 border-r text-left text-xs font-medium text-gray-700 uppercase tracking-wider min-w-64 table-header-compact">Keterangan</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-20 table-header-compact">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in barangItems" :key="index">
                                        <tr class="border-b hover:bg-gray-50 transition duration-150">
                                            <td class="px-4 py-2 border-r text-center font-medium text-gray-900 text-sm table-cell-compact" x-text="index + 1"></td>
                                            
                                            {{-- NAMA BARANG COLUMN --}}
                                            <td class="px-4 py-2 border-r relative">
                                                <div class="relative">
                                                    <input type="text" 
                                                           x-model="item.nama_barang"
                                                           @input.debounce.300ms="searchBarang($event.target.value, index, $event)"
                                                           @focus="handleBarangFocus(index, $event)"
                                                           @keydown.down.prevent="navigateSearchResults('down')"
                                                           @keydown.up.prevent="navigateSearchResults('up')"
                                                           @keydown.enter.prevent="selectHighlightedResult(index)"
                                                           @keydown.escape="clearBarangSearchState()"
                                                           placeholder="Ketik minimal 2 karakter..."
                                                           autocomplete="off"
                                                           autocapitalize="off"
                                                           spellcheck="false"
                                                           class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm compact-input">
                                                    
                                                    <button type="button" 
                                                            x-show="item.nama_barang"
                                                            @click="clearSearch(index)"
                                                            class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                    
                                                    <div x-show="isSearching && activeSearchIndex === index" 
                                                         class="absolute right-8 top-1/2 transform -translate-y-1/2">
                                                        <svg class="animate-spin h-4 w-4 text-primary-600" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            {{-- JUMLAH --}}
                                            <td class="px-4 py-2 border-r">
                                                <input type="number" 
                                                       x-model="item.jumlah"
                                                       @input="if($event.target.value <= 0) $event.target.value = ''"
                                                       placeholder="0"
                                                       min="0.01"
                                                       step="0.01"
                                                       class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm text-center compact-input">
                                            </td>
                                            
                                            {{-- SATUAN --}}
                                            <td class="px-4 py-2 border-r">
                                                <div class="relative">
                                                    <select x-model="item.satuan"
                                                            class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm appearance-none bg-white pr-8 compact-input">
                                                        <option value="">- Pilih Satuan -</option>
                                                        <template x-for="satuan in satuanList" :key="satuan.value">
                                                            <option :value="satuan.value" x-text="satuan.label"></option>
                                                        </template>
                                                    </select>
                                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            {{-- KETERANGAN --}}
                                            <td class="px-4 py-2 border-r">
                                                <input type="text" 
                                                       x-model="item.keterangan"
                                                       placeholder="Masukkan keterangan (opsional)..."
                                                       class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm compact-input">
                                            </td>
                                            
                                            {{-- AKSI --}}
                                            <td class="px-4 py-2 text-center">
                                                <button type="button" 
                                                        @click="removeBarangItem(index)"
                                                        class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition duration-200"
                                                        :disabled="barangItems.length === 1"
                                                        :class="{'opacity-50 cursor-not-allowed hover:bg-transparent hover:text-red-600': barangItems.length === 1}"
                                                        title="Hapus baris">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- BARANG SEARCH DROPDOWN --}}
                        <template x-if="shouldRenderBarangDropdown()">
                            <div :style="getSearchDropdownStyle()"
                                 class="search-dropdown"
                                 @mousedown.stop
                                 @click.stop>
                                <div class="p-2 border-b bg-gray-50 sticky top-0 flex justify-between items-center">
                                    <div class="text-xs font-medium text-gray-700">
                                        <template x-if="searchResults.length > 0">
                                            <span><span x-text="searchResults.length"></span> hasil ditemukan</span>
                                        </template>
                                        <template x-if="searchResults.length === 0 && barangSearchHasRun && !isSearching && hasMinimumBarangSearchLength()">
                                            <span>Tidak ada hasil</span>
                                        </template>
                                        <template x-if="isSearching && hasMinimumBarangSearchLength()">
                                            <span>Mencari barang...</span>
                                        </template>
                                    </div>
                                    <div x-show="searchResults.length > 0" class="text-xs text-gray-500">
                                        ↑ ↓ pilih • Enter
                                    </div>
                                </div>

                                <div x-show="searchResults.length > 0" class="p-1">
                                    <template x-for="(result, idx) in searchResults" :key="result.id || result.kode || idx">
                                        <button type="button"
                                                @mousedown.prevent
                                                @click="selectBarang(result, activeSearchIndex)"
                                                @mouseenter="highlightedResult = idx"
                                                :class="{'bg-blue-50': highlightedResult === idx}"
                                                class="w-full px-3 py-2 text-left hover:bg-blue-50 border-b border-gray-100 last:border-b-0 transition duration-150 focus:outline-none focus:bg-blue-50 rounded">
                                            <div class="font-medium text-gray-900 text-sm" x-text="result.nama"></div>
                                            <div class="flex justify-between items-center mt-0.5">
                                                <div class="text-xs text-gray-500" x-text="'Kategori: ' + result.kategori"></div>
                                                <div class="text-xs text-primary-600 font-medium" x-text="'Satuan: ' + result.satuan"></div>
                                            </div>
                                            <div x-show="result.kode" class="text-xs text-gray-400 mt-0.5" x-text="'Kode: ' + result.kode"></div>
                                        </button>
                                    </template>
                                </div>

                                <div x-show="isSearching && searchResults.length === 0 && hasMinimumBarangSearchLength()"
                                     class="p-4 text-center text-gray-500 text-sm">
                                    Mohon tunggu, sedang mencari barang...
                                </div>

                                <div x-show="searchResults.length === 0 && barangSearchHasRun && !isSearching && hasMinimumBarangSearchLength()" 
                                     class="p-4 text-center text-gray-500 text-sm">
                                    Tidak ditemukan barang dengan kata kunci "<span x-text="barangItems[activeSearchIndex]?.nama_barang" class="font-medium"></span>"
                                    <div class="mt-2">
                                        <button type="button" 
                                                @mousedown.prevent
                                                @click="addNewBarang(barangItems[activeSearchIndex]?.nama_barang, activeSearchIndex)"
                                                class="text-xs text-primary-600 hover:text-primary-800 font-medium">
                                            + Tambah barang baru
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- FOOTER NOTE --}}
                        <div class="text-xs text-gray-500 italic flex items-center gap-2">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            * Pastikan semua data barang sudah diisi dengan benar sebelum menyimpan.
                        </div>

                        {{-- ACTION BUTTONS --}}
                        <div class="flex justify-end gap-3 pt-4 border-t action-buttons-compact">
                            <button type="button"
                                    @click="showCreateModal = false"
                                    :disabled="isSubmitting"
                                    class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary-500 transition duration-200 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                Batalkan
                            </button>
                            <button type="submit"
                                    :disabled="isSubmitting"
                                    class="px-6 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary-500 transition duration-200 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                <span x-show="!isSubmitting">Simpan Permintaan</span>
                                <span x-show="isSubmitting" class="flex items-center gap-1">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Menyimpan...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL DETAIL PERMINTAAN BARANG --}}
    <div x-show="showDetailModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="detail-modal-title"
         role="dialog"
         aria-modal="true"
         @keydown.escape.window="showDetailModal = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            
            {{-- BACKGROUND OVERLAY --}}
            <div x-show="showDetailModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50 backdrop-blur-sm"
                 aria-hidden="true">
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- MODAL CONTENT --}}
            <div x-show="showDetailModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block w-full max-w-5xl p-0 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-xl shadow-2xl">
                
                {{-- HEADER dengan Status Badge --}}
                <div class="relative bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="text-xl font-bold text-white" id="detail-modal-title">
                                    Detail Permintaan Barang
                                </h3>
                                <span x-show="selectedDetail?.header?.nomor_pb" 
                                      class="px-3 py-1 text-xs font-mono bg-white/20 text-white rounded-full border border-white/30">
                                    <span x-text="selectedDetail?.header?.nomor_pb"></span>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            {{-- STATUS BADGE --}}
                            <span x-show="selectedDetail?.header?.status" 
                                  class="px-4 py-1.5 text-xs font-semibold rounded-full shadow-lg"
                                  :class="{
                                      'bg-yellow-100 text-yellow-800 border border-yellow-200': selectedDetail?.header?.status === 'pending',
                                      'bg-blue-100 text-blue-800 border border-blue-200': selectedDetail?.header?.status === 'approved',
                                      'bg-green-100 text-green-800 border border-green-200': selectedDetail?.header?.status === 'completed',
                                      'bg-red-100 text-red-800 border border-red-200': selectedDetail?.header?.status === 'rejected',
                                      'bg-purple-100 text-purple-800 border border-purple-200': selectedDetail?.header?.status === 'in_progress',
                                      'bg-gray-100 text-gray-800 border border-gray-200': !['pending','approved','completed','rejected','in_progress'].includes(selectedDetail?.header?.status)
                                  }">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full" :class="{
                                        'bg-yellow-500': selectedDetail?.header?.status === 'pending',
                                        'bg-blue-500': selectedDetail?.header?.status === 'approved',
                                        'bg-green-500': selectedDetail?.header?.status === 'completed',
                                        'bg-red-500': selectedDetail?.header?.status === 'rejected',
                                        'bg-purple-500': selectedDetail?.header?.status === 'in_progress',
                                        'bg-gray-500': !['pending','approved','completed','rejected','in_progress'].includes(selectedDetail?.header?.status)
                                    }"></span>
                                    <span x-text="selectedDetail?.header?.status ? selectedDetail.header.status.charAt(0).toUpperCase() + selectedDetail.header.status.slice(1).replace('_', ' ') : '-'"></span>
                                </span>
                            </span>
                            <button @click="showDetailModal = false" 
                                    class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition duration-200 focus:outline-none focus:ring-2 focus:ring-white/50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- DETAIL INFO --}}
                <div x-show="selectedDetail" class="p-6 space-y-6">
                    
                {{-- INFO CARD GRID --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    {{-- BAGIAN --}}
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:shadow-md transition duration-200">
                        <div class="flex items-start gap-3">
                            <div class="bg-primary-100 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bagian</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5" x-text="selectedDetail?.header?.bagian || 'Engineering'"></p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- UNTUK --}}
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:shadow-md transition duration-200">
                        <div class="flex items-start gap-3">
                            <div class="bg-blue-100 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Untuk</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5 capitalize" x-text="selectedDetail?.header?.untuk || '-'"></p>
                                <p class="text-xs text-gray-500 mt-0.5">Tujuan permintaan</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- DARI GUDANG --}}
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:shadow-md transition duration-200">
                        <div class="flex items-start gap-3">
                            <div class="bg-green-100 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Dari Gudang</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5" x-text="selectedDetail?.header ? formatGudang(selectedDetail.header.dari_gudang) : fixedGudangLabel"></p>
                                <p class="text-xs text-gray-500 mt-0.5">Lokasi pengambilan</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- JENIS PEKERJAAN --}}
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:shadow-md transition duration-200">
                        <div class="flex items-start gap-3">
                            <div class="bg-purple-100 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Pekerjaan</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5 capitalize" x-text="selectedDetail?.header?.jenis_pekerjaan || '-'"></p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    <span x-show="selectedDetail?.header?.jenis_pekerjaan === 'repair'">Perbaikan mesin/alat</span>
                                    <span x-show="selectedDetail?.header?.jenis_pekerjaan === 'maintenance'">Perawatan rutin</span>
                                    <span x-show="selectedDetail?.header?.jenis_pekerjaan === 'project'">Kebutuhan proyek</span>
                                 
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- TANGGAL DIPERLUKAN --}}
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:shadow-md transition duration-200">
                        <div class="flex items-start gap-3">
                            <div class="bg-orange-100 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Diperlukan</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5" x-text="formatDateForDisplay(selectedDetail?.header?.tanggal_diperlukan)"></p>
                                <p class="text-xs text-gray-500 mt-0.5">Batas kebutuhan</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- TANGGAL PERMINTAAN --}}
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:shadow-md transition duration-200">
                        <div class="flex items-start gap-3">
                            <div class="bg-indigo-100 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Permintaan</p>
                                <p class="text-sm font-semibold text-gray-900 mt-0.5" x-text="formatDateForDisplay(selectedDetail?.header?.tanggal_permintaan)"></p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- NAMA MESIN/BANGUNAN - INI YANG MUNCUL DI KOTAK MERAH --}}
                    <template x-if="selectedDetail?.untuk_info">
                        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200 col-span-1 md:col-span-2 lg:col-span-2">
                            <div class="flex items-center gap-3">
                                <div class="bg-blue-500 p-2 rounded-lg">
                                    <template x-if="selectedDetail?.header?.untuk === 'mesin'">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </template>
                                    <template x-if="selectedDetail?.header?.untuk === 'bangunan'">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </template>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-blue-600 uppercase tracking-wider" x-text="selectedDetail?.header?.untuk === 'mesin' ? 'DETAIL MESIN' : 'DETAIL BANGUNAN'"></p>
                                    <div class="flex items-center flex-wrap gap-2 mt-1">
                                        <span class="text-base font-bold text-gray-900" x-text="selectedDetail.untuk_info.nama"></span>
                                        <span x-show="selectedDetail.untuk_info.kode" class="px-2 py-0.5 bg-white text-blue-700 rounded-md text-xs font-mono border border-blue-200" x-text="selectedDetail.untuk_info.kode"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    {{-- KALO TIDAK ADA INFO, TAMPILKAN KOLOM KOSONG --}}
                    <template x-if="!selectedDetail?.untuk_info">
                        <div class="col-span-1 md:col-span-2 lg:col-span-2"></div>
                    </template>
                    
                </div>

                    {{-- KETERANGAN TAMBAHAN --}}
                    <div x-show="selectedDetail?.header?.keterangan" 
                         class="bg-gradient-to-r from-gray-50 to-white rounded-lg p-4 border border-gray-200">
                        <div class="flex gap-3">
                            <div class="bg-gray-200 p-2 rounded-lg h-fit">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Keterangan Tambahan</p>
                                <p class="text-sm text-gray-700 bg-white p-3 rounded-lg border border-gray-100" x-text="selectedDetail?.header?.keterangan"></p>
                            </div>
                        </div>
                    </div>

                    {{-- TABLE BARANG --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="bg-primary-100 p-1.5 rounded-lg">
                                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <h4 class="text-base font-bold text-gray-900">Daftar Barang</h4>
                            </div>
                            <div class="text-sm text-gray-500">
                                Total <span x-text="selectedDetail?.detail?.length || 0"></span> item
                            </div>
                        </div>
                        
                        <div class="overflow-hidden border border-gray-200 rounded-xl">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">No</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Jumlah</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Satuan</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(item, index) in selectedDetail?.detail" :key="index">
                                        <tr class="hover:bg-gray-50 transition duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <span class="flex items-center justify-center w-6 h-6 bg-gray-100 rounded-full text-xs">
                                                    <span x-text="index + 1"></span>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                <div x-text="item.nama_barang"></div>
                                                <div x-show="item.kode_barang" class="text-xs text-gray-500 mt-0.5" x-text="item.kode_barang"></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-mono">
                                                <span class="font-semibold" x-text="formatNumber(item.jumlah)"></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <span class="px-2 py-1 bg-gray-100 rounded-md text-xs font-medium" x-text="item.satuan || '-'"></span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                <span x-show="item.keterangan" x-text="item.keterangan" class="italic"></span>
                                                <span x-show="!item.keterangan" class="text-gray-400 italic">-</span>
                                            </td>
                                        </tr>
                                    </template>
                                    
                                    <tr x-show="!selectedDetail?.detail || selectedDetail.detail.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center gap-2">
                                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                                <p class="text-gray-500 text-sm">Tidak ada daftar barang</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                
                                {{-- FOOTER SUMMARY --}}
                                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                    <tr>
                                        <td colspan="2" class="px-6 py-3 text-sm font-medium text-gray-700">Total Barang</td>
                                        <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right font-mono" 
                                            x-text="selectedDetail?.detail ? selectedDetail.detail.reduce((sum, item) => sum + parseFloat(item.jumlah || 0), 0).toFixed(2) : '0.00'">
                                        </td>
                                        <td colspan="2" class="px-6 py-3 text-sm text-gray-600">
                                            <span x-text="selectedDetail?.detail?.length || 0 + ' item(s)'"></span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- FOOTER ACTION BUTTONS --}}
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <div class="flex gap-2">
                            <button type="button"
                                    @click="showDetailModal = false"
                                    class="px-5 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-200 shadow-sm">
                                Tutup
                            </button>
                            <button type="button"
                                    @click="printDetail(selectedDetail)"
                                    class="px-5 py-2 text-xs font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-200 shadow-sm flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Cetak
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Notification Component
function notificationApp() {
    return {
        show: false,
        type: 'success',
        message: '',
        
        showNotification(type, message, duration = 3000) {
            this.type = type;
            this.message = message;
            this.show = true;
            
            setTimeout(() => {
                this.show = false;
            }, duration);
        }
    }
}

// Main Transaksi App - COMPLETE VERSION WITH DYNAMIC MESIN/BANGUNAN DROPDOWN
function transaksiApp() {
    return {
        // ============ TAB & MODAL STATE ============
        tab: 'all',
        showCreateModal: false,
        showDetailModal: false,
        selectedDetail: null,

        // ============ FIXED GUDANG ============
        fixedGudangValue: 'gudang_11',
        fixedGudangLabel: 'Gudang 11 (Spareparts & Packaging)',
        
        // ============ DATA TRANSAKSI ============
        transaksiData: @json($transaksiData),
        searchQuery: '',
        statusFilter: '',
        dateFilter: '',
        isSearchingTransaksi: false,
        isSearchFocused: false,
        
        // ============ REFRESH STATE ============
        isRefreshing: false,
        lastRefresh: null,
        refreshInterval: null,
        autoRefreshEnabled: false,
        
        // ============ SORTING ============
        sortField: 'created_at',
        sortDirection: 'desc',
        
        // ============ PAGINATION ============
        currentPage: 1,
        perPage: 10,
        
        // ============ FORM DATA ============
        formData: {
            nomor_pb: '',
            untuk: '',
            untuk_id: '',        // ID untuk mesin atau bangunan
            dari_gudang: 'gudang_11',
            jenis_pekerjaan: '',
            tanggal_diperlukan: '',
            keterangan: ''
        },
        
        // ============ DYNAMIC LIST STATE ============
        untukList: [],           // List mesin atau bangunan
        isLoadingUntuk: false,
        selectedUntuk: null,
        
        // ============ BARANG ITEMS ============
        barangItems: [{ 
            id: null, 
            nama_barang: '', 
            jumlah: '', 
            satuan: '', 
            keterangan: '' 
        }],
        
        // ============ SEARCH BARANG ============
        searchResults: [],
        isSearching: false,
        activeSearchIndex: null,
        highlightedResult: 0,
        minBarangSearchLength: 2,
        barangSearchHasRun: false,
        lastBarangSearchQuery: '',
        lastBarangSearchIndex: null,
        searchDropdownPosition: { top: 0, left: 0, width: 0, maxHeight: 256 },
        
        // ============ SUBMIT STATE ============
        isSubmitting: false,
        
        // ============ DATE UTILS ============
        tanggalHariIni: '',
        tanggalFormat: '',
        
        // ============ MASTER DATA ============
        satuanList: [
            { value: 'pcs', label: 'Pcs (Unit)' },
            { value: 'unit', label: 'Unit' },
            { value: 'kg', label: 'Kilogram (Kg)' },
            { value: 'gram', label: 'Gram (g)' },
            { value: 'liter', label: 'Liter (L)' },
            { value: 'ml', label: 'Milliliter (ml)' },
            { value: 'meter', label: 'Meter (m)' },
            { value: 'cm', label: 'Centimeter (cm)' },
            { value: 'mm', label: 'Millimeter (mm)' },
            { value: 'box', label: 'Box' },
            { value: 'pack', label: 'Pack' },
            { value: 'roll', label: 'Roll' },
            { value: 'set', label: 'Set' },
            { value: 'buah', label: 'Buah' },
            { value: 'lembar', label: 'Lembar' },
            { value: 'pair', label: 'Pair (Pasang)' },
            { value: 'bottle', label: 'Bottle (Botol)' },
            { value: 'can', label: 'Can (Kaleng)' },
            { value: 'tube', label: 'Tube (Tabung)' },
            { value: 'bag', label: 'Bag (Karung)' },
            { value: 'drum', label: 'Drum' },
            { value: 'carton', label: 'Carton (Kardus)' },
            { value: 'pallet', label: 'Pallet' }
        ],

        // ============ USER ROLE ============
        userRole: window.userRole || 'user',

        // ============ GETTERS ============
        get filteredTransaksi() {
            let result = [...this.transaksiData];
            
            if (this.searchQuery && this.searchQuery.length >= 1) {
                const query = this.searchQuery.toLowerCase();
                result = result.filter(tr => 
                    (tr.nomor_pb && tr.nomor_pb.toLowerCase().includes(query)) ||
                    (tr.untuk && tr.untuk.toLowerCase().includes(query)) ||
                    (tr.dari_gudang && tr.dari_gudang.toLowerCase().includes(query)) ||
                    (tr.jenis_pekerjaan && tr.jenis_pekerjaan.toLowerCase().includes(query)) ||
                    (tr.status && tr.status.toLowerCase().includes(query))
                );
            }
            
            if (this.statusFilter) {
                result = result.filter(tr => tr.status === this.statusFilter);
            }
            
            if (this.dateFilter) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                result = result.filter(tr => {
                    const trDate = new Date(tr.tanggal_permintaan);
                    trDate.setHours(0, 0, 0, 0);
                    
                    switch(this.dateFilter) {
                        case 'today':
                            return trDate.getTime() === today.getTime();
                        case 'week':
                            const weekAgo = new Date(today);
                            weekAgo.setDate(today.getDate() - 7);
                            return trDate >= weekAgo;
                        case 'month':
                            const monthAgo = new Date(today);
                            monthAgo.setMonth(today.getMonth() - 1);
                            return trDate >= monthAgo;
                        case 'year':
                            const yearAgo = new Date(today);
                            yearAgo.setFullYear(today.getFullYear() - 1);
                            return trDate >= yearAgo;
                        default:
                            return true;
                    }
                });
            }
            
            result.sort((a, b) => {
                if (a.created_at && b.created_at) {
                    return new Date(b.created_at) - new Date(a.created_at);
                }
                return (b.id || 0) - (a.id || 0);
            });
            
            return result;
        },
        
        get completedTransaksi() {
            return this.transaksiData.filter(tr => tr.status === 'completed');
        },
        
        get paginatedTransaksi() {
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            return this.filteredTransaksi.slice(start, end);
        },
        
        get totalPages() {
            return Math.ceil(this.filteredTransaksi.length / this.perPage);
        },

        // ============ BARANG METHODS ============
        addBarangItem() {
            this.clearBarangSearchState();
            this.barangItems.push({ 
                id: null, 
                nama_barang: '', 
                jumlah: '', 
                satuan: '', 
                keterangan: '' 
            });
        },

        removeBarangItem(index) {
            this.clearBarangSearchState();
            if (this.barangItems.length > 1) {
                this.barangItems.splice(index, 1);
            }
        },

        // ============ LOAD NOMOR PB DARI SERVER ============
        async loadNomorPB() {
            console.log('?? Loading nomor PB...');
            
            try {
                const response = await fetch('/transaksi/generate-nomor', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.formData.nomor_pb = data.nomor_pb;
                        console.log('? Nomor PB loaded:', data.nomor_pb);
                        return;
                    }
                }
                
                console.warn('?? Gagal load nomor PB, generate manual');
                this.generateManualNomorPB();
                
            } catch (error) {
                console.error('Error loading nomor PB:', error);
                this.generateManualNomorPB();
            }
        },

        generateManualNomorPB() {
            const date = new Date();
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const timestamp = Date.now().toString().slice(-3);
            this.formData.nomor_pb = `PB-ENG-${year}${month}${day}-${timestamp}`;
            console.log('?? Manual nomor PB:', this.formData.nomor_pb);
        },

        // ============ DYNAMIC LIST METHODS (MESIN/BANGUNAN) ============
        async loadUntukList() {
            if (!this.formData.untuk) {
                this.untukList = [];
                return;
            }
            
            this.isLoadingUntuk = true;
            this.formData.untuk_id = '';
            this.selectedUntuk = null;
            
            try {
                let url = '';
                let responseData = null;
                
                if (this.formData.untuk === 'mesin') {
                    url = '/api/mesin/list';
                    console.log('?? Loading mesin list...');
                } else if (this.formData.untuk === 'bangunan') {
                    url = '/api/bangunan/list';
                    console.log('?? Loading bangunan list...');
                } else {
                    this.untukList = [];
                    this.isLoadingUntuk = false;
                    return;
                }
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('?? Response:', result);
                
                if (result.success && Array.isArray(result.data)) {
                    // Map data sesuai format yang konsisten
                    this.untukList = result.data.map(item => ({
                        id: item.id || item.id_mesin || item.id_bangunan,
                        nama: item.nama || item.nama_mesin || item.nama_bangunan || '-',
                        kode: item.kode || item.kode_mesin || item.kode_bangunan || ''
                    }));
                    
                    console.log(`? ${this.formData.untuk} loaded:`, this.untukList.length, 'items');
                    
                    if (this.untukList.length === 0) {
                        if (window.notificationApp) {
                            window.notificationApp.showNotification('info', `Tidak ada data ${this.formData.untuk} tersedia`);
                        }
                    }
                } else {
                    console.error(`? Gagal load ${this.formData.untuk}:`, result.message);
                    this.untukList = [];
                    
                    if (window.notificationApp) {
                        window.notificationApp.showNotification('error', `Gagal memuat data ${this.formData.untuk}`);
                    }
                }
            } catch (error) {
                console.error(`? Error loading ${this.formData.untuk}:`, error);
                this.untukList = [];
                
                if (window.notificationApp) {
                    window.notificationApp.showNotification('error', `Gagal terhubung ke server`);
                }
            } finally {
                this.isLoadingUntuk = false;
            }
        },

        async handleUntukChange() {
            console.log('?? Untuk changed to:', this.formData.untuk);
            
            // Reset form yang terkait
            this.formData.untuk_id = '';
            this.selectedUntuk = null;
            this.untukList = [];
            
            // Load list berdasarkan pilihan
            if (this.formData.untuk === 'mesin' || this.formData.untuk === 'bangunan') {
                await this.loadUntukList();
            }
        },

        handleUntukIdChange() {
            console.log('?? Selected ID:', this.formData.untuk_id);
            
            if (!this.formData.untuk_id) {
                this.selectedUntuk = null;
                return;
            }
            
            // Cari item yang dipilih
            this.selectedUntuk = this.untukList.find(item => item.id == this.formData.untuk_id);
            
            if (this.selectedUntuk) {
                console.log(`? ${this.formData.untuk} dipilih:`, this.selectedUntuk);
                
                if (window.notificationApp) {
                    window.notificationApp.showNotification('success', `${this.selectedUntuk.nama} dipilih`);
                }
            }
        },

        // ============ SEARCH METHODS ============
        async searchTransaksi() {
            if (this.searchQuery.length < 1) {
                return;
            }
            
            this.isSearchingTransaksi = true;
            
            try {
                await new Promise(resolve => setTimeout(resolve, 300));
            } catch (error) {
                console.error('Search error:', error);
            } finally {
                this.isSearchingTransaksi = false;
            }
        },

        filterTransaksi() {
            this.currentPage = 1;
        },

        resetFilters() {
            this.searchQuery = '';
            this.statusFilter = '';
            this.dateFilter = '';
            this.currentPage = 1;
            this.loadData();
        },

        // ============ LOAD DATA FROM SERVER ============
        async loadData() {
            console.log('?? Loading data from server...');
            this.isSearchingTransaksi = true;
            
            try {
                const response = await fetch('/transaksi', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const result = await response.json();
                    
                    if (result.success && Array.isArray(result.data)) {
                        this.transaksiData = result.data;
                        console.log('? Data loaded:', result.data.length, 'items');
                    } else if (Array.isArray(result)) {
                        this.transaksiData = result;
                        console.log('? Data loaded (array):', result.length, 'items');
                    } else {
                        console.warn('?? Unexpected response structure, using fallback');
                        this.transaksiData = @json($transaksiData);
                    }
                } else {
                    console.warn('?? Response not OK, using fallback');
                    this.transaksiData = @json($transaksiData);
                }
                
                this.lastRefresh = new Date();
                this.currentPage = this.currentPage;
                return true;
                
            } catch (error) {
                console.error('? Load data error:', error);
                this.transaksiData = @json($transaksiData);
                this.lastRefresh = new Date();
                return false;
            } finally {
                this.isSearchingTransaksi = false;
            }
        },

        async refreshData() {
            if (this.isRefreshing) return;
            
            console.log('?? Manual refresh triggered');
            this.isRefreshing = true;
            
            try {
                await this.loadData();
                this.currentPage = 1;
                
                if (window.notificationApp) {
                    window.notificationApp.showNotification('success', '? Data berhasil diperbarui');
                }
                
            } catch (error) {
                console.error('Refresh error:', error);
                
                if (window.notificationApp) {
                    window.notificationApp.showNotification('error', '? Gagal refresh: ' + error.message);
                }
            } finally {
                this.isRefreshing = false;
            }
        },

        toggleAutoRefresh() {
            this.autoRefreshEnabled = !this.autoRefreshEnabled;
            
            if (this.autoRefreshEnabled) {
                if (this.refreshInterval) {
                    clearInterval(this.refreshInterval);
                }
                
                this.refreshInterval = setInterval(() => {
                    if (!this.showCreateModal && !this.showDetailModal) {
                        console.log('?? Auto-refresh triggered');
                        this.refreshData();
                    }
                }, 30000);
                
                if (window.notificationApp) {
                    window.notificationApp.showNotification('info', 'Auto-refresh diaktifkan (30 detik)');
                }
            } else {
                if (this.refreshInterval) {
                    clearInterval(this.refreshInterval);
                    this.refreshInterval = null;
                }
                
                if (window.notificationApp) {
                    window.notificationApp.showNotification('info', 'Auto-refresh dimatikan');
                }
            }
        },

        formatTime(date) {
            if (!date) return '';
            return date.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        },

        sortBy(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'asc';
            }
        },

        getPaginationPages() {
            const total = this.totalPages;
            const current = this.currentPage;
            const delta = 2;
            
            let pages = [];
            
            for (let i = 1; i <= total; i++) {
                if (
                    i === 1 ||
                    i === total ||
                    (i >= current - delta && i <= current + delta)
                ) {
                    pages.push(i);
                } else if (
                    (i === current - delta - 1 && i > 1) ||
                    (i === current + delta + 1 && i < total)
                ) {
                    pages.push('...');
                }
            }
            
            pages = pages.filter((page, index, self) => 
                self.indexOf(page) === index
            );
            
            return pages;
        },

        // ============ SEARCH BARANG METHODS ============
        handleBarangFocus(index, event) {
            this.activeSearchIndex = index;
            this.highlightedResult = 0;
            this.updateSearchDropdownPosition(event);

            const query = (this.barangItems[index]?.nama_barang || '').trim();

            if (query.length < this.minBarangSearchLength) {
                this.clearBarangSearchState(false);
                return;
            }

            this.searchBarang(query, index, event);
        },

        clearBarangSearchState(clearActiveIndex = true) {
            this.searchResults = [];
            this.isSearching = false;
            this.barangSearchHasRun = false;
            this.lastBarangSearchQuery = '';
            this.lastBarangSearchIndex = null;
            this.highlightedResult = 0;

            if (clearActiveIndex) {
                this.activeSearchIndex = null;
            }
        },

        getActiveBarangSearchQuery() {
            if (this.activeSearchIndex === null || this.activeSearchIndex === undefined) {
                return '';
            }

            return (this.barangItems[this.activeSearchIndex]?.nama_barang || '').trim();
        },

        hasMinimumBarangSearchLength() {
            return this.getActiveBarangSearchQuery().length >= this.minBarangSearchLength;
        },

        isBarangSearchCurrent(index, query) {
            return this.activeSearchIndex === index && this.getActiveBarangSearchQuery() === query;
        },

        shouldRenderBarangDropdown() {
            if (this.activeSearchIndex === null || this.activeSearchIndex === undefined) {
                return false;
            }

            if (!this.hasMinimumBarangSearchLength()) {
                return false;
            }

            return this.isSearching || this.searchResults.length > 0 || (this.barangSearchHasRun && !this.isSearching);
        },

        shouldShowBarangDropdown() {
            return this.shouldRenderBarangDropdown();
        },

        async searchBarang(query, index, event) {
            this.activeSearchIndex = index;
            this.highlightedResult = 0;
            this.updateSearchDropdownPosition(event);

            const trimmedQuery = (query || '').trim();

            if (trimmedQuery.length < this.minBarangSearchLength) {
                this.clearBarangSearchState(false);
                return;
            }

            this.isSearching = true;
            this.barangSearchHasRun = false;
            this.lastBarangSearchQuery = trimmedQuery;
            this.lastBarangSearchIndex = index;
            this.searchResults = [];
            
            try {
                const response = await fetch(`/api/barang/search?q=${encodeURIComponent(trimmedQuery)}`);
                
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const data = await response.json();

                if (!this.isBarangSearchCurrent(index, trimmedQuery)) {
                    return;
                }
                
                if (data.error) {
                    console.error('Server error:', data.message);
                    this.searchResults = [];
                } else {
                    this.searchResults = data.slice(0, 10);
                    this.updateSearchDropdownPosition(event);
                }
                
            } catch (error) {
                console.error('Search error:', error);

                if (!this.isBarangSearchCurrent(index, trimmedQuery)) {
                    return;
                }

                this.searchResults = [];
                
                if (window.masterBarang && window.masterBarang.length > 0) {
                    const searchTerm = trimmedQuery.toLowerCase();
                    this.searchResults = window.masterBarang
                        .filter(item => 
                            (item.item_name && item.item_name.toLowerCase().includes(searchTerm)) || 
                            (item.code && item.code.toLowerCase().includes(searchTerm))
                        )
                        .map(item => ({
                            id: item.id_items,
                            nama: item.item_name,
                            kode: item.code,
                            satuan: this.mapSatuanFromDB(item.meins),
                            kategori: item.mtart || 'Sparepart'
                        }))
                        .slice(0, 10);
                    this.updateSearchDropdownPosition(event);
                }
            } finally {
                this.isSearching = false;

                if (this.isBarangSearchCurrent(index, trimmedQuery)) {
                    this.barangSearchHasRun = true;
                }
            }
        },

        calculateSearchDropdownHeight(resultCount = null) {
            const count = resultCount ?? (this.searchResults?.length || 10);
            const headerHeight = 42;
            const itemHeight = 76;
            const padding = 8;

            return Math.min(256, headerHeight + (Math.max(count, 1) * itemHeight) + padding);
        },

        updateSearchDropdownPosition(event) {
            if (!event || !event.target) return;

            const inputRect = event.target.getBoundingClientRect();
            const modalContent = event.target.closest('.modal-content') || document.querySelector('.modal-content');
            const modalRect = modalContent ? modalContent.getBoundingClientRect() : { top: 0, left: 0 };
            const scrollTop = modalContent ? modalContent.scrollTop : window.scrollY;
            const scrollLeft = modalContent ? modalContent.scrollLeft : window.scrollX;
            const dropdownHeight = this.calculateSearchDropdownHeight();

            // Default: dropdown barang dibuka ke ATAS agar tidak keluar/ketutup bagian bawah modal.
            let top = inputRect.top - modalRect.top + scrollTop - dropdownHeight - 6;
            const minTop = scrollTop + 8;

            // Safety fallback: kalau ruang atas benar-benar tidak cukup, baru tampilkan ke bawah.
            if (top < minTop) {
                top = inputRect.bottom - modalRect.top + scrollTop + 6;
            }

            this.searchDropdownPosition = {
                top,
                left: inputRect.left - modalRect.left + scrollLeft,
                width: inputRect.width,
                maxHeight: dropdownHeight
            };
        },

        getSearchDropdownStyle() {
            const position = this.searchDropdownPosition || {};
            const top = Number(position.top) || 0;
            const left = Number(position.left) || 0;
            const width = Number(position.width) || 320;
            const maxHeight = Number(position.maxHeight) || 256;

            return `position: absolute; z-index: 9999; top: ${top}px; left: ${left}px; width: ${width}px; max-height: ${maxHeight}px; overflow-y: auto;`;
        },

        mapSatuanFromDB(meins) {
            const map = {
                'PCS': 'pcs', 'PC': 'pcs',
                'UNIT': 'unit', 'UNT': 'unit',
                'KG': 'kg', 'KGM': 'kg',
                'GRAM': 'gram', 'G': 'gram',
                'L': 'liter', 'LT': 'liter', 'LTR': 'liter',
                'ML': 'ml',
                'M': 'meter', 'MTR': 'meter',
                'CM': 'cm',
                'MM': 'mm',
                'BOX': 'box', 'BOK': 'box',
                'PACK': 'pack', 'PK': 'pack',
                'ROLL': 'roll', 'ROL': 'roll',
                'SET': 'set',
                'BTL': 'bottle',
                'CAN': 'can',
                'TUBE': 'tube',
                'DR': 'drum', 'DRUM': 'drum',
                'PLT': 'pallet', 'PAL': 'pallet'
            };
            return map[meins?.toUpperCase()] || meins?.toLowerCase() || 'pcs';
        },

        navigateSearchResults(direction) {
            if (this.searchResults.length === 0) return;
            
            if (direction === 'down') {
                this.highlightedResult = (this.highlightedResult + 1) % this.searchResults.length;
            } else if (direction === 'up') {
                this.highlightedResult = this.highlightedResult - 1;
                if (this.highlightedResult < 0) {
                    this.highlightedResult = this.searchResults.length - 1;
                }
            }
        },

        selectHighlightedResult(index) {
            if (this.searchResults.length > 0 && this.highlightedResult >= 0) {
                this.selectBarang(this.searchResults[this.highlightedResult], index);
            }
        },

        selectBarang(item, index) {
            this.barangItems[index].id = item.id;
            this.barangItems[index].nama_barang = item.nama;
            this.barangItems[index].satuan = item.satuan;
            this.clearBarangSearchState();
        },

        clearSearch(index) {
            this.barangItems[index].id = null;
            this.barangItems[index].nama_barang = '';
            this.barangItems[index].satuan = '';
            this.clearBarangSearchState();
        },

        addNewBarang(namaBarang, index) {
            alert(`Fitur tambah barang "${namaBarang}" akan segera hadir!`);
        },

        closeAllDropdowns() {
            this.clearBarangSearchState();
            this.isSearchFocused = false;
        },

        // ============ DATE METHODS ============
        formatDate() {
            const date = new Date();
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        },

        getDefaultRequiredDate() {
            const today = new Date();
            const nextWeek = new Date(today);
            nextWeek.setDate(today.getDate() + 7);
            return nextWeek.toISOString().split('T')[0];
        },

        formatDateForDisplay(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        },

        formatNumber(value) {
            if (!value) return '0.00';
            return parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },

        formatGudang(value) {
            if (!value || value === this.fixedGudangValue) {
                return this.fixedGudangLabel;
            }

            const normalized = String(value).trim().toLowerCase();
            if (normalized === 'gudang_11' || normalized === 'gudang 11') {
                return this.fixedGudangLabel;
            }

            return String(value).replace(/_/g, ' ').toUpperCase();
        },

        ensureFixedGudang() {
            this.formData.dari_gudang = this.fixedGudangValue;
        },

        // ============ FORM METHODS ============
        validateForm() {
            this.ensureFixedGudang();

            if (!this.formData.untuk) {
                alert('Pilih tujuan permintaan');
                return false;
            }

            if ((this.formData.untuk === 'mesin' || this.formData.untuk === 'bangunan') && !this.formData.untuk_id) {
                alert(`Pilih ${this.formData.untuk} terlebih dahulu`);
                return false;
            }

            if (!this.formData.dari_gudang) {
                alert('Pilih gudang asal');
                return false;
            }

            if (!this.formData.jenis_pekerjaan) {
                alert('Pilih jenis pekerjaan');
                return false;
            }

            if (!this.formData.tanggal_diperlukan) {
                alert('Pilih tanggal diperlukan');
                return false;
            }

            const validItems = this.barangItems.filter(item => 
                item.nama_barang && item.jumlah && item.satuan
            );
            
            if (validItems.length === 0) {
                alert('Tambahkan minimal 1 barang dengan data lengkap');
                return false;
            }

            for (let i = 0; i < validItems.length; i++) {
                const item = validItems[i];
                if (!item.nama_barang.trim()) {
                    alert(`Baris ${i + 1}: Nama barang harus diisi`);
                    return false;
                }
                if (item.jumlah <= 0) {
                    alert(`Baris ${i + 1}: Jumlah harus lebih dari 0`);
                    return false;
                }
                if (!item.satuan) {
                    alert(`Baris ${i + 1}: Pilih satuan`);
                    return false;
                }
            }

            return true;
        },

        async submitForm() {
            if (!this.validateForm()) {
                return;
            }

            const formData = {
                nomor_pb: this.formData.nomor_pb,
                untuk: this.formData.untuk,
                untuk_id: this.formData.untuk_id,  // Kirim ID mesin atau bangunan
                dari_gudang: this.formData.dari_gudang,
                jenis_pekerjaan: this.formData.jenis_pekerjaan,
                tanggal_diperlukan: this.formData.tanggal_diperlukan,
                keterangan: this.formData.keterangan || '',
                barang: this.barangItems
                    .filter(item => item.nama_barang && item.jumlah && item.satuan)
                    .map(item => ({
                        nama_barang: item.nama_barang,
                        jumlah: parseFloat(item.jumlah),
                        satuan: item.satuan,
                        keterangan: item.keterangan || ''
                    }))
            };

            this.isSubmitting = true;
            
            try {
                const response = await fetch('/transaksi', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (window.notificationApp) {
                        window.notificationApp.showNotification('success', '? Permintaan berhasil disimpan!');
                    }
                    
                    this.resetForm();
                    this.showCreateModal = false;
                    
                    if (result.data && Array.isArray(result.data)) {
                        this.transaksiData = result.data;
                        console.log('? Data updated from response:', result.data.length, 'items');
                    } else {
                        await this.loadData();
                    }
                    
                    this.currentPage = 1;
                    
                } else {
                    throw new Error(result.message || 'Gagal menyimpan');
                }
            } catch (error) {
                console.error('Error:', error);
                
                if (window.notificationApp) {
                    window.notificationApp.showNotification('error', '? Gagal menyimpan: ' + error.message);
                }
            } finally {
                this.isSubmitting = false;
            }
        },

        resetForm() {
            this.formData = {
                nomor_pb: '',
                untuk: '',
                untuk_id: '',
                dari_gudang: 'gudang_11',
                jenis_pekerjaan: '',
                tanggal_diperlukan: this.getDefaultRequiredDate(),
                keterangan: ''
            };
            this.barangItems = [{ 
                id: null, 
                nama_barang: '', 
                jumlah: '', 
                satuan: '', 
                keterangan: '' 
            }];
            this.untukList = [];
            this.selectedUntuk = null;
            this.clearBarangSearchState();
            this.ensureFixedGudang();
            
            this.loadNomorPB();
        },

        // ============ DETAIL METHODS ============
        async showDetail(id) {
            try {
                const response = await fetch(`/transaksi/${id}`);
                const data = await response.json();
                
                if (data.success) {
                    this.selectedDetail = data.data;
                    this.showDetailModal = true;
                } else {
                    alert('Gagal mengambil data detail');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal mengambil data detail');
            }
        },

        async printTransaksi(tr) {
            const transaksiId = tr?.id;

            if (!transaksiId) {
                alert('Data transaksi tidak valid untuk dicetak.');
                return;
            }

            // Open window immediately from the click event to avoid browser popup blocker.
            const printWindow = window.open('', '_blank');

            if (!printWindow) {
                alert('Popup print diblokir browser. Izinkan popup untuk aplikasi ini dulu ya.');
                return;
            }

            printWindow.document.open();
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Menyiapkan Print...</title>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                padding: 32px;
                                color: #1f2937;
                            }
                            .loading {
                                max-width: 420px;
                                margin: 80px auto;
                                padding: 24px;
                                border: 1px solid #e5e7eb;
                                border-radius: 12px;
                                text-align: center;
                                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
                            }
                            .title {
                                font-size: 18px;
                                font-weight: 700;
                                margin-bottom: 8px;
                            }
                            .subtitle {
                                font-size: 13px;
                                color: #6b7280;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="loading">
                            <div class="title">Menyiapkan dokumen print...</div>
                            <div class="subtitle">Mohon tunggu sebentar.</div>
                        </div>
                    </body>
                </html>
            `);
            printWindow.document.close();

            try {
                const response = await fetch(`/transaksi/${transaksiId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal mengambil data detail transaksi.');
                }

                this.printDetail(data.data, printWindow);
            } catch (error) {
                console.error('Print transaksi error:', error);

                printWindow.document.open();
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Gagal Print</title>
                            <style>
                                body {
                                    font-family: Arial, sans-serif;
                                    padding: 32px;
                                    color: #991b1b;
                                }
                                .error-box {
                                    max-width: 520px;
                                    margin: 80px auto;
                                    padding: 24px;
                                    border: 1px solid #fecaca;
                                    border-radius: 12px;
                                    background: #fef2f2;
                                }
                                .title {
                                    font-size: 18px;
                                    font-weight: 700;
                                    margin-bottom: 8px;
                                }
                                .message {
                                    font-size: 13px;
                                    color: #7f1d1d;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="error-box">
                                <div class="title">Gagal menyiapkan print</div>
                                <div class="message">${error.message}</div>
                            </div>
                        </body>
                    </html>
                `);
                printWindow.document.close();

                alert('Gagal print transaksi: ' + error.message);
            }
        },

printDetail(detail, targetWindow = null) {
    if (!detail) return;
    
    const status = detail.header.status;
    const isApproved = status === 'approved' || status === 'completed';
    const today = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    
    const printWindow = targetWindow || window.open('', '_blank');

    if (!printWindow) {
        alert('Popup print diblokir browser. Izinkan popup untuk aplikasi ini dulu ya.');
        return;
    }

    printWindow.document.open();
    printWindow.document.write(`
        <html>
            <head>
                <title>Bon Permintaan Barang - ${detail.header.nomor_pb}</title>
                <style>
                    body {
                        font-family: 'Segoe UI', Arial, sans-serif;
                        margin: 0;
                        padding: 20px;
                        background: #f5f5f5;
                    }
                    
                    .print-container {
                        max-width: 800px;
                        margin: 0 auto;
                        background: white;
                        padding: 30px;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                        position: relative;
                    }
                    
                    /* Watermark */
                    .watermark {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%) rotate(-45deg);
                        font-size: 60px;
                        font-weight: bold;
                        color: rgba(34, 197, 94, 0.1);
                        text-transform: uppercase;
                        white-space: nowrap;
                        pointer-events: none;
                        z-index: 10;
                        border: 5px solid rgba(34, 197, 94, 0.2);
                        padding: 20px 60px;
                        border-radius: 20px;
                    }
                    
                    /* Header */
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                        padding-bottom: 20px;
                        border-bottom: 2px solid #2563eb;
                    }
                    
                    .header h1 {
                        font-size: 28px;
                        font-weight: bold;
                        color: #1e3a8a;
                        margin: 0 0 5px 0;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                    }
                    
                    .header h3 {
                        font-size: 16px;
                        font-weight: normal;
                        color: #4b5563;
                        margin: 0;
                    }
                    
                    /* Status Badge */
                    .status-container {
                        text-align: center;
                        margin: 15px 0 25px 0;
                    }
                    
                    .status-badge {
                        display: inline-block;
                        padding: 8px 24px;
                        border-radius: 30px;
                        font-size: 14px;
                        font-weight: bold;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                    }
                    
                    .status-approved {
                        background: #22c55e;
                        color: white;
                        border: 1px solid #16a34a;
                    }
                    
                    .status-pending {
                        background: #eab308;
                        color: white;
                        border: 1px solid #ca8a04;
                    }
                    
                    .status-rejected {
                        background: #ef4444;
                        color: white;
                        border: 1px solid #dc2626;
                    }
                    
                    .status-in_progress {
                        background: #8b5cf6;
                        color: white;
                        border: 1px solid #7c3aed;
                    }
                    
                    .status-completed {
                        background: #10b981;
                        color: white;
                        border: 1px solid #059669;
                    }
                    
                    /* Grid Info */
                    .info-grid {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 20px;
                        background: #f8fafc;
                        padding: 20px;
                        border-radius: 10px;
                        border: 1px solid #e2e8f0;
                        margin-bottom: 20px;
                    }
                    
                    .info-item {
                        display: flex;
                        flex-direction: column;
                    }
                    
                    .info-label {
                        font-size: 11px;
                        font-weight: 600;
                        color: #64748b;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        margin-bottom: 4px;
                    }
                    
                    .info-value {
                        font-size: 15px;
                        font-weight: 500;
                        color: #0f172a;
                    }
                    
                    .info-value.highlight {
                        background: #dbeafe;
                        padding: 4px 8px;
                        border-radius: 6px;
                        display: inline-block;
                        color: #1e40af;
                        font-weight: 600;
                    }
                    
                    /* Mesin/Bangunan Card */
                    .asset-card {
                        background: #eff6ff;
                        border-left: 4px solid #2563eb;
                        padding: 15px 20px;
                        margin: 20px 0;
                        border-radius: 8px;
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                    }
                    
                    .asset-icon {
                        background: #2563eb;
                        color: white;
                        width: 40px;
                        height: 40px;
                        border-radius: 8px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 20px;
                    }
                    
                    .asset-detail {
                        flex: 1;
                    }
                    
                    .asset-label {
                        font-size: 11px;
                        font-weight: 600;
                        color: #2563eb;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        margin-bottom: 4px;
                    }
                    
                    .asset-name {
                        font-size: 16px;
                        font-weight: 600;
                        color: #0f172a;
                    }
                    
                    .asset-code {
                        font-size: 12px;
                        color: #475569;
                        margin-top: 2px;
                    }
                    
                    /* Keterangan */
                    .keterangan-box {
                        background: #fef9c3;
                        border: 1px solid #facc15;
                        padding: 15px 20px;
                        border-radius: 8px;
                        margin: 20px 0;
                        display: flex;
                        gap: 12px;
                    }
                    
                    .keterangan-icon {
                        color: #854d0e;
                        font-size: 20px;
                    }
                    
                    .keterangan-text {
                        flex: 1;
                        font-style: italic;
                        color: #422006;
                    }
                    
                    .keterangan-text strong {
                        font-style: normal;
                    }
                    
                    /* Table */
                    .table-container {
                        margin: 25px 0;
                        border: 1px solid #e2e8f0;
                        border-radius: 10px;
                        overflow: hidden;
                    }
                    
                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    
                    th {
                        background: #1e293b;
                        color: white;
                        font-weight: 600;
                        font-size: 12px;
                        padding: 12px 10px;
                        text-align: left;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    
                    td {
                        padding: 10px;
                        border-bottom: 1px solid #e2e8f0;
                        font-size: 13px;
                    }
                    
                    tbody tr:last-child td {
                        border-bottom: none;
                    }
                    
                    tbody tr:hover {
                        background: #f8fafc;
                    }
                    
                    /* Total Section */
                    .total-section {
                        display: flex;
                        justify-content: flex-end;
                        gap: 30px;
                        margin: 20px 0;
                        padding: 15px 20px;
                        background: #f1f5f9;
                        border-radius: 8px;
                        border: 1px solid #cbd5e1;
                    }
                    
                    .total-item {
                        text-align: right;
                    }
                    
                    .total-label {
                        font-size: 12px;
                        color: #475569;
                        text-transform: uppercase;
                    }
                    
                    .total-value {
                        font-size: 18px;
                        font-weight: bold;
                        color: #0f172a;
                    }
                    
                    /* Approval Info */
                    .approval-info {
                        background: #f0fdf4;
                        border: 1px solid #86efac;
                        padding: 15px 20px;
                        border-radius: 8px;
                        margin: 20px 0;
                        color: #166534;
                        display: flex;
                        align-items: center;
                        gap: 12px;
                    }
                    
                    .rejection-info {
                        background: #fef2f2;
                        border: 1px solid #fecaca;
                        padding: 15px 20px;
                        border-radius: 8px;
                        margin: 20px 0;
                        color: #991b1b;
                        display: flex;
                        align-items: center;
                        gap: 12px;
                    }
                    
                    /* Print Date */
                    .print-date {
                        text-align: right;
                        font-size: 11px;
                        color: #94a3b8;
                        margin-top: 25px;
                        padding-top: 15px;
                        border-top: 1px dashed #cbd5e1;
                    }
                    
                    @media print {
                        body { background: white; padding: 0; }
                        .print-container { box-shadow: none; padding: 20px; }
                    }
                </style>
            </head>
            <body>
                <div class="print-container" style="position: relative;">
                    ${isApproved ? '<div class="watermark">APPROVED</div>' : ''}
                    
                    <!-- Header -->
                    <div class="header">
                        <h1>BON PERMINTAAN BARANG</h1>
                        <h3>Nomor: ${detail.header.nomor_pb}</h3>
                    </div>
                    
                    <!-- Status -->
                    <div class="status-container">
                        <span class="status-badge status-${detail.header.status || 'pending'}">
                            ${detail.header.status ? detail.header.status.toUpperCase().replace('_', ' ') : 'PENDING'}
                        </span>
                    </div>
                    
                    <!-- Info Grid 2 Kolom -->
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Tanggal Permintaan</span>
                            <span class="info-value">${this.formatDateForDisplay(detail.header.tanggal_permintaan)}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Bagian</span>
                            <span class="info-value">${detail.header.bagian || 'Engineering'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Untuk</span>
                            <span class="info-value highlight">${detail.header.untuk ? detail.header.untuk.toUpperCase() : '-'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Dari Gudang</span>
                            <span class="info-value">${this.formatGudang(detail.header.dari_gudang)}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Jenis Pekerjaan</span>
                            <span class="info-value">${detail.header.jenis_pekerjaan ? detail.header.jenis_pekerjaan.toUpperCase() : '-'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tanggal Diperlukan</span>
                            <span class="info-value">${this.formatDateForDisplay(detail.header.tanggal_diperlukan)}</span>
                        </div>
                    </div>
                    
<!-- Asset Card (Mesin/Bangunan) - Versi Simple -->
${detail.untuk_info ? `
<div class="asset-card" style="display: flex; align-items: center; gap: 15px; background: #eff6ff; border-left: 4px solid #2563eb; padding: 15px 20px; border-radius: 8px;">
    <div style="
        background: ${detail.header.untuk === 'mesin' ? '#2563eb' : '#059669'};
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 16px;
    ">
        ${detail.header.untuk === 'mesin' ? 'M' : 'B'}
    </div>
    <div style="flex: 1;">
        <div style="font-size: 11px; font-weight: 600; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
            ${detail.header.untuk === 'mesin' ? 'MESIN' : 'BANGUNAN'}
        </div>
        <div style="font-size: 16px; font-weight: 600; color: #0f172a;">
            ${detail.untuk_info.nama}
        </div>
        <div style="font-size: 12px; color: #475569; margin-top: 2px;">
            ${detail.untuk_info.kode ? 'Kode: ' + detail.untuk_info.kode : ''}
        </div>
    </div>
</div>
` : ''}
                    
                    <!-- Keterangan -->
                    ${detail.header.keterangan ? `
                    <div class="keterangan-box">
                        <div class="keterangan-text">
                            <strong>Keterangan:</strong> ${detail.header.keterangan}
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Table Barang -->
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 50%;">Nama Barang</th>
                                    <th style="width: 10%;">Jumlah</th>
                                    <th style="width: 10%;">Satuan</th>
                                    <th style="width: 25%;">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${detail.detail.map((item, index) => `
                                    <tr>
                                        <td style="text-align: center;">${index + 1}</td>
                                        <td>${item.nama_barang}</td>
                                        <td style="text-align: right;">${this.formatNumber(item.jumlah)}</td>
                                        <td style="text-align: center;">${item.satuan || '-'}</td>
                                        <td>${item.keterangan || '-'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Total -->
                    <div class="total-section">
                        <div class="total-item">
                            <div class="total-label">Total Item</div>
                            <div class="total-value">${detail.detail.length} item</div>
                        </div>
                        <div class="total-item">
                            <div class="total-label">Total Jumlah</div>
                            <div class="total-value">${this.formatNumber(detail.detail.reduce((sum, item) => sum + parseFloat(item.jumlah || 0), 0))}</div>
                        </div>
                    </div>
                    
                    <!-- Approval Info -->
                    ${detail.header.approved_at ? `
                    <div class="approval-info">
                        <div></div>
                        <div>
                            <strong>DISETUJUI PADA:</strong> ${new Date(detail.header.approved_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Rejection Info -->
                    ${detail.header.rejection_reason ? `
                    <div class="rejection-info">
                        <div>?</div>
                        <div>
                            <strong>DITOLAK:</strong> ${detail.header.rejection_reason}
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Print Date -->
                    <div class="print-date">
                        Dicetak pada: ${today}
                    </div>
                </div>
                
                <script>
                    window.onload = function() { window.print(); }
                <\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
},

        // ============ APPROVAL METHODS ============
        async approveRequest(tr) {
            if (!confirm(`Setujui permintaan barang ${tr.nomor_pb}?`)) {
                return;
            }
            
            this.isRefreshing = true;
            
            try {
                console.log('?? Approving ID:', tr.id);
                
                const response = await fetch(`/approval/${tr.id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                console.log('?? Response status:', response.status);
                
                const data = await response.json();
                console.log('?? Response data:', data);
                
                if (data.success) {
                    if (data.data && Array.isArray(data.data)) {
                        this.transaksiData = data.data;
                        console.log('? Data updated from response');
                    } else {
                        await this.loadData();
                    }
                    
                    if (window.notificationApp) {
                        window.notificationApp.showNotification('success', '? Permintaan berhasil disetujui!');
                    }
                    
                } else {
                    throw new Error(data.message || 'Gagal approve');
                }
            } catch (error) {
                console.error('? Error detail:', error);
                
                let errorMessage = error.message;
                
                if (error.response) {
                    try {
                        const errorData = await error.response.json();
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {}
                }
                
                if (window.notificationApp) {
                    window.notificationApp.showNotification('error', '? ' + errorMessage);
                } else {
                    alert('Gagal approve: ' + errorMessage);
                }
            } finally {
                this.isRefreshing = false;
            }
        },

        async rejectRequest(tr, reason) {
            try {
                console.log('?? Rejecting ID:', tr.id, 'Reason:', reason);
                
                this.isRefreshing = true;
                
                const response = await fetch(`/approval/${tr.id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ alasan: reason })
                });
                
                console.log('?? Response status:', response.status);
                
                const data = await response.json();
                console.log('?? Response data:', data);
                
                if (data.success) {
                    if (data.data && Array.isArray(data.data)) {
                        this.transaksiData = data.data;
                        console.log('? Data updated from response');
                    } else {
                        await this.loadData();
                    }
                    
                    if (window.notificationApp) {
                        window.notificationApp.showNotification('success', '? Permintaan ditolak');
                    }
                    
                } else {
                    throw new Error(data.message || 'Gagal menolak');
                }
            } catch (error) {
                console.error('? Error detail:', error);
                
                let errorMessage = error.message;
                
                if (error.response) {
                    try {
                        const errorData = await error.response.json();
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {}
                }
                
                if (window.notificationApp) {
                    window.notificationApp.showNotification('error', '? ' + errorMessage);
                } else {
                    alert('Gagal menolak: ' + errorMessage);
                }
            } finally {
                this.isRefreshing = false;
            }
        },

        showRejectModal(tr) {
            const reason = prompt('Masukkan alasan penolakan:', '');
            
            if (reason === null) return;
            
            if (!reason.trim()) {
                alert('Alasan penolakan harus diisi');
                return;
            }
            
            this.rejectRequest(tr, reason);
        },

        // ============ INIT ============
        async init() {
            console.log('?? Initializing app...');
            
            this.tanggalHariIni = new Date().toISOString().split('T')[0];
            this.tanggalFormat = this.formatDate();
            this.formData.tanggal_diperlukan = this.getDefaultRequiredDate();
            
            this.sortField = 'created_at';
            this.sortDirection = 'desc';
            
            console.log('?? User Role:', this.userRole);
            
            window.notificationApp = document.querySelector('[x-data="notificationApp()"]')?.__x?.$data;
            
            await this.loadData();
            
            this.lastRefresh = new Date();

            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                    e.preventDefault();
                    if (!this.showCreateModal && !this.showDetailModal) {
                        this.refreshData();
                    }
                }
            });

            this.$watch('barangItems', (newItems) => {
                newItems.forEach((item, index) => {
                    if (item.jumlah && parseFloat(item.jumlah) <= 0) {
                        this.barangItems[index].jumlah = '';
                    }
                });
            }, { deep: true });
            
            this.$watch('searchQuery', () => this.currentPage = 1);
            this.$watch('statusFilter', () => this.currentPage = 1);
            this.$watch('dateFilter', () => this.currentPage = 1);
            
            this.$watch('showCreateModal', async (value) => {
                if (value) {
                    console.log('?? Modal opened, loading nomor PB...');
                    await this.loadNomorPB();
                    
                    // Reset dropdown
                    this.clearBarangSearchState();
                    this.untukList = [];
                    this.selectedUntuk = null;
                    this.formData.untuk = '';
                    this.formData.untuk_id = '';
                    this.ensureFixedGudang();
                } else {
                    this.clearBarangSearchState();
                    console.log('?? Create modal closed, refreshing...');
                    setTimeout(() => this.loadData(), 100);
                }
            });
            
            this.$watch('showDetailModal', async (value) => {
                if (!value) {
                    console.log('?? Detail modal closed, refreshing...');
                    setTimeout(() => this.loadData(), 100);
                }
            });
        }
    }
}
</script>
@endsection