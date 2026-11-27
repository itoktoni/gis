<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Report Cuti</title>
<style>body{font-family:sans-serif;font-size:10pt}table{border-collapse:collapse;width:100%}td,th{border:1px solid #333;padding:4px}</style>
</head>
<body onload="window.print()">
@php
    $judul = 'REPORT CUTI';
    $filterTitle = request('dari') ? 'Periode: '.request('dari').' s/d '.request('sampai') : 'Semua Data';
@endphp
@include('partials.report-kop', ['judul' => $judul, 'filterTitle' => $filterTitle])
<h4>Rekap Sisa Cuti per Karyawan</h4>
<table>
<tr><th>Nama</th><th>Kuota</th><th>Terpakai</th><th>Sisa</th></tr>
@foreach($summary as $row)
<tr><td>{{ $row['nama'] }}</td><td>{{ $row['kuota'] }}</td><td>{{ $row['terpakai'] }}</td><td>{{ $row['sisa'] }}</td></tr>
@endforeach
</table>
<br>
<h4>Detail Pengajuan</h4>
<table>
<tr><th>No</th><th>Nama</th><th>Jenis</th><th>Mulai</th><th>Selesai</th><th>Hari</th><th>Status</th><th>Keperluan</th></tr>
@foreach($data as $i => $table)
<tr><td>{{ $i+1 }}</td><td>{{ $table->hasUser?->name }}</td><td>{{ $table->cuti_jenis }}</td><td>{{ $table->cuti_tanggal_mulai?->format('Y-m-d') }}</td><td>{{ $table->cuti_tanggal_selesai?->format('Y-m-d') }}</td><td>{{ $table->cuti_jumlah_hari }}</td><td>{{ $table->cuti_status }}</td><td>{{ $table->cuti_keperluan }}</td></tr>
@endforeach
</table>

@include('partials.report-footer')
</body>
</html>
