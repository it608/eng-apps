@extends('layouts.admin')

@section('title', 'Warehouse 2 - Terima Barang Baru')

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

    .border-red-500 {
        border-color: #ef4444 !important;
        background-color: #fef2f2;
    }

    .error-message {
        color: #ef4444;
        font-size: 0.75rem;
        margin-top: 0.25rem;
        display: none;
    }

    .error-message.visible {
        display: block;
    }

    /* Loading indicator untuk search */
    .search-loading {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3b82f6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        display: none;
    }

    .search-loading.visible {
        display: block;
    }
</style>
@endpush

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-800">Warehouse 2 - Terima Barang Baru</h1>
    <p class="text-sm text-gray-500 mt-1">Input penerimaan barang masuk</p>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6">
    <form id="receivingForm">
        @csrf
        <!-- Hidden input untuk receipt number -->
        <input type="hidden" name="receipt_number" value="{{ $receiptNumber }}">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">Nomor Penerimaan</label>
                <input type="text" value="{{ $receiptNumber }}" readonly
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-800 font-mono text-sm compact-input">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Tanggal Penerimaan</label>
                <input type="date" name="receipt_date" id="receiptDate" 
                       value="{{ date('Y-m-d') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input"
                       required>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Supplier</label>
                <input type="text" name="supplier" id="supplier"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input"
                       placeholder="Nama supplier" required>
            </div>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Keterangan</label>
            <textarea name="notes" id="notes" rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm"
                      placeholder="Tambahkan keterangan (opsional)"></textarea>
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
                        <th class="px-4 py-2 border-r text-left text-xs font-medium text-gray-700 uppercase w-40 table-header-compact">Harga Satuan</th>
                        <th class="px-4 py-2 border-r text-left text-xs font-medium text-gray-700 uppercase w-40 table-header-compact">Total</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase w-20 table-header-compact">Aksi</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <!-- Items will be added here dynamically -->
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right font-semibold">Total</td>
                        <td class="px-4 py-2 text-right font-semibold" id="totalValue">Rp 0</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div id="formError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 hidden">
            <!-- Error messages will be displayed here -->
        </div>
        
        <div class="flex justify-end gap-3">
            <a href="{{ route('warehouse2.receiving.index') }}" 
               class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded hover:bg-blue-700">
                Simpan Penerimaan
            </button>
        </div>
    </form>
</div>

<!-- Search Dropdown Template -->
<div id="searchDropdown" class="hidden search-dropdown"></div>
@endsection

@push('scripts')
<script>
// ============== CEK DATA ITEMS ==============
console.log('Data items dari controller:', @json($items));
const items = @json($items);

if (!items || items.length === 0) {
    console.error('TIDAK ADA DATA BARANG! Cek database warehouse2_items');
    alert('?? PERINGATAN: Data barang kosong! Hubungi administrator.');
} else {
    console.log(`? Loaded ${items.length} items:`, items.map(i => `${i.code} - ${i.name}`));
}

let itemRows = [];
let nextRowId = 1;
let searchTimeout = null;

// ============== INITIALIZE ==============
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, menambahkan baris pertama...');
    addItem();
    
    // Set default supplier jika kosong
    if (!document.getElementById('supplier').value) {
        document.getElementById('supplier').value = 'Internal';
    }
});

// ============== FORM SUBMIT HANDLER ==============
document.getElementById('receivingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Sembunyikan error sebelumnya
    hideFormError();
    
    // Debug: lihat isi itemRows
    console.log('ITEM ROWS SEBELUM FILTER:', JSON.stringify(itemRows, null, 2));
    
    // ============== VALIDASI MANUAL ==============
    let hasErrors = false;
    let errorMessages = [];
    
    // Validasi header
    if (!document.getElementById('receiptDate').value) {
        hasErrors = true;
        errorMessages.push('Tanggal penerimaan harus diisi');
    }
    
    if (!document.getElementById('supplier').value) {
        hasErrors = true;
        errorMessages.push('Supplier harus diisi');
    }
    
    // Validasi items
    if (itemRows.length === 0) {
        hasErrors = true;
        errorMessages.push('Minimal 1 barang harus ditambahkan');
    } else {
        // Reset semua validasi visual
        itemRows.forEach(row => {
            const searchInput = document.getElementById(`row-${row.id}-search`);
            const quantityInput = document.getElementById(`row-${row.id}-quantity`);
            const priceInput = document.getElementById(`row-${row.id}-price`);
            
            if (searchInput) searchInput.classList.remove('border-red-500');
            if (quantityInput) quantityInput.classList.remove('border-red-500');
            if (priceInput) priceInput.classList.remove('border-red-500');
        });
        
        // Validasi setiap row
        itemRows.forEach((row, index) => {
            const searchInput = document.getElementById(`row-${row.id}-search`);
            const quantityInput = document.getElementById(`row-${row.id}-quantity`);
            const priceInput = document.getElementById(`row-${row.id}-price`);
            
            // Cek apakah barang dipilih
            if (!row.itemId) {
                hasErrors = true;
                errorMessages.push(`Baris ${index + 1}: Pilih barang terlebih dahulu`);
                if (searchInput) searchInput.classList.add('border-red-500');
            }
            
            // Cek quantity
            if (!row.quantity || row.quantity <= 0) {
                hasErrors = true;
                errorMessages.push(`Baris ${index + 1}: Jumlah harus diisi dan lebih dari 0`);
                if (quantityInput) quantityInput.classList.add('border-red-500');
            }
            
            // Cek price
            if (row.price === undefined || row.price === null || row.price < 0) {
                hasErrors = true;
                errorMessages.push(`Baris ${index + 1}: Harga satuan harus diisi`);
                if (priceInput) priceInput.classList.add('border-red-500');
            }
        });
    }
    
    // Jika ada error, tampilkan dan stop
    if (hasErrors) {
        showFormError(errorMessages);
        return;
    }
    
    // ============== KUMPULKAN DATA VALID ==============
    const validItems = itemRows
        .map(row => {
            // Pastikan data tipe nya benar
            const itemId = parseInt(row.itemId);
            const quantity = parseFloat(row.quantity) || 0;
            const price = parseFloat(row.price) || 0;
            
            return {
                item_id: itemId,
                quantity: quantity,
                unit_price: price
            };
        })
        .filter(item => {
            // Filter hanya yang valid
            return !isNaN(item.item_id) && 
                   item.item_id > 0 && 
                   !isNaN(item.quantity) && 
                   item.quantity > 0 &&
                   !isNaN(item.unit_price) && 
                   item.unit_price >= 0;
        });
    
    console.log('VALID ITEMS:', validItems);
    
    if (validItems.length === 0) {
        showFormError(['Tidak ada barang valid yang bisa disimpan']);
        return;
    }
    
    // ============== FORM DATA ==============
    const formData = {
        receipt_number: document.querySelector('input[name="receipt_number"]').value,
        receipt_date: document.getElementById('receiptDate').value,
        supplier: document.getElementById('supplier').value,
        notes: document.getElementById('notes').value,
        items: validItems
    };
    
    console.log('FORM DATA DIKIRIM:', formData);
    
    // ============== SUBMIT KE SERVER ==============
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Menyimpan...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('/warehouse2/receiving', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        console.log('RESPONSE SERVER:', result);
        
        if (result.success) {
            alert('? Penerimaan berhasil disimpan!');
            window.location.href = '{{ route("warehouse2.receiving.index") }}';
        } else {
            let errorMsg = '? Gagal: ' + (result.message || 'Unknown error');
            if (result.errors) {
                errorMsg += '\n\nDetail:';
                Object.keys(result.errors).forEach(key => {
                    errorMsg += `\n- ${key}: ${result.errors[key].join(', ')}`;
                });
            }
            alert(errorMsg);
        }
    } catch (error) {
        console.error('ERROR:', error);
        alert('? Terjadi kesalahan: ' + error.message);
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// ============== FUNGSI TAMBAH BARIS ==============
function addItem() {
    const rowId = nextRowId++;
    console.log('Menambah baris baru dengan ID:', rowId);
    
    const rowHtml = `
        <tr class="border-b hover:bg-gray-50" id="row-${rowId}">
            <td class="px-4 py-2 border-r text-center font-medium text-gray-900 text-sm table-cell-compact" id="row-${rowId}-no">${itemRows.length + 1}</td>
            <td class="px-4 py-2 border-r">
                <div class="relative">
                    <input type="text" 
                           id="row-${rowId}-search"
                           class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm compact-input"
                           placeholder="Ketik minimal 2 huruf..."
                           onkeyup="searchItem(${rowId}, this.value)"
                           onfocus="this.select()"
                           autocomplete="off">
                    <input type="hidden" id="row-${rowId}-itemId">
                    <div id="row-${rowId}-results" class="hidden absolute z-50 bg-white border border-gray-300 rounded-lg shadow-xl max-h-48 overflow-y-auto w-full" style="width: calc(100% - 2px);"></div>
                </div>
            </td>
            <td class="px-4 py-2 border-r">
                <input type="number" 
                       id="row-${rowId}-quantity"
                       class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm text-center compact-input"
                       step="0.01" min="0.01"
                       oninput="calculateRowTotal(${rowId})">
            </td>
            <td class="px-4 py-2 border-r">
                <input type="number" 
                       id="row-${rowId}-price"
                       class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm text-right compact-input"
                       step="1" min="0"
                       oninput="calculateRowTotal(${rowId})">
            </td>
            <td class="px-4 py-2 border-r text-right font-medium" id="row-${rowId}-total">Rp 0</td>
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
        price: 0
    });
    
    updateRowNumbers();
}

// ============== FUNGSI SEARCH BARANG ==============
function searchItem(rowId, query) {
    console.log(`Search item: row=${rowId}, query="${query}"`);
    
    const resultsDiv = document.getElementById(`row-${rowId}-results`);
    
    if (!resultsDiv) {
        console.error(`Results div untuk row ${rowId} tidak ditemukan!`);
        return;
    }
    
    // Clear previous timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    if (query.length < 2) {
        resultsDiv.classList.add('hidden');
        resultsDiv.innerHTML = '';
        return;
    }
    
    // Add loading indicator
    resultsDiv.innerHTML = '<div class="p-3 text-center"><div class="spinner" style="width: 20px; height: 20px;"></div><p class="text-xs mt-1">Mencari...</p></div>';
    resultsDiv.classList.remove('hidden');
    
    // Debounce search
    searchTimeout = setTimeout(() => {
        // Filter items berdasarkan query
        const results = items.filter(item => {
            if (!item || !item.code || !item.name) return false;
            return item.code.toLowerCase().includes(query.toLowerCase()) ||
                   item.name.toLowerCase().includes(query.toLowerCase());
        }).slice(0, 10);
        
        console.log(`Ditemukan ${results.length} barang`);
        
        if (results.length === 0) {
            resultsDiv.innerHTML = '<div class="p-3 text-gray-500 text-sm text-center">Tidak ada barang ditemukan</div>';
            return;
        }
        
        let html = '';
        results.forEach(item => {
            const stockDisplay = item.stock !== undefined ? item.stock : 0;
            html += `
                <div class="p-3 hover:bg-blue-50 cursor-pointer border-b last:border-b-0 transition-colors"
                     onclick="selectItem(${rowId}, ${item.id}, '${item.code} - ${item.name}')">
                    <div class="font-medium text-sm">${item.code} - ${item.name}</div>
                    <div class="text-xs text-gray-500 mt-1">
                        <span>Satuan: ${item.unit || '-'}</span> | 
                        <span>Stok: ${stockDisplay}</span>
                    </div>
                </div>
            `;
        });
        
        resultsDiv.innerHTML = html;
    }, 300); // Delay 300ms untuk mengurangi beban
}

// ============== FUNGSI PILIH BARANG ==============
function selectItem(rowId, itemId, displayText) {
    console.log(`Memilih barang: row=${rowId}, itemId=${itemId}, text="${displayText}"`);
    
    const searchInput = document.getElementById(`row-${rowId}-search`);
    const itemIdInput = document.getElementById(`row-${rowId}-itemId`);
    const resultsDiv = document.getElementById(`row-${rowId}-results`);
    
    if (searchInput) {
        searchInput.value = displayText;
        searchInput.classList.remove('border-red-500');
    }
    
    if (itemIdInput) {
        itemIdInput.value = itemId;
    }
    
    if (resultsDiv) {
        resultsDiv.classList.add('hidden');
        resultsDiv.innerHTML = '';
    }
    
    const row = itemRows.find(r => r.id === rowId);
    if (row) {
        row.itemId = itemId;
    }
    
    // Auto focus ke input quantity
    setTimeout(() => {
        document.getElementById(`row-${rowId}-quantity`)?.focus();
    }, 100);
}

// ============== FUNGSI HAPUS BARIS ==============
function removeItem(rowId) {
    console.log('Menghapus baris:', rowId);
    document.getElementById(`row-${rowId}`).remove();
    itemRows = itemRows.filter(row => row.id !== rowId);
    updateRowNumbers();
    calculateGrandTotal();
}

// ============== FUNGSI UPDATE NOMOR URUT ==============
function updateRowNumbers() {
    itemRows.forEach((row, index) => {
        const noCell = document.getElementById(`row-${row.id}-no`);
        if (noCell) {
            noCell.textContent = index + 1;
        }
    });
}

// ============== FUNGSI HITUNG TOTAL PER BARIS ==============
function calculateRowTotal(rowId) {
    const quantity = parseFloat(document.getElementById(`row-${rowId}-quantity`).value) || 0;
    const price = parseFloat(document.getElementById(`row-${rowId}-price`).value) || 0;
    const total = quantity * price;
    
    document.getElementById(`row-${rowId}-total`).textContent = formatRupiah(total);
    
    const row = itemRows.find(r => r.id === rowId);
    if (row) {
        row.quantity = quantity;
        row.price = price;
    }
    
    calculateGrandTotal();
}

// ============== FUNGSI HITUNG TOTAL SEMUA ==============
function calculateGrandTotal() {
    const total = itemRows.reduce((sum, row) => {
        return sum + (row.quantity * row.price);
    }, 0);
    
    document.getElementById('totalValue').textContent = formatRupiah(total);
}

// ============== FUNGSI FORMAT RUPIAH ==============
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(angka);
}

// ============== FUNGSI TAMPILKAN ERROR ==============
function showFormError(messages) {
    const errorDiv = document.getElementById('formError');
    if (!errorDiv) return;
    
    if (Array.isArray(messages)) {
        errorDiv.innerHTML = messages.map(msg => `<div class="mb-1">• ${msg}</div>`).join('');
    } else {
        errorDiv.innerHTML = `<div>• ${messages}</div>`;
    }
    
    errorDiv.classList.remove('hidden');
    
    // Scroll ke error
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// ============== FUNGSI SEMBUNYIKAN ERROR ==============
function hideFormError() {
    const errorDiv = document.getElementById('formError');
    if (errorDiv) {
        errorDiv.classList.add('hidden');
        errorDiv.innerHTML = '';
    }
}

// ============== HIDE SEARCH RESULTS WHEN CLICKING OUTSIDE ==============
document.addEventListener('click', function(e) {
    if (!e.target.closest('[id$="-search"]') && !e.target.closest('[id$="-results"]')) {
        document.querySelectorAll('[id$="-results"]').forEach(el => {
            el.classList.add('hidden');
        });
    }
});

// ============== DEBUG AUTO - LOG SETIAP 10 DETIK ==============
setInterval(function() {
    if (itemRows.length > 0) {
        console.log('DEBUG - Current itemRows:', itemRows.map(row => ({
            id: row.id,
            itemId: row.itemId,
            quantity: row.quantity,
            price: row.price
        })));
    }
}, 10000);

// ============== HANDLE ENTER KEY ==============
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
    }
});
</script>
@endpush