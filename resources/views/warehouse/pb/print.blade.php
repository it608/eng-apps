@php
    $formatDate = function ($value, $withTime = false) {
        if (!$value) return '-';

        try {
            return \Carbon\Carbon::parse($value)
                ->locale('id')
                ->translatedFormat($withTime ? 'd M Y, H.i' : 'd M Y');
        } catch (\Throwable $e) {
            return '-';
        }
    };

    $formatNumber = fn ($value) => number_format((float) ($value ?? 0), 2, '.', '');
    $status = strtolower((string) ($pb->status ?? 'approved'));
    $statusLabel = match ($status) {
        'completed' => 'Selesai',
        'in_progress' => 'Diproses',
        'approved' => 'Disetujui',
        default => ucfirst($status),
    };
    $isApproved = in_array($status, ['approved', 'in_progress', 'completed'], true);
    $approvedAt = $pb->approved_at ?? $pb->approval_level_2_at ?? $pb->approval_level_1_at ?? null;
    $printedAt = now();
    $targetType = strtolower((string) ($pb->untuk ?? ''));
    $targetLabel = $targetType === 'bangunan' ? 'Bangunan' : ($targetType === 'mesin' ? 'Mesin' : ucfirst($targetType ?: '-'));
    $targetInitial = $targetType === 'bangunan' ? 'B' : ($targetType === 'mesin' ? 'M' : strtoupper(substr($targetLabel, 0, 1)));
    $targetName = $pb->tujuan_nama ?? $pb->untuk ?? '-';
    $targetCode = $pb->tujuan_kode ?? '';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bon Permintaan Barang - {{ $pb->nomor_pb }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            color: #0f172a;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .print-container {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .1);
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            z-index: 10;
            transform: translate(-50%, -50%) rotate(-45deg);
            border: 5px solid rgba(34, 197, 94, .2);
            border-radius: 20px;
            color: rgba(34, 197, 94, .1);
            font-size: 60px;
            font-weight: 800;
            padding: 20px 60px;
            pointer-events: none;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2563eb;
            text-align: center;
        }

        .header h1 {
            margin: 0 0 5px;
            color: #1e3a8a;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .header h3 {
            margin: 0;
            color: #4b5563;
            font-size: 16px;
            font-weight: 400;
        }

        .status-container {
            margin: 15px 0 25px;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            min-width: 128px;
            padding: 8px 24px;
            border-radius: 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .1);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 1px;
            text-align: center;
            text-transform: uppercase;
        }

        .status-approved,
        .status-completed,
        .status-in_progress {
            border: 1px solid #16a34a;
            background: #22c55e;
            color: #fff;
        }

        .status-backdate {
            margin-left: 8px;
            border: 1px solid #fcd34d;
            background: #fef3c7;
            color: #92400e;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            padding: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            margin-bottom: 4px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .info-value {
            color: #0f172a;
            font-size: 15px;
            font-weight: 600;
        }

        .info-value.highlight {
            display: inline-block;
            width: fit-content;
            border-radius: 6px;
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 8px;
            font-weight: 700;
        }

        .asset-card {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
            border-left: 4px solid #2563eb;
            border-radius: 8px;
            background: #eff6ff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, .05);
            padding: 15px 20px;
        }

        .asset-icon {
            display: flex;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
            font-size: 16px;
            font-weight: 800;
        }

        .asset-label {
            margin-bottom: 4px;
            color: #2563eb;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .asset-name {
            color: #0f172a;
            font-size: 16px;
            font-weight: 700;
        }

        .asset-code {
            margin-top: 2px;
            color: #475569;
            font-size: 12px;
        }

        .note-box {
            display: flex;
            gap: 12px;
            margin: 20px 0;
            border: 1px solid #facc15;
            border-radius: 8px;
            background: #fef9c3;
            padding: 15px 20px;
            color: #422006;
            font-style: italic;
        }

        .note-box strong {
            font-style: normal;
        }

        .backdate-box {
            border-color: #fcd34d;
            background: #fffbeb;
        }

        .table-container {
            margin: 25px 0;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #1e293b;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px;
            padding: 12px 10px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            padding: 10px;
            vertical-align: top;
        }

        tbody tr:last-child td { border-bottom: 0; }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .total-section {
            display: flex;
            justify-content: flex-end;
            gap: 30px;
            margin: 20px 0;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #f1f5f9;
            padding: 15px 20px;
        }

        .total-item { text-align: right; }

        .total-label {
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
        }

        .total-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
        }

        .approval-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            border: 1px solid #86efac;
            border-radius: 8px;
            background: #f0fdf4;
            color: #166534;
            padding: 15px 20px;
        }

        .print-date {
            margin-top: 25px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 15px;
            color: #94a3b8;
            font-size: 11px;
            text-align: right;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .print-container {
                box-shadow: none;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        @if($isApproved)
            <div class="watermark">APPROVED</div>
        @endif

        <div class="header">
            <h1>BON PERMINTAAN BARANG</h1>
            <h3>Nomor: {{ $pb->nomor_pb }}</h3>
        </div>

        <div class="status-container">
            <span class="status-badge status-{{ $status }}">{{ $statusLabel }}</span>
            @if((bool) ($pb->is_backdate ?? false))
                <span class="status-badge status-backdate">Backdate</span>
            @endif
        </div>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Tanggal Permintaan</span>
                <span class="info-value">{{ $formatDate($pb->tanggal_permintaan) }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Bagian</span>
                <span class="info-value">{{ $pb->bagian ?? 'Engineering' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Untuk</span>
                <span class="info-value highlight">{{ strtoupper($pb->untuk ?? '-') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Dari Gudang</span>
                <span class="info-value">{{ $pb->dari_gudang ?? '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Jenis Pekerjaan</span>
                <span class="info-value">{{ strtoupper($pb->jenis_pekerjaan ?? '-') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Diperlukan</span>
                <span class="info-value">{{ $formatDate($pb->tanggal_diperlukan) }}</span>
            </div>
        </div>

        @if((bool) ($pb->is_backdate ?? false))
            <div class="note-box backdate-box">
                <div>
                    <strong>PB BACKDATE:</strong> {{ $pb->backdate_reason ?: '-' }}<br>
                    <span style="font-size:11px;color:#92400e;">Diinput pada: {{ $formatDate($pb->created_at, true) }}</span>
                </div>
            </div>
        @endif

        <div class="asset-card">
            <div class="asset-icon">{{ $targetInitial }}</div>
            <div>
                <div class="asset-label">{{ $targetLabel }}</div>
                <div class="asset-name">{{ $targetName }}</div>
                @if($targetCode !== '')
                    <div class="asset-code">Kode: {{ $targetCode }}</div>
                @endif
            </div>
        </div>

        @if(!empty($pb->keterangan))
            <div class="note-box">
                <div><strong>Keterangan:</strong> {{ $pb->keterangan }}</div>
            </div>
        @endif

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
                    @forelse($detail as $item)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $item->nama_barang }}</td>
                            <td class="text-right">{{ $formatNumber($item->jumlah) }}</td>
                            <td class="text-center">{{ $item->satuan ?: '-' }}</td>
                            <td>{{ $item->keterangan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <div class="total-item">
                <div class="total-label">Total Item</div>
                <div class="total-value">{{ $totals->item_count }} item</div>
            </div>
            <div class="total-item">
                <div class="total-label">Total Jumlah</div>
                <div class="total-value">{{ $formatNumber($totals->qty) }}</div>
            </div>
        </div>

        @if($approvedAt)
            <div class="approval-info">
                <div>
                    <strong>DISETUJUI PADA:</strong> {{ $formatDate($approvedAt, true) }}
                </div>
            </div>
        @endif

        <div class="print-date">
            Dicetak pada: {{ $printedAt->locale('id')->translatedFormat('d M Y') }}
        </div>
    </div>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>
