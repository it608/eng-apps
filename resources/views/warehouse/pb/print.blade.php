@php
    $formatDate = function ($value, $withTime = false) {
        if (!$value) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
        } catch (\Throwable $e) {
            return '-';
        }
    };

    $formatNumber = fn ($value) => number_format((float) ($value ?? 0), 2, ',', '.');
    $formatCurrency = fn ($value) => 'Rp ' . number_format((float) ($value ?? 0), 0, ',', '.');
    $displayUser = function ($name, $username) {
        $name = trim((string) $name);
        $username = trim((string) $username);

        if ($name !== '' && $username !== '') {
            return "{$name} ({$username})";
        }

        return $name !== '' ? $name : ($username !== '' ? $username : '-');
    };

    $approvalL1User = $displayUser($pb->approver1_name ?? null, $pb->approver1_username ?? null);
    $approvalL2User = $displayUser($pb->approver2_name ?? null, $pb->approver2_username ?? null);
    $finalUser = $displayUser($pb->final_approver_name ?? null, $pb->final_approver_username ?? null);
    $requesterUser = $displayUser($pb->requester_name ?? null, $pb->requester_username ?? null);
    $verifierUser = $displayUser($pb->verifier_name ?? null, $pb->verifier_username ?? null);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print PB {{ $pb->nomor_pb }}</title>
    <style>
        @page {
            size: A4;
            margin: 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 18mm;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
        }

        .toolbar {
            width: 210mm;
            margin: 18px auto 12px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: #0f172a;
            cursor: pointer;
            font-weight: 700;
            padding: 9px 14px;
        }

        .btn-primary {
            border-color: #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 3px solid #111827;
            padding-bottom: 14px;
        }

        .brand {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.3px;
        }

        .subtitle {
            margin-top: 3px;
            color: #475569;
            font-size: 12px;
        }

        .doc-title {
            text-align: right;
        }

        .doc-title h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.5px;
        }

        .doc-number {
            margin-top: 5px;
            color: #2563eb;
            font-size: 13px;
            font-weight: 800;
        }

        .section {
            margin-top: 16px;
        }

        .section-title {
            margin-bottom: 8px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, 1fr);
        }

        .box {
            min-height: 54px;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            padding: 10px;
        }

        .box-wide {
            grid-column: span 2;
        }

        .label {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .value {
            margin-top: 4px;
            color: #111827;
            font-size: 12px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f1f5f9;
            border: 1px solid #dbe3ef;
            color: #334155;
            font-size: 10px;
            padding: 8px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border: 1px solid #dbe3ef;
            padding: 8px;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #64748b;
            font-size: 11px;
        }

        .sign-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, 1fr);
        }

        .sign-box {
            min-height: 92px;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }

        .sign-name {
            margin-top: 34px;
            font-weight: 800;
        }

        .footer {
            margin-top: 16px;
            border-top: 1px solid #dbe3ef;
            padding-top: 8px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .toolbar {
                display: none;
            }

            .page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="btn" onclick="window.close()">Tutup</button>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print PB</button>
    </div>

    <main class="page">
        <header class="header">
            <div>
                <div class="brand">PT. SEKARBUMI TBK</div>
                <div class="subtitle">e-Request Engineering Apps</div>
                <div class="subtitle">Dokumen PB sudah selesai approval dan masuk Warehouse Fulfillment.</div>
            </div>
            <div class="doc-title">
                <h1>BON PERMINTAAN BARANG</h1>
                <div class="doc-number">{{ $pb->nomor_pb }}</div>
            </div>
        </header>

        <section class="section">
            <div class="section-title">Informasi PB</div>
            <div class="grid">
                <div class="box">
                    <div class="label">Tanggal PB</div>
                    <div class="value">{{ $formatDate($pb->tanggal_permintaan) }}</div>
                </div>
                <div class="box">
                    <div class="label">Tanggal Diperlukan</div>
                    <div class="value">{{ $formatDate($pb->tanggal_diperlukan) }}</div>
                </div>
                <div class="box">
                    <div class="label">Bagian</div>
                    <div class="value">{{ $pb->bagian ?? '-' }}</div>
                </div>
                <div class="box">
                    <div class="label">Requestor</div>
                    <div class="value">{{ $requesterUser }}</div>
                </div>
                <div class="box">
                    <div class="label">Untuk</div>
                    <div class="value">{{ ucfirst((string) ($pb->untuk ?? '-')) }}</div>
                </div>
                <div class="box">
                    <div class="label">Gudang</div>
                    <div class="value">{{ $pb->dari_gudang ?? '-' }}</div>
                </div>
                <div class="box">
                    <div class="label">Jenis Pekerjaan</div>
                    <div class="value">{{ ucfirst((string) ($pb->jenis_pekerjaan ?? '-')) }}</div>
                </div>
                <div class="box">
                    <div class="label">Status PB</div>
                    <div class="value">{{ ucfirst(str_replace('_', ' ', (string) ($pb->status ?? '-'))) }}</div>
                </div>
                <div class="box box-wide">
                    <div class="label">Keterangan</div>
                    <div class="value">{{ $pb->keterangan ?: '-' }}</div>
                </div>
                <div class="box box-wide">
                    <div class="label">Referensi GI ERP</div>
                    <div class="value">{{ $pb->erp_gi_number ?: '-' }}</div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-title">Approval</div>
            <div class="sign-grid">
                <div class="sign-box">
                    <div class="label">Verifikasi SH</div>
                    <div class="sign-name">{{ $verifierUser }}</div>
                    <div class="muted">{{ $formatDate($pb->verified_at ?? null, true) }}</div>
                </div>
                <div class="sign-box">
                    <div class="label">Approval L1</div>
                    <div class="sign-name">{{ $approvalL1User }}</div>
                    <div class="muted">{{ $formatDate($pb->approval_level_1_at ?? null, true) }}</div>
                </div>
                <div class="sign-box">
                    <div class="label">Approval L2</div>
                    <div class="sign-name">{{ ($pb->approval_level_required ?? 1) >= 2 ? $approvalL2User : 'Tidak diperlukan' }}</div>
                    <div class="muted">{{ ($pb->approval_level_required ?? 1) >= 2 ? $formatDate($pb->approval_level_2_at ?? null, true) : '-' }}</div>
                </div>
                <div class="sign-box">
                    <div class="label">Final Approved</div>
                    <div class="sign-name">{{ $finalUser }}</div>
                    <div class="muted">{{ $formatDate($pb->approved_at ?? null, true) }}</div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-title">Daftar Barang</div>
            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width: 36px;">No</th>
                        <th>Nama Barang</th>
                        <th style="width: 110px;">Kode</th>
                        <th class="text-right" style="width: 90px;">Qty</th>
                        <th style="width: 70px;">Satuan</th>
                        <th class="text-right" style="width: 120px;">Harga</th>
                        <th class="text-right" style="width: 130px;">Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($detail as $item)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $item->nama_barang }}</strong>
                                @if($item->keterangan)
                                    <div class="muted">{{ $item->keterangan }}</div>
                                @endif
                            </td>
                            <td>{{ $item->kode_barang ?: '-' }}</td>
                            <td class="text-right">{{ $formatNumber($item->jumlah) }}</td>
                            <td>{{ $item->satuan ?: '-' }}</td>
                            <td class="text-right">{{ $formatCurrency($item->unit_price) }}</td>
                            <td class="text-right">{{ $formatCurrency($item->total_price) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) ($item->fulfillment_status ?? 'pending'))) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center muted">Tidak ada barang.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total</strong></td>
                        <td class="text-right"><strong>{{ $formatNumber($totals->qty) }}</strong></td>
                        <td><strong>{{ $totals->item_count }} item</strong></td>
                        <td></td>
                        <td class="text-right"><strong>{{ $formatCurrency($totals->value) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </section>

        <footer class="footer">
            <span>Dicetak dari e-Request Engineering Apps</span>
            <span>Waktu cetak: {{ now()->format('d/m/Y H:i') }}</span>
        </footer>
    </main>
</body>
</html>
