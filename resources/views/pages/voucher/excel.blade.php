<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">No Voucher</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Vendor</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Estate</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">No Invoice</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Redaksi</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Qty</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Satuan</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Nilai (Rp)</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Tanggal</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Status</th>
                <th style="background:#00288e;color:#ffffff;font-weight:bold;">Tgl Bayar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $v)
                <tr>
                    <td>{{ $v->voucher_no }}</td>
                    <td>{{ $v->hasVendor?->vendor_nama }}</td>
                    <td>{{ $v->voucher_estate ?? $v->hasKebun?->kebun_nama }}</td>
                    <td>{{ $v->voucher_no_invoice }}</td>
                    <td>{{ $v->voucher_redaksi }}</td>
                    <td>{{ $v->voucher_qty }}</td>
                    <td>{{ $v->voucher_satuan }}</td>
                    <td>{{ $v->voucher_nilai }}</td>
                    <td>{{ $v->voucher_tanggal?->format('Y-m-d') }}</td>
                    <td>{{ $v->status_label }}</td>
                    <td>{{ $v->voucher_tanggal_bayar?->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr><td colspan="11">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
