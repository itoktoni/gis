<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">No</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Tanggal</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Hari</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Nama Kebun</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Luas TM Planted (Ha)</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Luas Panen (Ha)</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">TK Panen (HK)</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Janjang Panen (Jjg)</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Produksi Panen (Kg)</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Output Panen (Ha/HK)</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Output Panen (Kg/HK)</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Target Produksi (Kg)</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Produksi TBS RKAP (Kg)</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Janjang Kirim (Jjg)</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Produksi TBS Kirim (Kg)</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">BJR</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $i => $lhp)
                @php
                    $tk = (float) ($lhp->lhp_tk_hk ?? 0);
                    $janjangPanen = (int) ($lhp->lhp_janjang_panen ?? 0);
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $lhp->lhp_tanggal?->format('Y-m-d') }}</td>
                    <td>{{ $lhp->lhp_hari }}</td>
                    <td>{{ $lhp->hasKebun?->kebun_nama }}{{ $lhp->hasKebun?->hasPt ? ' - '.$lhp->hasKebun->hasPt->pt_nama : '' }}</td>
                    <td>{{ $lhp->lhp_luas_tm }}</td>
                    <td>{{ $lhp->lhp_luas_panen }}</td>
                    <td>{{ $lhp->lhp_tk_hk }}</td>
                    <td>{{ $lhp->lhp_janjang_panen }}</td>
                    <td>{{ $lhp->lhp_produksi_panen_kg }}</td>
                    <td>{{ $tk > 0 ? round((float) $lhp->lhp_luas_panen / $tk, 4) : 0 }}</td>
                    <td>{{ $tk > 0 ? round((float) $lhp->lhp_produksi_panen_kg / $tk, 2) : 0 }}</td>
                    <td></td>
                    <td></td>
                    <td>{{ $lhp->lhp_janjang_kirim }}</td>
                    <td>{{ $lhp->lhp_produksi_kirim_kg }}</td>
                    <td>{{ $janjangPanen > 0 ? round((float) $lhp->lhp_produksi_panen_kg / $janjangPanen, 2) : 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
