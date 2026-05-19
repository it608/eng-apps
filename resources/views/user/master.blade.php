@extends('layouts.admin')

@section('title', 'Master Data')

@push('styles')
<style>
    .tab-btn{
        padding: 0.75rem 1.25rem;
        font-size: 0.95rem;
        font-weight: 600;
        border-bottom: 2px solid transparent;
        color: #6b7280;
        transition: all .2s ease;
    }
    .tab-btn:hover{
        color: #374151;
        border-bottom-color: #d1d5db;
    }
    .tab-btn.active{
        color: #2563eb;
        border-bottom-color: #2563eb;
        background: linear-gradient(to bottom, #eff6ff, transparent);
    }

    .table-bordered{
        border-collapse: collapse;
    }
    .table-bordered th,
    .table-bordered td{
        border: 1px solid #d1d5db;
        padding: 0.75rem 1.5rem;
        font-size: 0.75rem;
        line-height: 1.25rem;
    }

    .col-filter{
        width:100%;
        padding:4px 6px;
        font-size:11px;
        border:1px solid #d1d5db;
        border-radius:4px;
        background:#fff;
    }

    /* Modal */
    #editMesinModal, #editBangunanModal{
        background: rgba(0,0,0,0.5);
    }
</style>
@endpush

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-800">Master Data</h1>
    <p class="text-sm text-gray-500 mt-1">List Data untuk ruang lingkup Engineering</p>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6">

    {{-- TAB --}}
    <div class="mb-4 border-b border-gray-200">
        <ul class="flex -mb-px text-sm font-medium">
            <li class="mr-2">
                <button class="tab-btn active" data-tab="master-sparepart">Master Sparepart</button>
            </li>
            <li class="mr-2">
                <button class="tab-btn" data-tab="master-mesin">Master Mesin</button>
            </li>
            <li class="mr-2">
                <button class="tab-btn" data-tab="master-bangunan">Master Bangunan</button>
            </li>
        </ul>
    </div>

    {{-- ================= MASTER SPAREPART ================= --}}
    <div id="master-sparepart" class="tab-content">

        <div class="mb-4 flex justify-between gap-4">
            <input id="spSearch" class="border rounded-lg px-3 py-2 w-64" placeholder="Cari kode / nama...">
            <select id="spPerPage" class="border rounded-lg px-3 py-2 w-28">
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="0">Semua</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full table-bordered" id="spTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th><select class="col-filter" data-key="code"><option value="">All</option></select></th>
                        <th><select class="col-filter" data-key="name"><option value="">All</option></select></th>
                        <th><select class="col-filter" data-key="unit"><option value="">All</option></select></th>
                    </tr>
                </thead>
                <tbody id="spBody"></tbody>
            </table>
        </div>

        <div id="spPaging" class="mt-4 flex justify-between text-sm"></div>
    </div>

    {{-- ================= MASTER MESIN ================= --}}
    <div id="master-mesin" class="tab-content hidden">

        <div class="mb-4 flex justify-between gap-4">
            <input id="msSearch" class="border rounded-lg px-3 py-2 w-64" placeholder="Cari mesin / zona / area...">
            <select id="msPerPage" class="border rounded-lg px-3 py-2 w-28">
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="0">Semua</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full table-bordered" id="msTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th>No</th>
                        <th>Kode Mesin</th>
                        <th>Nama Mesin</th>
                        <th>Zona</th>
                        <th>Area</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th><select class="col-filter" data-key="code"><option value="">All</option></select></th>
                        <th><select class="col-filter" data-key="name"><option value="">All</option></select></th>
                        <th><select class="col-filter" data-key="zona"><option value="">All</option></select></th>
                        <th><select class="col-filter" data-key="area"><option value="">All</option></select></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="msBody"></tbody>
            </table>
        </div>

        <div id="msPaging" class="mt-4 flex justify-between text-sm"></div>
    </div>

    {{-- ================= MASTER BANGUNAN ================= --}}
    <div id="master-bangunan" class="tab-content hidden">

        <div class="mb-4 flex justify-between gap-4">
            <input id="bgSearch" class="border rounded-lg px-3 py-2 w-64" placeholder="Cari bangunan / zona...">
            <select id="bgPerPage" class="border rounded-lg px-3 py-2 w-28">
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="0">Semua</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full table-bordered" id="bgTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th>No</th>
                        <th>Kode Bangunan</th>
                        <th>Nama Bangunan</th>
                        <th>Zona</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th><select class="col-filter" data-key="code"><option value="">All</option></select></th>
                        <th><select class="col-filter" data-key="name"><option value="">All</option></select></th>
                        <th><select class="col-filter" data-key="zona"><option value="">All</option></select></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="bgBody"></tbody>
            </table>
        </div>

        <div id="bgPaging" class="mt-4 flex justify-between text-sm"></div>
    </div>

</div>

{{-- Modal Edit Mesin --}}
<div id="editMesinModal" class="fixed inset-0 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl w-96 p-6 relative">
        <h2 class="text-lg font-semibold mb-4">Edit Mesin</h2>
        <form id="editMesinForm">
            @csrf
            <input type="hidden" name="id" id="editMesinId">

            <div class="mb-3">
                <label class="block text-sm font-medium">Kode Mesin</label>
                <input type="text" name="code" id="editMesinCode" class="border rounded w-full px-3 py-2" required>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Nama Mesin</label>
                <input type="text" name="name" id="editMesinName" class="border rounded w-full px-3 py-2" required>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Zona</label>
                <select name="zona" id="editMesinZona" class="border rounded w-full px-3 py-2" required>
                    <option value="">-- Pilih Zona --</option>
                    @foreach($zonas as $z)
                        <option value="{{ $z->znName }}">{{ $z->znName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Area</label>
                <select name="area" id="editMesinArea" class="border rounded w-full px-3 py-2" required>
                    <option value="">-- Pilih Area --</option>
                    @foreach($areas as $a)
                        <option value="{{ $a->areaName }}">{{ $a->areaName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeEditMesinModal()" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Update</button>
            </div>
        </form>
        <button onclick="closeEditMesinModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 font-bold">×</button>
    </div>
</div>

{{-- Modal Edit Bangunan --}}
<div id="editBangunanModal" class="fixed inset-0 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl w-96 p-6 relative">
        <h2 class="text-lg font-semibold mb-4">Edit Bangunan</h2>
        <form id="editBangunanForm">
            @csrf
            <input type="hidden" name="id" id="editBangunanId">

            <div class="mb-3">
                <label class="block text-sm font-medium">Kode Bangunan</label>
                <input type="text" name="code" id="editBangunanCode" class="border rounded w-full px-3 py-2" required>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Nama Bangunan</label>
                <input type="text" name="name" id="editBangunanName" class="border rounded w-full px-3 py-2" required>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium">Zona</label>
                <select name="zona" id="editBangunanZona" class="border rounded w-full px-3 py-2" required>
                    <option value="">-- Pilih Zona --</option>
                    @foreach($zonas as $z)
                        <option value="{{ $z->znName }}">{{ $z->znName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeEditBangunanModal()" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Update</button>
            </div>
        </form>
        <button onclick="closeEditBangunanModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 font-bold">×</button>
    </div>
</div>

@php
// Data untuk JavaScript
$sparepart = $barang->map(fn($i)=>[
    'code'=>$i->code ?? '',
    'name'=>$i->item_name ?? '',
    'unit'=>strtoupper($i->meins ?? ''),
])->values();

$mesinData = $mesin->map(fn($m)=>[
    'id'   => $m->msnID,
    'code' => $m->msnCode ?? '',
    'name' => $m->msnName ?? '',
    'zona' => $m->znName ?? '',
    'area' => $m->areaName ?? '',
])->values();

$bangunanData = $bangunan->map(fn($b)=>[
    'id'   => $b->id,
    'code' => $b->code ?? '',
    'name' => $b->name ?? '',
    'zona' => $b->zona ?? '',
])->values();
@endphp

@push('scripts')
<script>
// Data untuk masing-masing tab
const sparepartData = @json($sparepart, JSON_INVALID_UTF8_IGNORE);
const mesinData = @json($mesinData, JSON_INVALID_UTF8_IGNORE);
const bangunanData = @json($bangunanData, JSON_INVALID_UTF8_IGNORE);
const userRole = '{{ auth()->user()->role }}';  // Role user yang login

console.log('User Role:', userRole);
console.log('Sparepart:', sparepartData.length, 'items');
console.log('Mesin:', mesinData.length, 'items');
console.log('Bangunan:', bangunanData.length, 'items');

/* ========== TABLE ENGINE SEDERHANA ========== */
class TableManager {
    constructor(data, searchId, perPageId, bodyId, pagingId, columns, hasActions = false) {
        this.data = data;
        this.filteredData = [...data];
        this.searchEl = document.getElementById(searchId);
        this.perPageEl = document.getElementById(perPageId);
        this.bodyEl = document.getElementById(bodyId);
        this.pagingEl = document.getElementById(pagingId);
        this.columns = columns;
        this.hasActions = hasActions;
        this.page = 1;
        this.perPage = parseInt(this.perPageEl.value) || 20;
        this.filters = {};
        
        this.init();
    }
    
    init() {
        // Search
        this.searchEl.addEventListener('input', () => {
            this.page = 1;
            this.applyFilters();
        });
        
        // PerPage
        this.perPageEl.addEventListener('change', () => {
            this.perPage = parseInt(this.perPageEl.value) || 0;
            this.page = 1;
            this.render();
        });
        
        // Column filters
        const tableId = this.bodyEl.id.replace('Body', 'Table');
        document.querySelectorAll(`#${tableId} .col-filter`).forEach(select => {
            const key = select.dataset.key;
            
            // Populate options
            const values = [...new Set(this.data.map(item => item[key]).filter(Boolean))].sort();
            values.forEach(val => {
                const option = document.createElement('option');
                option.value = val;
                option.textContent = val;
                select.appendChild(option);
            });
            
            // Event
            select.addEventListener('change', () => {
                if (select.value) {
                    this.filters[key] = select.value;
                } else {
                    delete this.filters[key];
                }
                this.page = 1;
                this.applyFilters();
            });
        });
        
        this.render();
    }
    
    applyFilters() {
        // Start with all data
        let filtered = [...this.data];
        
        // Apply search
        const searchTerm = this.searchEl.value.toLowerCase();
        if (searchTerm) {
            filtered = filtered.filter(item => 
                Object.values(item).some(val => 
                    String(val).toLowerCase().includes(searchTerm)
                )
            );
        }
        
        // Apply column filters
        Object.entries(this.filters).forEach(([key, value]) => {
            filtered = filtered.filter(item => String(item[key]) === String(value));
        });
        
        this.filteredData = filtered;
        this.render();
    }
    
    render() {
        const start = this.perPage ? (this.page - 1) * this.perPage : 0;
        const end = this.perPage ? start + this.perPage : this.filteredData.length;
        const rows = this.filteredData.slice(start, end);
        
        let html = '';
        rows.forEach((item, index) => {
            html += '<tr>';
            html += `<td class="text-center">${start + index + 1}</td>`;
            
            this.columns.forEach(col => {
                html += `<td>${item[col] || '-'}</td>`;
            });
            
            // ========== BAGIAN EDIT BUTTON - HANYA UNTUK ADMIN ==========
            if (this.hasActions) {
                const tableType = this.bodyEl.id === 'msBody' ? 'mesin' : 'bangunan';
                
                if (userRole === 'admin') {
                    // Admin bisa lihat tombol Edit
                    html += `<td class="text-center">
                        <button onclick='openEditModal("${tableType}", ${JSON.stringify(item)})'
                           class="px-3 py-1 text-xs text-blue-700 bg-blue-50 rounded hover:bg-blue-100">
                           Edit
                        </button>
                    </td>`;
                } else {
                    // Approval & User tidak bisa edit (tombol dihilangkan)
                    html += `<td class="text-center text-gray-400 text-xs">-</td>`;
                }
            }
            
            html += '</tr>';
        });
        
        this.bodyEl.innerHTML = html;
        
        // Pagination
        const totalPages = this.perPage ? Math.ceil(this.filteredData.length / this.perPage) : 1;
        if (this.perPage) {
            this.pagingEl.innerHTML = `
                <span class="text-gray-600">Halaman ${this.page} dari ${totalPages} (${this.filteredData.length} data)</span>
                <div class="space-x-2">
                    <button ${this.page === 1 ? 'disabled' : ''} 
                        onclick="tables['${this.bodyEl.id}'].changePage(-1)"
                        class="px-3 py-1 border rounded ${this.page === 1 ? 'bg-gray-100 text-gray-400' : 'bg-white text-gray-700 hover:bg-gray-50'}">
                        Prev
                    </button>
                    <button ${this.page === totalPages ? 'disabled' : ''} 
                        onclick="tables['${this.bodyEl.id}'].changePage(1)"
                        class="px-3 py-1 border rounded ${this.page === totalPages ? 'bg-gray-100 text-gray-400' : 'bg-white text-gray-700 hover:bg-gray-50'}">
                        Next
                    </button>
                </div>
            `;
        } else {
            this.pagingEl.innerHTML = `<span class="text-gray-600">Total ${this.filteredData.length} data</span>`;
        }
    }
    
    changePage(delta) {
        this.page += delta;
        this.render();
    }
}

// Initialize tables
const tables = {
    spBody: new TableManager(sparepartData, 'spSearch', 'spPerPage', 'spBody', 'spPaging', ['code', 'name', 'unit'], false),
    msBody: new TableManager(mesinData, 'msSearch', 'msPerPage', 'msBody', 'msPaging', ['code', 'name', 'zona', 'area'], true),
    bgBody: new TableManager(bangunanData, 'bgSearch', 'bgPerPage', 'bgBody', 'bgPaging', ['code', 'name', 'zona'], true)
};

/* ========== TAB HANDLING ========== */
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Hide all tab content
        document.querySelectorAll('.tab-content').forEach(el => {
            el.classList.add('hidden');
        });
        
        // Show selected tab
        const tabId = this.dataset.tab;
        document.getElementById(tabId).classList.remove('hidden');
        
        // Update active state
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active');
        });
        this.classList.add('active');
    });
});

/* ========== MODAL HANDLING ========== */
function openEditModal(type, data) {
    if (type === 'mesin') {
        document.getElementById('editMesinId').value = data.id;
        document.getElementById('editMesinCode').value = data.code;
        document.getElementById('editMesinName').value = data.name;
        document.getElementById('editMesinZona').value = data.zona;
        document.getElementById('editMesinArea').value = data.area;
        document.getElementById('editMesinModal').classList.remove('hidden');
    } else if (type === 'bangunan') {
        document.getElementById('editBangunanId').value = data.id;
        document.getElementById('editBangunanCode').value = data.code;
        document.getElementById('editBangunanName').value = data.name;
        document.getElementById('editBangunanZona').value = data.zona;
        document.getElementById('editBangunanModal').classList.remove('hidden');
    }
}

function closeEditMesinModal() {
    document.getElementById('editMesinModal').classList.add('hidden');
}

function closeEditBangunanModal() {
    document.getElementById('editBangunanModal').classList.add('hidden');
}

/* ========== AJAX UPDATE MESIN ========== */
document.getElementById('editMesinForm').onsubmit = async function(e) {
    e.preventDefault();
    
    // CEK ROLE - hanya admin yang bisa submit
    if (userRole !== 'admin') {
        alert('? Hanya Administrator yang dapat mengedit data!');
        closeEditMesinModal();
        return;
    }
    
    const id = document.getElementById('editMesinId').value;
    const formData = new FormData(this);
    formData.append('_method', 'PUT');
    
    // Tampilkan loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Menyimpan...';
    submitBtn.disabled = true;
    
    try {
        console.log('?? Mengirim data ke:', `/admin/mesin/${id}`);
        console.log('?? Form data:', Object.fromEntries(formData));
        
        const res = await fetch(`/admin/mesin/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        console.log('?? Response status:', res.status);
        
        // Cek response text dulu
        const responseText = await res.text();
        console.log('?? Response text:', responseText.substring(0, 500));
        
        // Coba parse JSON
        try {
            const data = JSON.parse(responseText);
            console.log('?? Parsed JSON:', data);
            
            if (data.success) {
                alert('? Data Mesin berhasil diupdate!');
                closeEditMesinModal();
                location.reload();
            } else {
                alert('? Gagal update: ' + (data.message || 'Unknown error'));
            }
        } catch (jsonError) {
            console.error('? JSON Parse Error:', jsonError);
            alert('? Server mengembalikan HTML, bukan JSON. Cek console untuk detail (F12).');
        }
        
    } catch (err) {
        console.error('? Fetch Error:', err);
        alert('? Terjadi kesalahan: ' + err.message);
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
};

/* ========== AJAX UPDATE BANGUNAN ========== */
document.getElementById('editBangunanForm').onsubmit = async function(e) {
    e.preventDefault();
    
    // CEK ROLE - hanya admin yang bisa submit
    if (userRole !== 'admin') {
        alert('? Hanya Administrator yang dapat mengedit data!');
        closeEditBangunanModal();
        return;
    }
    
    const id = document.getElementById('editBangunanId').value;
    const formData = new FormData(this);
    formData.append('_method', 'PUT');
    
    // Tampilkan loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Menyimpan...';
    submitBtn.disabled = true;
    
    try {
        console.log('?? Mengirim data ke:', `/admin/bangunan/${id}`);
        console.log('?? Form data:', Object.fromEntries(formData));
        
        const res = await fetch(`/admin/bangunan/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        console.log('?? Response status:', res.status);
        
        // Cek response text dulu
        const responseText = await res.text();
        console.log('?? Response text:', responseText.substring(0, 500));
        
        // Coba parse JSON
        try {
            const data = JSON.parse(responseText);
            console.log('?? Parsed JSON:', data);
            
            if (data.success) {
                alert('? Data Bangunan berhasil diupdate!');
                closeEditBangunanModal();
                location.reload();
            } else {
                alert('? Gagal update: ' + (data.message || 'Unknown error'));
            }
        } catch (jsonError) {
            console.error('? JSON Parse Error:', jsonError);
            alert('? Server mengembalikan HTML, bukan JSON. Cek console untuk detail (F12).');
        }
        
    } catch (err) {
        console.error('? Fetch Error:', err);
        alert('? Terjadi kesalahan: ' + err.message);
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
};

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.id === 'editMesinModal') {
        closeEditMesinModal();
    }
    if (e.target.id === 'editBangunanModal') {
        closeEditBangunanModal();
    }
});
</script>
@endpush

@endsection