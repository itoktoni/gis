<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Report LHP per Blok {{ request('dari') }} - {{ request('sampai') }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 16px; }
        h2 { margin: 0 0 2px; }
        .sub { color: #555; margin-bottom: 12px; }
        table.data { border-collapse: collapse; width: 100%; }
        table.data th, table.data td { border: 1px solid #999; padding: 3px 5px; white-space: nowrap; }
        table.data th { background: #eee; }
        table.data td.num { text-align: right; }
        .no-print { position: fixed; bottom: 16px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; background: #fff; border: 1px solid #ccc; border-radius: 999px; padding: 8px 12px; box-shadow: 0 4px 16px rgba(0,0,0,.15); }
        .no-print button, .no-print a { border: 0; border-radius: 999px; padding: 8px 16px; font-size: 13px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-print { background: #00236f; color: #fff; }
        .btn-copy { background: #15803d; color: #fff; }
        .btn-excel { background: #e6eeff; color: #00236f; }
        @media print { .no-print { display: none; } @page { size: landscape; } }
    </style>
</head>
<body>
    <h2>LAPORAN HARIAN PRODUKSI (LHP) PER BLOK</h2>
    <div class="sub">Periode: {{ request('dari') ?: '-' }} s/d {{ request('sampai') ?: '-' }} • {{ $data->count() }} baris • Dicetak {{ now()->format('d M Y H:i') }} oleh {{ auth()->user()->name ?? '-' }}</div>

    <table class="data" id="reportTable">
        <thead>
            <tr>
                <th>No</th><th>Tanggal</th><th>Nama Kebun</th><th>Kode Blok</th><th>Nama Blok</th>
                <th>Luas Panen<br>(Ha)</th>
                <th>Jjg<br>Panen</th><th>Jjg<br>Kirim</th><th>Restan<br>Jjg</th>
                <th>Brd Panen<br>(Kg)</th><th>Brd Kirim<br>(Kg)</th><th>Restan Brd<br>(Kg)</th>
                <th>Restan</th><th>Pusingan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->hasLhp?->lhp_tanggal?->format('Y-m-d') }}</td>
                <td>{{ $r->hasLhp?->hasKebun?->kebun_nama }}</td>
                <td>{{ $r->hasBlok?->blok_kode }}</td>
                <td>{{ $r->hasBlok?->blok_nama }}</td>
                <td class="num">{{ $r->lhp_blok_luas_panen }}</td>
                <td class="num">{{ $r->lhp_blok_janjang_panen }}</td>
                <td class="num">{{ $r->lhp_blok_janjang_kirim }}</td>
                <td class="num">{{ $r->lhp_blok_restan_janjang ?? 0 }}</td>
                <td class="num">{{ $r->lhp_blok_brondolan_panen }}</td>
                <td class="num">{{ $r->lhp_blok_brondolan_kirim }}</td>
                <td class="num">{{ $r->lhp_blok_restan_brondolan ?? 0 }}</td>
                <td>{{ $r->lhp_blok_restan ? 'Ya' : '-' }}</td>
                <td class="num">@if($r->pusingan_hari === null)<span style="color:#888">perdana</span>@else Pusingan {{ $r->pusingan_hari }} hari@if($r->pusingan_prev)<span style="display:block;color:#666;font-size:10px">terakhir {{ \Carbon\Carbon::parse($r->pusingan_prev)->format('d/m/Y') }}</span>@endif@endif</td>
            </tr>
            @empty
            <tr><td colspan="14" style="text-align:center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="no-print">
        <button type="button" class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
        <button type="button" class="btn-copy" onclick="copyTsv()">Salin TSV (ke Google Sheet)</button>
        <a class="btn-excel" href="{{ route('lhp-report.getExportBlok', request()->query()) }}">Export Excel</a>
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
