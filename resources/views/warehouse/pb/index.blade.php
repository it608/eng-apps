@extends('layouts.admin')

@section('title', 'PB Fulfillment - Warehouse')

@section('content')
<div x-data="warehousePbApp()" x-init="init()" class="space-y-6">
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">PB Fulfillment</h1>
        <p class="text-sm text-gray-500">Pemenuhan permintaan barang yang sudah disetujui approval</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <div class="text-sm font-medium text-gray-900">PB Masuk</div>
            <div class="mt-1 text-base font-medium text-blue-600" x-text="summary.total"></div>
            <div class="mt-1 text-xs text-gray-500">Approved dan diproses</div>
        </div>
        <div>
            <div class="text-sm font-medium text-gray-900">Pending Item</div>
            <div class="mt-1 text-base font-medium text-amber-600" x-text="summary.pending"></div>
            <div class="mt-1 text-xs text-gray-500">Menunggu pemenuhan</div>
        </div>
        <div>
            <div class="text-sm font-medium text-gray-900">Hold</div>
            <div class="mt-1 text-base font-medium text-purple-600" x-text="summary.hold"></div>
            <div class="mt-1 text-xs text-gray-500">Ditunda sementara</div>
        </div>
        <div>
            <div class="text-sm font-medium text-gray-900">Rejected</div>
            <div class="mt-1 text-base font-medium text-red-600" x-text="summary.rejected"></div>
            <div class="mt-1 text-xs text-gray-500">Tidak bisa dipenuhi</div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Daftar PB Approved</h2>
                    <p class="mt-1 text-sm text-gray-500">Klik detail untuk memproses item satu per satu</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative min-w-[280px]">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="fas fa-search text-sm"></i>
                        </span>
                        <input x-model.debounce.400ms="search"
                               @input="loadData()"
                               type="text"
                               placeholder="Cari no PB, item, gudang..."
                               class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <button type="button"
                            @click="loadData()"
                            class="inline-flex items-center gap-2 rounded-lg border border-blue-100 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-600 hover:bg-blue-100">
                        <i class="fas fa-rotate-right text-xs"></i>
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                        <th class="px-6 py-3">No. PB</th>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Tujuan</th>
                        <th class="px-6 py-3">Status Pemenuhan</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <template x-for="pb in rows" :key="pb.id">
                        <tr class="hover:bg-blue-50/30">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900" x-text="pb.nomor_pb"></div>
                                <div class="text-xs text-gray-500 capitalize" x-text="pb.jenis_pekerjaan || '-'"></div>
                                <div class="mt-1 text-xs text-emerald-700" x-show="pb.erp_gi_number">
                                    GI ERP: <span class="font-medium" x-text="pb.erp_gi_number"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div x-text="formatDate(pb.tanggal_permintaan)"></div>
                                <div class="text-xs text-gray-500">Diperlukan: <span x-text="formatDate(pb.tanggal_diperlukan)"></span></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="capitalize" x-text="pb.untuk || '-'"></div>
                                <div class="text-xs text-gray-500" x-text="formatGudang(pb.dari_gudang)"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-2">
                                    <div class="inline-flex min-w-[120px] items-center justify-center rounded-full px-3 py-1 text-xs font-semibold"
                                         :class="pbFulfillmentClass(pb)"
                                         x-text="pbFulfillmentLabel(pb)">
                                    </div>
                                    <div class="h-1.5 w-full max-w-[180px] rounded-full bg-gray-100">
                                        <div class="h-1.5 rounded-full bg-green-500"
                                             :style="`width: ${pbFulfillmentPercent(pb)}%`"></div>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <span x-text="processedItems(pb)"></span>/<span x-text="pb.total_items || 0"></span> item diproses
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button type="button"
                                        @click="openDetail(pb)"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50"
                                        title="Proses item">
                                    <i class="fas fa-clipboard-check"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && rows.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">Belum ada PB approved untuk warehouse</td>
                    </tr>
                    <tr x-show="loading">
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="showModal" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto">
        <div class="flex min-h-full items-start justify-center px-4 py-6 pt-24 lg:pl-72 lg:pr-8">
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeModal()"></div>
            <div class="relative w-full max-w-6xl overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Pemenuhan PB</h3>
                        <p class="mt-1 text-sm text-gray-500" x-text="selected?.header?.nomor_pb || '-'"></p>
                    </div>
                    <button type="button" @click="closeModal()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6" x-show="detailLoading">
                    <div class="flex min-h-[260px] flex-col items-center justify-center gap-3 text-gray-500">
                        <i class="fas fa-spinner fa-spin text-xl text-blue-600"></i>
                        <div class="text-sm font-medium">Memuat detail PB...</div>
                    </div>
                </div>

                <div class="p-6" x-show="!detailLoading">
                    <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Tanggal PB</div>
                            <div class="mt-1 font-semibold text-gray-900" x-text="formatDate(selected?.header?.tanggal_permintaan)"></div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Diperlukan</div>
                            <div class="mt-1 font-semibold text-gray-900" x-text="formatDate(selected?.header?.tanggal_diperlukan)"></div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Tujuan</div>
                            <div class="mt-1 font-semibold capitalize text-gray-900" x-text="selected?.header?.untuk || '-'"></div>
                        </div>
                    </div>

                    <div class="mb-5 rounded-lg border border-emerald-100 bg-emerald-50/50 p-4">
                        <label for="erpGiNumber" class="block text-xs font-semibold uppercase tracking-wider text-emerald-700">
                            No. Good Issue ERP
                        </label>
                        <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                            <input id="erpGiNumber"
                                   x-model="erpGiNumber"
                                   type="text"
                                   :readonly="hasErpReference()"
                                   placeholder="Contoh: GI-2026-000123"
                                   :class="hasErpReference() ? 'border-gray-200 bg-gray-100 text-gray-700 cursor-not-allowed' : 'border-emerald-200 bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100'"
                                   class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none">
                            <button type="button"
                                    @click="saveErpReference()"
                                    :disabled="hasErpReference()"
                                    :class="hasErpReference() ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-emerald-600 text-white hover:bg-emerald-700'"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold">
                                <i :class="hasErpReference() ? 'fas fa-lock text-xs' : 'fas fa-save text-xs'"></i>
                                <span x-text="hasErpReference() ? 'Ref Tersimpan' : 'Simpan Ref'"></span>
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-emerald-700">
                            Diisi dari nomor dokumen Good Issue ERP setelah barang dikeluarkan dari gudang.
                        </p>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                    <th class="px-4 py-3">Barang</th>
                                    <th class="px-4 py-3 text-right">Qty</th>
                                    <th class="px-4 py-3 text-right">Harga Rata-rata</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Catatan</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <template x-for="item in selected?.detail || []" :key="item.id">
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900" x-text="item.nama_barang"></div>
                                            <div class="text-xs text-gray-500" x-text="item.keterangan || '-'"></div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="font-mono font-semibold" x-text="formatNumber(item.jumlah)"></div>
                                            <div class="text-xs text-gray-500" x-text="item.satuan"></div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="font-mono font-semibold"
                                                 :class="hasAveragePrice(item) ? 'text-gray-900' : 'text-gray-400'"
                                                 x-text="formatAveragePrice(item.unit_price)"></div>
                                            <div class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                                 x-show="isHighValueItem(item)"
                                                 :class="'bg-amber-50 text-amber-700'">
                                                >= 10 Juta
                                            </div>
                                            <div class="mt-1 text-[11px] text-gray-400" x-show="!hasAveragePrice(item)">
                                                Belum tersedia
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex min-w-[88px] justify-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                                  :class="fulfillmentClass(item.fulfillment_status)"
                                                  x-text="fulfillmentLabel(item.fulfillment_status)"></span>
                                            <div class="mt-1 text-xs text-gray-500" x-show="item.fulfilled_by_name">
                                                Oleh <span x-text="item.fulfilled_by_name"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600" x-text="item.fulfillment_note || '-'"></td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" @click="checkItem(item)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-green-600 hover:bg-green-50" title="Ceklis">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" @click="askNote(item, 'hold')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-purple-600 hover:bg-purple-50" title="Hold">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                                <button type="button" @click="askNote(item, 'rejected')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 hover:bg-red-50" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function warehousePbApp() {
    return {
        rows: [],
        selected: null,
        showModal: false,
        loading: false,
        detailLoading: false,
        search: '',
        erpGiNumber: '',
        summary: { total: 0, pending: 0, hold: 0, rejected: 0 },

        init() {
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.search) params.set('search', this.search);
                const response = await fetch(`/warehouse/pb/data?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();
                this.rows = result.success ? result.data : [];
                this.updateSummary();
            } catch (error) {
                console.error(error);
                this.rows = [];
            } finally {
                this.loading = false;
            }
        },

        updateSummary() {
            this.summary = this.rows.reduce((acc, row) => {
                acc.total += 1;
                acc.pending += parseInt(row.pending_items || 0, 10);
                acc.hold += parseInt(row.hold_items || 0, 10);
                acc.rejected += parseInt(row.rejected_items || 0, 10);
                return acc;
            }, { total: 0, pending: 0, hold: 0, rejected: 0 });
        },

        processedItems(pb) {
            return parseInt(pb.checked_items || 0, 10)
                + parseInt(pb.hold_items || 0, 10)
                + parseInt(pb.rejected_items || 0, 10);
        },

        pbFulfillmentPercent(pb) {
            const total = parseInt(pb.total_items || 0, 10);
            if (total <= 0) return 0;
            return Math.round((this.processedItems(pb) / total) * 100);
        },

        pbFulfillmentLabel(pb) {
            const total = parseInt(pb.total_items || 0, 10);
            const pending = parseInt(pb.pending_items || 0, 10);
            const hold = parseInt(pb.hold_items || 0, 10);
            const rejected = parseInt(pb.rejected_items || 0, 10);

            if (total > 0 && pending === total) return 'Siap Diproses';
            if (rejected > 0) return 'Ada Reject';
            if (hold > 0) return 'Ada Hold';
            if (pending > 0) return 'Proses Parsial';
            return 'Selesai';
        },

        pbFulfillmentClass(pb) {
            const label = this.pbFulfillmentLabel(pb);
            const classes = {
                'Siap Diproses': 'bg-blue-50 text-blue-700',
                'Proses Parsial': 'bg-amber-50 text-amber-700',
                'Ada Hold': 'bg-purple-50 text-purple-700',
                'Ada Reject': 'bg-red-50 text-red-700',
                'Selesai': 'bg-green-50 text-green-700'
            };
            return classes[label] || classes['Siap Diproses'];
        },

        async openDetail(pb) {
            const id = typeof pb === 'object' ? pb.id : pb;
            this.selected = typeof pb === 'object'
                ? { header: pb, detail: [] }
                : { header: { id, nomor_pb: '-' }, detail: [] };
            this.erpGiNumber = this.selected.header.erp_gi_number || '';
            this.detailLoading = true;
            this.showModal = true;

            try {
                const response = await fetch(`/warehouse/pb/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (!result.success) {
                    this.showModal = false;
                    this.detailLoading = false;
                    alert(result.message || 'Gagal mengambil detail PB');
                    return;
                }
                this.selected = result.data;
                this.erpGiNumber = result.data.header.erp_gi_number || '';
                this.detailLoading = false;
            } catch (error) {
                console.error(error);
                this.showModal = false;
                this.detailLoading = false;
                alert('Gagal mengambil detail PB');
            }
        },

        closeModal() {
            this.showModal = false;
            this.selected = null;
            this.erpGiNumber = '';
            this.detailLoading = false;
            this.loadData();
        },

        hasErpReference() {
            return Boolean(String(this.selected?.header?.erp_gi_number || '').trim());
        },

        async saveErpReference() {
            if (!this.selected?.header?.id) return;
            if (this.hasErpReference()) return;

            const erpGiNumber = String(this.erpGiNumber || '').trim();
            if (!erpGiNumber) {
                alert('No. Good Issue ERP wajib diisi.');
                return;
            }

            const pbId = this.selected.header.id;

            const response = await fetch(`/warehouse/pb/${pbId}/erp-reference`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ erp_gi_number: erpGiNumber })
            });
            const result = await response.json();
            if (!result.success) {
                alert(result.message || 'Gagal menyimpan nomor Good Issue ERP');
                return;
            }

            await this.openDetail(pbId);
            await this.loadData();
            alert('No. Good Issue ERP berhasil disimpan.');
        },

        askNote(item, status) {
            const label = status === 'hold' ? 'hold' : 'reject';
            const note = prompt(`Catatan ${label} untuk item ini:`, item.fulfillment_note || '');
            if (note === null) return;
            this.updateItem(item, status, note);
        },

        async checkItem(item) {
            if (!this.erpGiNumber.trim()) {
                const giNumber = prompt('Masukkan No. Good Issue ERP terlebih dahulu:', '');
                if (giNumber === null || !giNumber.trim()) return;
                this.erpGiNumber = giNumber.trim();
                await this.saveErpReference();
            }

            this.updateItem(item, 'checked');
        },

        async updateItem(item, status, note = '') {
            const response = await fetch(`/warehouse/pb/${this.selected.header.id}/items/${item.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ status, note })
            });
            const result = await response.json();
            if (!result.success) {
                alert(result.message || 'Gagal update item');
                return;
            }
            await this.openDetail(this.selected.header.id);
            await this.loadData();
        },

        fulfillmentLabel(status) {
            const labels = {
                pending: 'Pending',
                checked: 'Checked',
                hold: 'Hold',
                rejected: 'Rejected'
            };
            return labels[status] || 'Pending';
        },

        fulfillmentClass(status) {
            const classes = {
                pending: 'bg-amber-50 text-amber-700',
                checked: 'bg-green-50 text-green-700',
                hold: 'bg-purple-50 text-purple-700',
                rejected: 'bg-red-50 text-red-700'
            };
            return classes[status] || classes.pending;
        },

        formatDate(value) {
            if (!value) return '-';
            return new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        formatNumber(value) {
            return Number(value || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        hasAveragePrice(item) {
            return Number(item?.unit_price || 0) > 0;
        },

        formatAveragePrice(value) {
            const amount = Number(value || 0);
            if (amount <= 0) return '-';
            return 'Rp ' + amount.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        },

        isHighValueItem(item) {
            return Number(item?.unit_price || 0) >= 10000000;
        },

        formatGudang(value) {
            if (!value || value === 'gudang_11') return 'Gudang 11';
            return String(value).replace(/_/g, ' ').toUpperCase();
        }
    };
}
</script>
@endsection
