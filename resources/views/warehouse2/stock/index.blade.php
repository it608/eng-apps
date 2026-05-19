@extends('layouts.admin')

@section('title', 'Warehouse 2 - Stock')

@push('styles')
<style>
    .tab-btn {
        padding: 0.75rem 1.25rem;
        font-size: 0.95rem;
        font-weight: 600;
        border-bottom: 2px solid transparent;
        color: #6b7280;
        transition: all .2s ease;
    }
    .tab-btn:hover {
        color: #374151;
        border-bottom-color: #d1d5db;
    }
    .tab-btn.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
        background: linear-gradient(to bottom, #eff6ff, transparent);
    }

    .table-bordered {
        border-collapse: collapse;
    }
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #d1d5db;
        padding: 0.75rem 1rem;
        font-size: 0.75rem;
        line-height: 1.25rem;
    }

    .col-filter {
        width: 100%;
        padding: 4px 6px;
        font-size: 11px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        background: #fff;
    }

    .badge {
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-success {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .badge-warning {
        background-color: #fed7aa;
        color: #92400e;
    }
    
    .badge-danger {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .summary-card {
        @apply bg-white rounded-lg border p-4 transition-all hover:shadow-md;
    }
    
    .summary-card .label {
        @apply text-xs text-gray-500 uppercase tracking-wider;
    }
    
    .summary-card .value {
        @apply text-2xl font-semibold mt-1;
    }

    .progress-bar {
        width: 100%;
        height: 6px;
        background-color: #e5e7eb;
        border-radius: 9999px;
        overflow: hidden;
        margin-top: 4px;
    }
    
    .progress-fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 0.3s ease;
    }
    
    .progress-low {
        background-color: #ef4444;
    }
    
    .progress-medium {
        background-color: #f59e0b;
    }
    
    .progress-high {
        background-color: #10b981;
    }

    .modal {
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
    }
    
    .fade-in {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3b82f6;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 20px auto;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .tooltip {
        position: relative;
        display: inline-block;
    }
    
    .tooltip .tooltiptext {
        visibility: hidden;
        width: 200px;
        background-color: #1f2937;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px;
        position: absolute;
        z-index: 1000;
        bottom: 125%;
        left: 50%;
        margin-left: -100px;
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 11px;
        pointer-events: none;
    }
    
    .tooltip:hover .tooltiptext {
        visibility: visible;
        opacity: 1;
    }
</style>
@endpush

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-800">Warehouse 2 - Stock Sparepart</h1>
    <p class="text-sm text-gray-500 mt-1">Monitoring stok sparepart Warehouse 2</p>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="summary-card">
        <div class="label">Total Item</div>
        <div class="value text-gray-800" id="totalItems">0</div>
        <div class="text-xs text-gray-500 mt-1">Unique sparepart</div>
    </div>
    
    <div class="summary-card">
        <div class="label">Total Stok</div>
        <div class="value text-blue-600" id="totalStock">0</div>
        <div class="text-xs text-gray-500 mt-1">Semua lokasi</div>
    </div>
    
    <div class="summary-card">
        <div class="label">Stok Menipis</div>
        <div class="value text-orange-500" id="lowStock">0</div>
        <div class="text-xs text-gray-500 mt-1">Di bawah minimum</div>
    </div>
    
    <div class="summary-card">
        <div class="label">Stok Habis</div>
        <div class="value text-red-500" id="outOfStock">0</div>
        <div class="text-xs text-gray-500 mt-1">Perlu re-order</div>
    </div>
    
    <div class="summary-card">
        <div class="label">Lokasi</div>
        <div class="value text-purple-600" id="totalLocations">0</div>
        <div class="text-xs text-gray-500 mt-1">Total gudang</div>
    </div>
</div>

<!-- Main Card -->
<div class="bg-white rounded-xl shadow-sm border p-6">
    
    <!-- Filter Bar -->
    <div class="mb-4 flex flex-wrap gap-3 items-center">
        <div class="flex-1 min-w-[200px]">
            <input id="stockSearch" class="border rounded-lg px-3 py-2 w-full" placeholder="Cari kode / nama sparepart...">
        </div>
        
        <select id="stockLocation" class="border rounded-lg px-3 py-2 w-40">
            <option value="">Semua Lokasi</option>
            @foreach($locations ?? [] as $loc)
                <option value="{{ $loc->location }}">{{ $loc->location }}</option>
            @endforeach
        </select>
        
        <select id="stockStatus" class="border rounded-lg px-3 py-2 w-40">
            <option value="">Semua Status</option>
            <option value="aman">Aman</option>
            <option value="menipis">Menipis</option>
            <option value="habis">Habis</option>
        </select>
        
        <select id="stockPerPage" class="border rounded-lg px-3 py-2 w-28">
            <option value="10">10</option>
            <option value="20" selected>20</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
        
        <button onclick="resetFilters()" class="px-3 py-2 text-sm border rounded-lg hover:bg-gray-50 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Reset
        </button>
        
        <button onclick="exportData()" class="px-3 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            Export
        </button>
    </div>

    <!-- Stock Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full table-bordered" id="stockTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-center w-12">No</th>
                    <th>Kode</th>
                    <th>Nama Sparepart</th>
                    <th>Kategori</th>
                    <th class="text-center">Satuan</th>
                    <th class="text-center">Stok</th>
                    <th class="text-center">Min</th>
                    <th class="text-center">Max</th>
                    <th class="text-center">Status</th>
                    <th>Lokasi</th>
                    <th class="text-center">Terakhir Update</th>
                    <th class="text-center">Aksi</th>
                </tr>
                <tr>
                    <th></th>
                    <th><input type="text" class="col-filter" data-key="code" placeholder="Filter..."></th>
                    <th><input type="text" class="col-filter" data-key="name" placeholder="Filter..."></th>
                    <th><input type="text" class="col-filter" data-key="category" placeholder="Filter..."></th>
                    <th><select class="col-filter" data-key="unit"><option value="">All</option></select></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th><select class="col-filter" data-key="status"><option value="">All</option></select></th>
                    <th><input type="text" class="col-filter" data-key="location" placeholder="Filter..."></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="stockBody">
                <tr>
                    <td colspan="12" class="text-center py-8">
                        <div class="spinner"></div>
                        <p class="text-sm text-gray-500 mt-2">Memuat data stok...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div id="stockPaging" class="mt-4 flex justify-between text-sm"></div>
</div>

<!-- Modal Detail Item -->
<div id="detailModal" class="fixed inset-0 modal hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl w-full max-w-4xl p-6 relative fade-in max-h-[80vh] overflow-y-auto">
        <h2 class="text-lg font-semibold mb-4" id="detailTitle">Detail Item</h2>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="text-xs text-gray-500">Kode</label>
                <div id="detailCode" class="font-medium">-</div>
            </div>
            <div>
                <label class="text-xs text-gray-500">Nama</label>
                <div id="detailName" class="font-medium">-</div>
            </div>
            <div>
                <label class="text-xs text-gray-500">Kategori</label>
                <div id="detailCategory" class="font-medium">-</div>
            </div>
            <div>
                <label class="text-xs text-gray-500">Satuan</label>
                <div id="detailUnit" class="font-medium">-</div>
            </div>
            <div>
                <label class="text-xs text-gray-500">Stok</label>
                <div id="detailStock" class="font-medium">-</div>
            </div>
            <div>
                <label class="text-xs text-gray-500">Lokasi</label>
                <div id="detailLocation" class="font-medium">-</div>
            </div>
            <div>
                <label class="text-xs text-gray-500">Min Stok</label>
                <div id="detailMinStock" class="font-medium">-</div>
            </div>
            <div>
                <label class="text-xs text-gray-500">Max Stok</label>
                <div id="detailMaxStock" class="font-medium">-</div>
            </div>
        </div>
        
        <h3 class="font-semibold text-sm mb-2">Histori Transaksi</h3>
        <div class="overflow-x-auto max-h-60">
            <table class="min-w-full text-sm border">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="p-2 text-left">Tanggal</th>
                        <th class="p-2 text-left">Dokumen</th>
                        <th class="p-2 text-left">Tipe</th>
                        <th class="p-2 text-right">Qty</th>
                        <th class="p-2 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody id="detailHistory">
                    <tr>
                        <td colspan="5" class="text-center py-4 text-gray-500">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="flex justify-end mt-4">
            <button onclick="closeDetailModal()" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Tutup</button>
        </div>
        
        <button onclick="closeDetailModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 font-bold text-xl">×</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
// State
let currentPage = 1;
let perPage = 20;
let filteredData = [];
let filters = {};

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    loadStockData();
    initEventListeners();
});

function initEventListeners() {
    // Search with debounce
    document.getElementById('stockSearch').addEventListener('input', debounce(function() {
        currentPage = 1;
        loadStockData();
    }, 500));
    
    // Per Page
    document.getElementById('stockPerPage').addEventListener('change', function() {
        perPage = parseInt(this.value) || 20;
        currentPage = 1;
        loadStockData();
    });
    
    // Location filter
    document.getElementById('stockLocation').addEventListener('change', function() {
        currentPage = 1;
        loadStockData();
    });
    
    // Status filter
    document.getElementById('stockStatus').addEventListener('change', function() {
        currentPage = 1;
        loadStockData();
    });
    
    // Column filters
    document.querySelectorAll('.col-filter').forEach(el => {
        el.addEventListener('change', function() {
            const key = this.dataset.key;
            if (this.value) {
                filters[key] = this.value;
            } else {
                delete filters[key];
            }
            currentPage = 1;
            loadStockData();
        });
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function loadStockData() {
    showLoading();
    
    const params = new URLSearchParams({
        page: currentPage,
        per_page: perPage,
        search: document.getElementById('stockSearch').value,
        location: document.getElementById('stockLocation').value,
        status: document.getElementById('stockStatus').value
    });
    
    // Add column filters
    Object.entries(filters).forEach(([key, value]) => {
        params.append(`filter_${key}`, value);
    });
    
    fetch(`/warehouse2/stock/data?${params.toString()}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                filteredData = result.data;
                updateSummaryCards(result.summary);
                renderStockTable();
                updatePagination(result.pagination);
                populateColumnFilters();
            } else {
                showError(result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Gagal memuat data: ' + error.message);
        });
}

function showLoading() {
    document.getElementById('stockBody').innerHTML = `
        <tr>
            <td colspan="12" class="text-center py-8">
                <div class="spinner"></div>
                <p class="text-sm text-gray-500 mt-2">Memuat data stok...</p>
            </td>
        </tr>
    `;
}

function showError(message) {
    document.getElementById('stockBody').innerHTML = `
        <tr>
            <td colspan="12" class="text-center py-8 text-red-500">
                <svg class="w-12 h-12 mx-auto mb-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p>${message}</p>
                <button onclick="loadStockData()" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Coba Lagi
                </button>
            </td>
        </tr>
    `;
}

function renderStockTable() {
    if (!filteredData || filteredData.length === 0) {
        document.getElementById('stockBody').innerHTML = `
            <tr>
                <td colspan="12" class="text-center py-8 text-gray-500">
                    Tidak ada data stok
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    filteredData.forEach((item, index) => {
        const statusBadge = getStatusBadge(item.status);
        const stockPercentage = Math.min((item.stock / item.max_stock) * 100, 100);
        const progressClass = getProgressClass(item.stock, item.min_stock, item.max_stock);
        
        html += '<tr class="hover:bg-gray-50">';
        html += `<td class="text-center">${(currentPage - 1) * perPage + index + 1}</td>`;
        html += `<td class="font-mono text-xs">${item.code || '-'}</td>`;
        html += `<td><div class="tooltip">${(item.name || '').substring(0, 40)}${item.name && item.name.length > 40 ? '...' : ''}<span class="tooltiptext">${item.name || ''}</span></div></td>`;
        html += `<td>${item.category || '-'}</td>`;
        html += `<td class="text-center">${item.unit || '-'}</td>`;
        html += `<td class="text-right font-semibold">
                    ${formatNumber(item.stock)}
                    <div class="progress-bar">
                        <div class="progress-fill ${progressClass}" style="width: ${stockPercentage}%"></div>
                    </div>
                 </td>`;
        html += `<td class="text-center">${formatNumber(item.min_stock)}</td>`;
        html += `<td class="text-center">${formatNumber(item.max_stock)}</td>`;
        html += `<td class="text-center">${statusBadge}</td>`;
        html += `<td>${item.location || '-'}</td>`;
        html += `<td class="text-center">${item.last_update || '-'}</td>`;
        html += `<td class="text-center">
                    <button onclick='showDetail(${JSON.stringify(item)})' class="text-blue-600 hover:text-blue-800 mx-1" title="Detail">
                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                 </td>`;
        html += '</tr>';
    });
    
    document.getElementById('stockBody').innerHTML = html;
}

function updateSummaryCards(summary) {
    if (!summary) return;
    
    document.getElementById('totalItems').textContent = formatNumber(summary.total_items || 0);
    document.getElementById('totalStock').textContent = formatNumber(summary.total_stock || 0);
    document.getElementById('lowStock').textContent = formatNumber(summary.low_stock || 0);
    document.getElementById('outOfStock').textContent = formatNumber(summary.out_of_stock || 0);
    
    // Count unique locations from filtered data
    const locations = [...new Set(filteredData.map(item => item.location).filter(Boolean))];
    document.getElementById('totalLocations').textContent = locations.length;
}

function formatNumber(angka) {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(angka);
}

function getStatusBadge(status) {
    const badges = {
        'aman': '<span class="badge badge-success">Aman</span>',
        'menipis': '<span class="badge badge-warning">Menipis</span>',
        'habis': '<span class="badge badge-danger">Habis</span>'
    };
    return badges[status] || badges.aman;
}

function getProgressClass(stock, min, max) {
    if (stock <= 0) return 'progress-low';
    const percentage = (stock / max) * 100;
    if (percentage < 30) return 'progress-low';
    if (percentage < 70) return 'progress-medium';
    return 'progress-high';
}

function updatePagination(pagination) {
    const pagingEl = document.getElementById('stockPaging');
    
    pagingEl.innerHTML = `
        <span class="text-gray-600">Halaman ${pagination.current_page} dari ${pagination.last_page} (${pagination.total} data)</span>
        <div class="space-x-2">
            <button ${pagination.current_page === 1 ? 'disabled' : ''} 
                onclick="changePage(${pagination.current_page - 1})"
                class="px-3 py-1 border rounded ${pagination.current_page === 1 ? 'bg-gray-100 text-gray-400' : 'bg-white text-gray-700 hover:bg-gray-50'}">
                Prev
            </button>
            <button ${pagination.current_page === pagination.last_page ? 'disabled' : ''} 
                onclick="changePage(${pagination.current_page + 1})"
                class="px-3 py-1 border rounded ${pagination.current_page === pagination.last_page ? 'bg-gray-100 text-gray-400' : 'bg-white text-gray-700 hover:bg-gray-50'}">
                Next
            </button>
        </div>
    `;
}

function changePage(newPage) {
    currentPage = newPage;
    loadStockData();
}

function populateColumnFilters() {
    if (!filteredData || filteredData.length === 0) return;
    
    const unitOptions = [...new Set(filteredData.map(item => item.unit).filter(Boolean))];
    const statusOptions = [...new Set(filteredData.map(item => item.status).filter(Boolean))];
    
    populateSelect('select[data-key="unit"]', unitOptions);
    populateSelect('select[data-key="status"]', statusOptions);
}

function populateSelect(selector, options) {
    const select = document.querySelector(selector);
    if (!select) return;
    
    const currentValue = select.value;
    select.innerHTML = '<option value="">All</option>';
    
    options.sort().forEach(opt => {
        const option = document.createElement('option');
        option.value = opt;
        option.textContent = opt;
        if (opt === currentValue) option.selected = true;
        select.appendChild(option);
    });
}

function resetFilters() {
    document.getElementById('stockSearch').value = '';
    document.getElementById('stockLocation').value = '';
    document.getElementById('stockStatus').value = '';
    
    document.querySelectorAll('.col-filter').forEach(el => {
        if (el.tagName === 'SELECT') {
            el.value = '';
        } else {
            el.value = '';
        }
    });
    
    filters = {};
    currentPage = 1;
    loadStockData();
}

function exportData() {
    const params = new URLSearchParams({
        search: document.getElementById('stockSearch').value,
        location: document.getElementById('stockLocation').value,
        status: document.getElementById('stockStatus').value
    });
    
    window.location.href = `/warehouse2/stock/export?${params.toString()}`;
}

function showDetail(item) {
    document.getElementById('detailTitle').textContent = 'Detail: ' + item.code;
    document.getElementById('detailCode').textContent = item.code;
    document.getElementById('detailName').textContent = item.name;
    document.getElementById('detailCategory').textContent = item.category || '-';
    document.getElementById('detailUnit').textContent = item.unit;
    document.getElementById('detailStock').textContent = formatNumber(item.stock);
    document.getElementById('detailLocation').textContent = item.location || '-';
    document.getElementById('detailMinStock').textContent = formatNumber(item.min_stock);
    document.getElementById('detailMaxStock').textContent = formatNumber(item.max_stock);
    
    // Load history
    fetch(`/warehouse2/stock/${item.id}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                renderDetailHistory(result.data.history);
            }
        })
        .catch(error => console.error('Error loading history:', error));
    
    document.getElementById('detailModal').classList.remove('hidden');
    document.getElementById('detailModal').classList.add('flex');
}

function renderDetailHistory(history) {
    const historyBody = document.getElementById('detailHistory');
    
    if (!history || history.length === 0) {
        historyBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">Tidak ada histori transaksi</td></tr>';
        return;
    }
    
    let html = '';
    history.forEach(item => {
        const typeBadge = item.type === 'TERIMA' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700';
        
        html += '<tr>';
        html += `<td class="p-2">${item.date}</td>`;
        html += `<td class="p-2">${item.document}</td>`;
        html += `<td class="p-2"><span class="px-2 py-1 text-xs rounded-full ${typeBadge}">${item.type}</span></td>`;
        html += `<td class="p-2 text-right">${formatNumber(item.quantity)}</td>`;
        html += `<td class="p-2">${item.notes || item.purpose || '-'}</td>`;
        html += '</tr>';
    });
    
    historyBody.innerHTML = html;
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
    document.getElementById('detailModal').classList.remove('flex');
}

// Tab handling
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-content').forEach(el => {
            el.classList.add('hidden');
        });
        
        const tabId = this.dataset.tab;
        document.getElementById(tabId).classList.remove('hidden');
        
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active');
        });
        this.classList.add('active');
    });
});

// Modal click outside
window.addEventListener('click', function(e) {
    if (e.target.id === 'detailModal') {
        closeDetailModal();
    }
});
</script>
@endpush