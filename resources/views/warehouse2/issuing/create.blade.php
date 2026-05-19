@extends('layouts.admin')

@section('title', 'Warehouse 2 - Keluar Barang Baru')

@push('styles')
<style>
    .compact-input {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }
    
    .table-header-compact {
        padding-top: 0.75rem !important;
        padding-bottom: 0.75rem !important;
    }
    
    .table-cell-compact {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }
    
    .search-dropdown {
        position: absolute;
        z-index: 9999;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        max-height: 16rem;
        overflow-y: auto;
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
</style>
@endpush

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-800">Warehouse 2 - Keluar Barang Baru</h1>
    <p class="text-sm text-gray-500 mt-1">Input pengeluaran barang</p>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6">
    <form id="issuingForm">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">Nomor Pengeluaran</label>
                <input type="text" value="{{ $issueNumber }}" readonly
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-800 font-mono text-sm compact-input">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Tanggal Pengeluaran</label>
                <input type="date" name="issue_date" id="issueDate" 
                       value="{{ date('Y-m-d') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input"
                       required>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Departemen</label>
                <input type="text" name="department" id="department"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input"
                       placeholder="Contoh: Produksi, Maintenance" required>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">Tujuan / Keperluan</label>
                <input type="text" name="purpose" id="purpose"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input"
                       placeholder="Contoh: Perbaikan mesin, Project" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Keterangan</label>
                <input type="text" name="notes" id="notes"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input"
                       placeholder="Tambahan keterangan (opsional)">
            </div>
        </div>
        
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-semibold">Detail Barang</h3>
            <button type="button" onclick="addItem()"
                    class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Tambah Barang
            </button>
        </div>
        
        <div class="overflow-x-auto border rounded-lg mb-4">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 border-r text-left text-xs font-medium text-gray-700 uppercase w-12 table-header-compact">No</th>
                        <th class="px-4 py-2 border-r text-left text-xs font-medium text-gray-700 uppercase min-w-80 table-header-compact">Pilih Barang</th>
                        <th class="px-4 py-2 border-r text-left text-xs font-medium text-gray-700 uppercase w-32 table-header-compact">Jumlah</th>
                        <th class="px-4 py-2 border-r text-left text-xs font-medium text-gray-700 uppercase min-w-64 table-header-compact">Keterangan</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase w-20 table-header-compact">Aksi</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <!-- Items will be added here dynamically -->
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="2" class="px-4 py-2 text-right font-semibold">Total</td>
                        <td class="px-4 py-2 text-right font-semibold" id="totalQuantity">0</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="flex justify-end gap-3">
            <a href="{{ route('warehouse2.issuing.index') }}" 
               class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded hover:bg-blue-700">
                Simpan Pengeluaran
            </button>
        </div>
    </form>
</div>

<!-- Search Dropdown Template -->
<div id="searchDropdown" class="hidden search-dropdown"></div>
@endsection

@push('scripts')
<script>
const items = @json($items);
let itemRows = [];
let nextRowId = 1;

document.getElementById('issuingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        issue_date: document.getElementById('issueDate').value,
        department: document.getElementById('department').value,
        purpose: document.getElementById('purpose').value,
        notes: document.getElementById('notes').value,
        items: itemRows.map(row => ({
            item_id: row.itemId,
            quantity: parseFloat(row.quantity),
            notes: row.notes
        })).filter(item => item.item_id && item.quantity > 0)
    };
    
    if (formData.items.length === 0) {
        alert('Minimal 1 barang harus diisi');
        return;
    }
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Menyimpan...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('/warehouse2/issuing', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('? Pengeluaran berhasil disimpan!');
            window.location.href = '{{ route("warehouse2.issuing.index") }}';
        } else {
            alert('? Gagal: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('? Terjadi kesalahan: ' + error.message);
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

function addItem() {
    const rowId = nextRowId++;
    const rowHtml = `
        <tr class="border-b hover:bg-gray-50" id="row-${rowId}">
            <td class="px-4 py-2 border-r text-center font-medium text-gray-900 text-sm table-cell-compact" id="row-${rowId}-no">${itemRows.length + 1}</td>
            <td class="px-4 py-2 border-r">
                <div class="relative">
                    <input type="text" 
                           id="row-${rowId}-search"
                           class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input"
                           placeholder="Ketik nama/kode barang..."
                           oninput="searchItem(${rowId}, this.value)"
                           onfocus="this.select()">
                    <input type="hidden" id="row-${rowId}-itemId">
                    <div id="row-${rowId}-results" class="hidden absolute z-50 bg-white border border-gray-300 rounded-lg shadow-xl max-h-48 overflow-y-auto w-full"></div>
                </div>
            </td>
            <td class="px-4 py-2 border-r">
                <input type="number" 
                       id="row-${rowId}-quantity"
                       class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm text-center compact-input"
                       step="0.01" min="0"
                       oninput="updateTotal()">
            </td>
            <td class="px-4 py-2 border-r">
                <input type="text" 
                       id="row-${rowId}-notes"
                       class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input"
                       placeholder="Keterangan (opsional)">
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" onclick="removeItem(${rowId})"
                        class="text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </td>
        </tr>
    `;
    
    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', rowHtml);
    
    itemRows.push({
        id: rowId,
        itemId: null,
        quantity: 0,
        notes: ''
    });
    
    updateRowNumbers();
}

function removeItem(rowId) {
    document.getElementById(`row-${rowId}`).remove();
    itemRows = itemRows.filter(row => row.id !== rowId);
    updateRowNumbers();
    updateTotal();
}

function updateRowNumbers() {
    itemRows.forEach((row, index) => {
        document.getElementById(`row-${row.id}-no`).textContent = index + 1;
    });
}

function searchItem(rowId, query) {
    const resultsDiv = document.getElementById(`row-${rowId}-results`);
    
    if (query.length < 2) {
        resultsDiv.classList.add('hidden');
        return;
    }
    
    const results = items.filter(item => 
        item.code.toLowerCase().includes(query.toLowerCase()) ||
        item.name.toLowerCase().includes(query.toLowerCase())
    ).slice(0, 10);
    
    if (results.length === 0) {
        resultsDiv.innerHTML = '<div class="p-2 text-gray-500 text-sm">Tidak ditemukan</div>';
        resultsDiv.classList.remove('hidden');
        return;
    }
    
    let html = '';
    results.forEach(item => {
        html += `
            <div class="p-2 hover:bg-blue-50 cursor-pointer border-b last:border-b-0"
                 onclick="selectItem(${rowId}, ${item.id}, '${item.code} - ${item.name}', ${item.stock})">
                <div class="font-medium text-sm">${item.code} - ${item.name}</div>
                <div class="text-xs text-gray-500">Satuan: ${item.unit} | Stok: ${formatNumber(item.stock)}</div>
            </div>
        `;
    });
    
    resultsDiv.innerHTML = html;
    resultsDiv.classList.remove('hidden');
}

function selectItem(rowId, itemId, displayText, stock) {
    document.getElementById(`row-${rowId}-search`).value = displayText;
    document.getElementById(`row-${rowId}-itemId`).value = itemId;
    document.getElementById(`row-${rowId}-results`).classList.add('hidden');
    
    const row = itemRows.find(r => r.id === rowId);
    if (row) {
        row.itemId = itemId;
        row.maxStock = stock;
    }
    
    // Validate quantity later
    const qtyInput = document.getElementById(`row-${rowId}-quantity`);
    qtyInput.max = stock;
    qtyInput.setAttribute('max', stock);
}

function updateTotal() {
    let total = 0;
    
    itemRows.forEach(row => {
        const qty = parseFloat(document.getElementById(`row-${row.id}-quantity`).value) || 0;
        row.quantity = qty;
        total += qty;
        
        // Validate against stock
        if (row.maxStock && qty > row.maxStock) {
            alert(`Stok tidak mencukupi. Maksimal ${formatNumber(row.maxStock)}`);
            document.getElementById(`row-${row.id}-quantity`).value = row.maxStock;
            row.quantity = row.maxStock;
            total = total - qty + row.maxStock;
        }
    });
    
    document.getElementById('totalQuantity').textContent = formatNumber(total);
}

function formatNumber(angka) {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(angka);
}

// Hide search results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('[id$="-search"]') && !e.target.closest('[id$="-results"]')) {
        document.querySelectorAll('[id$="-results"]').forEach(el => {
            el.classList.add('hidden');
        });
    }
});

// Add first row by default
addItem();
</script>
@endpush