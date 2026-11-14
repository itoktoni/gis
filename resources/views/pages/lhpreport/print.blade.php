<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Report LHP {{ request('dari') }} - {{ request('sampai') }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; margin: 16px; }
        h2 { margin: 0 0 2px; }
        .sub { color: #555; margin-bottom: 12px; }
        table.data { border-collapse: collapse; width: 100%; }
        table.data th, table.data td { border: 1px solid #999; padding: 4px 6px; }
        table.data th { background: #eee; }
        table.data td.num { text-align: right; }
        .no-print { position: fixed; bottom: 16px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; background: #fff; border: 1px solid #ccc; border-radius: 999px; padding: 8px 12px; box-shadow: 0 4px 16px rgba(0,0,0,.15); }
        .no-print button, .no-print a { border: 0; border-radius: 999px; padding: 8px 16px; font-size: 13px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-print { background: #00236f; color: #fff; }
        .btn-copy { background: #15803d; color: #fff; }
        .btn-excel { background: #e6eeff; color: #00236f; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <h2>LAPORAN HARIAN PRODUKSI (LHP)</h2>
    <div class="sub">Periode: {{ request('dari') ?: '-' }} s/d {{ request('sampai') ?: '-' }} • {{ $data->count() }} baris • Dicetak {{ now()->format('d M Y H:i') }} oleh {{ auth()->user()->name ?? '-' }}</div>

    <table class="data" id="reportTable">
        <thead>
            <tr>
                <th>No</th><th>Tanggal</th><th>Hari</th><th>Kebun</th><th>PT</th>
                <th>Luas TM (Ha)</th><th>Luas Panen (Ha)</th><th>TK (HK)</th>
                <th>Janjang Panen</th><th>Produksi Panen (Kg)</th><th>BJR Panen</th>
                <th>Janjang Kirim</th><th>Produksi Kirim (Kg)</th><th>BJR Kirim</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->lhp_tanggal?->format('Y-m-d') }}</td>
                <td>{{ $r->lhp_hari }}</td>
                <td>{{ $r->hasKebun?->kebun_nama }}</td>
                <td>{{ $r->hasKebun?->hasPt?->pt_nama }}</td>
                <td class="num">{{ $r->lhp_luas_tm }}</td>
                <td class="num">{{ $r->lhp_luas_panen }}</td>
                <td class="num">{{ $r->lhp_tk_hk }}</td>
                <td class="num">{{ $r->lhp_janjang_panen }}</td>
                <td class="num">{{ $r->lhp_produksi_panen_kg }}</td>
                <td class="num">{{ $r->bjr_panen }}</td>
                <td class="num">{{ $r->lhp_janjang_kirim }}</td>
                <td class="num">{{ $r->lhp_produksi_kirim_kg }}</td>
                <td class="num">{{ $r->bjr_kirim }}</td>
            </tr>
            @empty
            <tr><td colspan="14" style="text-align:center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="no-print">
        <button type="button" class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
        <button type="button" class="btn-copy" onclick="copyTsv()">Salin TSV (ke Google Sheet)</button>
        <a class="btn-excel" href="{{ route('lhp-report.getExportExcel', request()->query()) }}">Export Excel</a>
    </div>

    <script>
    function copyTsv() {
        const rows = [];
        document.querySelectorAll('#reportTable tr').forEach(tr => {
            const cells = [...tr.querySelectorAll('th, td')].map(td => td.innerText.trim().replace(/\t/g, ' '));
            rows.push(cells.join('\t'));
        });
        const text = rows.join('\n');
        const done = () => alert('Berhasil disalin: ' + (rows.length - 1) + ' baris. Paste ke Google Sheet.');
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done).catch(() => fallbackCopy(text, done));
        } else {
            fallbackCopy(text, done);
        }
    }

    function fallbackCopy(text, done) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        ta.setSelectionRange(0, ta.value.length);
        try {
            document.execCommand('copy');
            done();
        } catch (e) {
            alert('Gagal menyalin otomatis. Blokir tabel manual lalu Ctrl+C.');
        }
        document.body.removeChild(ta);
    }
    </script>
</body>
</html>
