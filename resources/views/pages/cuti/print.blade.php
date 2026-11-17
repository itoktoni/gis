<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Permohonan Cuti #{{ $cuti->cuti_id }}</title>
    <style>
        body { font-family: serif; font-size: 12pt; line-height: 1.6; }
        .title { text-align: center; font-weight: bold; margin-bottom: 0; }
        .subtitle { text-align: center; font-size: 10pt; font-style: italic; margin-top: 0; }
        table { border-collapse: collapse; width: 100%; }
        table.bordered td, table.bordered th { border: 1px solid #333; padding: 6px; }
        .sign { margin-top: 10px; }
        .right { text-align: right; }
        .mt { margin-top: 24px; }
    </style>
</head>
<body>
    <p class="title">PERMOHONAN CUTI TRIWULAN / TAHUNAN / BESAR *</p>
    <p class="subtitle">* Coret yang tidak perlu</p>

    <p>Yang bertanda tangan di bawah ini</p>
    <table>
        <tr><td width="220">Nama</td><td>: {{ $cuti->hasUser?->name ?? '-' }}</td></tr>
        <tr><td>Bagian</td><td>: {{ $cuti->cuti_bagian ?? '-' }}</td></tr>
        <tr><td>Mengajukan cuti selama</td><td>: {{ $cuti->cuti_jumlah_hari }} hari ({{ $cuti->cuti_jenis }})</td></tr>
        <tr><td>Mulai tanggal</td><td>: {{ formatDate($cuti->cuti_tanggal_mulai) }} s/d {{ formatDate($cuti->cuti_tanggal_selesai) }}</td></tr>
        <tr><td>Keperluan cuti</td><td>: {{ $cuti->cuti_keperluan }}</td></tr>
        <tr><td>Alamat cuti</td><td>: {{ $cuti->cuti_alamat ?? '-' }}</td></tr>
    </table>

    <p class="right">Jakarta, {{ now()->format('d-m-Y') }}<br>Pemohon<br><br><br><br>( {{ $cuti->hasUser?->name ?? '....................' }} )</p>

    <p class="title mt">Catatan Monitoring Cuti</p>
    <table>
        <tr><td width="220">Diagendakan No</td><td>: ...................</td></tr>
        <tr><td>No Monitoring</td><td>: ...................</td></tr>
        <tr><td>Sisa / Hak Cuti</td><td>: {{ $sisa ?? '-' }} / {{ \App\Models\Cuti::kuota((int) $cuti->cuti_tanggal_mulai->format('Y')) }} (hari)</td></tr>
        <tr><td>Total Cuti Yang Diambil</td><td>: {{ \App\Models\Cuti::kuota((int) $cuti->cuti_tanggal_mulai->format('Y')) - ($sisa ?? 0) }} (hari)</td></tr>
        <tr><td>Catatan</td><td>: {{ $cuti->cuti_catatan ?? '......................................................................' }}</td></tr>
    </table>

    <p>Sisa Cuti Tahun {{ $cuti->cuti_tanggal_mulai->format('Y') }}&nbsp;&nbsp;Sisa : {{ $sisa ?? '-' }} hari</p>

    <p class="title mt">Rekomendasi Atasan Langsung</p>
    <p>.....................................<br><br><br>(...............................) &nbsp;&nbsp;&nbsp;&nbsp; Jakarta, ....................</p>

    <p class="title mt">Keputusan</p>
    <table>
        <tr><td width="280">Diijinkan untuk mengambil cuti</td><td>: {{ $cuti->cuti_status === 'approved' ? 'Disetujui' : '....................' }}</td></tr>
        <tr><td>Ditunda / ditangguhkan</td><td>: {{ $cuti->cuti_status === 'rejected' ? 'Ya' : '....................' }}</td></tr>
    </table>
    <p class="right">................., ....................<br>...............................<br><br><br><br>( {{ $cuti->hasApprover?->name ?? '..........................................' }} )</p>
</body>
</html>
