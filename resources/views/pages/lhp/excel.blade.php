<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
    @php
        // Judul mengikuti pola file referensi: LAPORAN HARIAN PRODUKSI - {NAMA KEBUN}
        $judulKebun = $data->first()?->hasKebun?->kebun_nama ?? 'SEMUA KEBUN';
        $th = 'background:#00288e;color:#ffffff;font-weight:bold;text-align:center;vertical-align:middle;';
        $restanKg = [];
        $restanJjg = [];
    @endphp
    <table>
        <tr><td colspan="29" style="font-weight:bold;">LAPORAN HARIAN PRODUKSI - {{ strtoupper($judulKebun) }}</td></tr>
    </table>
    <br>
    <table border="1">
        <thead>
            <tr>
                <th rowspan="2" style="{{ $th }}">No</th>
                <th rowspan="2" style="{{ $th }}">Tanggal</th>
                <th rowspan="2" style="{{ $th }}">Hari</th>
                <th rowspan="2" style="{{ $th }}">Nama Kebun</th>
                <th rowspan="2" style="{{ $th }}">Luas TM<br>Planted (Ha)</th>
                <th rowspan="2" style="{{ $th }}">Luas<br>Panen (Ha)</th>
                <th rowspan="2" style="{{ $th }}">TK Panen<br>(HK)</th>
                <th rowspan="2" style="{{ $th }}">Janjang<br>Panen (Jjg)</th>
                <th rowspan="2" style="{{ $th }}">Produksi<br>Panen (Kg)</th>
                <th rowspan="2" style="{{ $th }}">Output Panen<br>(Ha/HK)</th>
                <th rowspan="2" style="{{ $th }}">Output Panen<br>(Kg/HK)</th>
                <th rowspan="2" style="{{ $th }}">Target<br>Produksi (Kg)</th>
                <th rowspan="2" style="{{ $th }}">Produksi TBS<br>RKAP (Kg)</th>
                <th rowspan="2" style="{{ $th }}">Janjang<br>Kirim (Jjg)</th>
                <th rowspan="2" style="{{ $th }}">Produksi TBS<br>Kirim (Kg)</th>
                <th rowspan="2" style="{{ $th }}">BJR</th>
                <th rowspan="2" style="{{ $th }}">Brondolan<br>(Kg)</th>
                <th rowspan="2" style="{{ $th }}">% Kutip<br>Brondolan</th>
                <th rowspan="2" style="{{ $th }}">Restan<br>(Kg)</th>
                <th rowspan="2" style="{{ $th }}">UP Ha<br>&lt;= 10 Hari</th>
                <th rowspan="2" style="{{ $th }}">UP Ha<br>11 s.d 15 Hari</th>
                <th rowspan="2" style="{{ $th }}">UP Ha<br>&gt; 15 Hari</th>
                <th colspan="4" style="{{ $th }}">Curah Hujan (mm)</th>
                <th rowspan="2" style="{{ $th }}">Blok Restan</th>
                <th rowspan="2" style="{{ $th }}">Restan (Jjg)</th>
            </tr>
            <tr>
                <th style="{{ $th }}">Pagi<br>Jam 6 - 11</th>
                <th style="{{ $th }}">Siang<br>Jam 11 - 16</th>
                <th style="{{ $th }}">Malam<br>Jam 16 - 6</th>
                <th style="{{ $th }}">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $i => $lhp)
                @php
                    $tk = (float) ($lhp->lhp_tk_hk ?? 0);
                    $luasPanen = (float) ($lhp->lhp_luas_panen ?? 0);
                    $prodPanen = (float) ($lhp->lhp_produksi_panen_kg ?? 0);
                    $jjPanen = (int) ($lhp->lhp_janjang_panen ?? 0);
                    $jjKirim = (int) ($lhp->lhp_janjang_kirim ?? 0);
                    $prodKirim = (float) ($lhp->lhp_produksi_kirim_kg ?? 0);
                    $brondolan = (float) ($lhp->lhp_brondolan_kg ?? 0);
                    $kid = $lhp->lhp_id_kebun;
                    // Rumus excel: Restan(Kg) = Restan_sebelumnya + Panen - Kirim (kumulatif per kebun)
                    $restanKg[$kid] = ($restanKg[$kid] ?? 0) + $prodPanen - $prodKirim;
                    // Rumus excel: Restan(Jjg) = Restan_sebelumnya + JjgPanen - JjgKirim
                    $restanJjg[$kid] = ($restanJjg[$kid] ?? 0) + $jjPanen - $jjKirim;
                    $hujanPagi = (float) ($lhp->lhp_hujan_pagi ?? 0);
                    $hujanSiang = (float) ($lhp->lhp_hujan_siang ?? 0);
                    $hujanMalam = (float) ($lhp->lhp_hujan_malam ?? 0);
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $lhp->lhp_tanggal?->format('Y-m-d') }}</td>
                    <td>{{ $lhp->lhp_hari }}</td>
                    <td>{{ $lhp->hasKebun?->kebun_nama }}</td>
                    <td>{{ $lhp->hasKebun?->kebun_luas_ha }}</td>
                    <td>{{ $lhp->lhp_luas_panen }}</td>
                    <td>{{ $lhp->lhp_tk_hk }}</td>
                    <td>{{ $lhp->lhp_janjang_panen }}</td>
                    <td>{{ $lhp->lhp_produksi_panen_kg }}</td>
                    <td>{{ $tk > 0 ? round($luasPanen / $tk, 4) : 0 }}</td>
                    <td>{{ $tk > 0 ? round($prodPanen / $tk, 2) : 0 }}</td>
                    <td>{{ $lhp->hasKebun?->kebun_target_produksi_kg }}</td>
                    <td>{{ $lhp->hasKebun?->kebun_rkap_kg }}</td>
                    <td>{{ $lhp->lhp_janjang_kirim }}</td>
                    <td>{{ $lhp->lhp_produksi_kirim_kg }}</td>
                    <td>{{ $jjKirim > 0 ? round($prodKirim / $jjKirim, 2) : 0 }}</td>
                    <td>{{ $lhp->lhp_brondolan_kg }}</td>
                    <td>{{ $prodKirim > 0 ? round($brondolan / $prodKirim * 100, 2) : 0 }}</td>
                    <td>{{ round($restanKg[$kid], 2) }}</td>
                    <td>{{ $lhp->lhp_up_le10 }}</td>
                    <td>{{ $lhp->lhp_up_11_15 }}</td>
                    <td>{{ $lhp->lhp_up_gt15 }}</td>
                    <td>{{ $lhp->lhp_hujan_pagi }}</td>
                    <td>{{ $lhp->lhp_hujan_siang }}</td>
                    <td>{{ $lhp->lhp_hujan_malam }}</td>
                    <td>{{ round($hujanPagi + $hujanSiang + $hujanMalam, 2) }}</td>
                    <td>{{ $lhp->lhp_blok_restan }}</td>
                    <td>{{ $restanJjg[$kid] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="29">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
