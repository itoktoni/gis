<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Report LHP {{ request('dari') }} - {{ request('sampai') }}</title>
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
    @php
        $judul = 'LAPORAN HARIAN PRODUKSI (LHP)';
        $filterTitle = 'Periode: '.(request('dari') ?: '-').' s/d '.(request('sampai') ?: '-').' • '.$data->count().' baris';
    @endphp
    @include('partials.report-kop', ['judul' => $judul, 'filterTitle' => $filterTitle])

    <table class="data" id="reportTable">
        <thead>
            <tr>
                <th>No</th><th>Tanggal</th><th>Hari</th><th>Nama Kebun</th>
                <th>Luas TM<br>Planted (Ha)</th><th>Luas<br>Panen (Ha)</th><th>TK Panen<br>(HK)</th>
                <th>Janjang<br>Panen (Jjg)</th><th>Produksi<br>Panen (Kg)</th>
                <th>Output Panen<br>(Ha/HK)</th><th>Output Panen<br>(Kg/HK)</th>
                <th>Target<br>Produksi (Kg)</th><th>Produksi TBS<br>RKAP (Kg)</th>
                <th>Janjang<br>Kirim (Jjg)</th><th>Produksi TBS<br>Kirim (Kg)</th>
                <th>BJR</th><th>Brondolan<br>(Kg)</th><th>% Kutip<br>Brondolan</th>
                <th>Restan<br>(Kg)</th><th>Restan<br>Jjg</th><th>Restan Brd<br>(Kg)</th>
                <th>UP Ha<br>&lt;= 10 Hari</th><th>UP Ha<br>11 s.d 15 Hari</th><th>UP Ha<br>&gt; 15 Hari</th>
                <th>Pagi<br>Jam 6 - 11</th><th>Siang<br>Jam 11 - 16</th><th>Malam<br>Jam 16 - 6</th><th>Total</th>
                <th>Pusingan Blok</th>
            </tr>
        </thead>
        <tbody>
            @php $restan = []; @endphp
            @forelse($data as $i => $r)
            @php
                $kid = $r->lhp_id_kebun;
                $tk = (float) ($r->lhp_tk_hk ?? 0);
                $luasPanen = (float) ($r->lhp_luas_panen ?? 0);
                $prodPanen = (float) ($r->lhp_produksi_panen_kg ?? 0);
                $jjKirim = (int) ($r->lhp_janjang_kirim ?? 0);
                $prodKirim = (float) ($r->lhp_produksi_kirim_kg ?? 0);
                $brondolan = (float) ($r->lhp_brondolan_kg ?? 0);
                if (! array_key_exists($kid, $restan)) {
                    $restan[$kid] = (float) (($opening[$kid] ?? 0));
                }
                $restan[$kid] += $prodPanen - $prodKirim;
                $hujanPagi = (float) ($r->lhp_hujan_pagi ?? 0);
                $hujanSiang = (float) ($r->lhp_hujan_siang ?? 0);
                $hujanMalam = (float) ($r->lhp_hujan_malam ?? 0);
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->lhp_tanggal?->format('Y-m-d') }}</td>
                <td>{{ $r->lhp_hari }}</td>
                <td>{{ $r->hasKebun?->kebun_nama }}</td>
                <td class="num">{{ $r->hasKebun?->kebun_luas_ha }}</td>
                <td class="num">{{ $r->lhp_luas_panen }}</td>
                <td class="num">{{ $r->lhp_tk_hk }}</td>
                <td class="num">{{ $r->lhp_janjang_panen }}</td>
                <td class="num">{{ $r->lhp_produksi_panen_kg }}</td>
                <td class="num">{{ $tk > 0 ? round($luasPanen / $tk, 4) : 0 }}</td>
                <td class="num">{{ $tk > 0 ? round($prodPanen / $tk, 2) : 0 }}</td>
                <td class="num">{{ $r->hasKebun?->kebun_target_produksi_kg }}</td>
                <td class="num">{{ $r->hasKebun?->kebun_rkap_kg }}</td>
                <td class="num">{{ $r->lhp_janjang_kirim }}</td>
                <td class="num">{{ $r->lhp_produksi_kirim_kg }}</td>
                <td class="num">{{ $jjKirim > 0 ? round($prodKirim / $jjKirim, 2) : 0 }}</td>
                <td class="num">{{ $r->lhp_brondolan_kg }}</td>
                <td class="num">{{ $prodKirim > 0 ? round($brondolan / $prodKirim * 100, 2) : 0 }}</td>
                <td class="num">{{ round($restan[$kid], 2) }}</td>
                <td class="num">{{ $r->lhp_restan_janjang ?? 0 }}</td>
                <td class="num">{{ $r->lhp_restan_brondolan_kg ?? 0 }}</td>
                <td class="num">{{ $r->lhp_up_le10 }}</td>
                <td class="num">{{ $r->lhp_up_11_15 }}</td>
                <td class="num">{{ $r->lhp_up_gt15 }}</td>
                <td class="num">{{ $r->lhp_hujan_pagi }}</td>
                <td class="num">{{ $r->lhp_hujan_siang }}</td>
                <td class="num">{{ $r->lhp_hujan_malam }}</td>
                <td class="num">{{ round($hujanPagi + $hujanSiang + $hujanMalam, 2) }}</td>
                <td>@forelse(($pusinganByLhp[$r->lhp_id] ?? []) as $p)<span style="display:block;white-space:nowrap">{{ $p['kode'] }}: {{ $p['hari'] === null ? 'perdana' : 'Pusingan '.$p['hari'].' hr' }}</span>@empty - @endforelse</td>
            </tr>
            @empty
            <tr><td colspan="30" style="text-align:center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('partials.report-footer')

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
