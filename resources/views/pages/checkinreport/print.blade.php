<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Report Absen</title>
<style>body{font-family:sans-serif;font-size:10pt}table{border-collapse:collapse;width:100%}td,th{border:1px solid #333;padding:4px}h3,h4{margin:12px 0 6px}</style>
</head>
<body onload="window.print()">
<h3>REPORT ABSEN {{ request('from') ? 'PERIODE '.request('from').' s/d '.request('to') : '' }}</h3>
<h4>Summary per Karyawan</h4>
<table>
<tr><th>No</th><th>Karyawan</th><th>Total</th><th>Valid</th><th>Luar Jangkauan</th><th>Palsu</th><th>Hari Hadir</th><th>% Valid</th></tr>
@foreach($summary as $i => $row)
<tr><td>{{ $i+1 }}</td><td>{{ $row['nama'] }}</td><td>{{ $row['total'] }}</td><td>{{ $row['valid'] }}</td><td>{{ $row['invalid'] }}</td><td>{{ $row['fake'] }}</td><td>{{ $row['hari_hadir'] }}</td><td>{{ $row['persen_valid'] }}%</td></tr>
@endforeach
</table>
<br>
<h4>Detail Check-In</h4>
<table>
<tr><th>No</th><th>Tanggal</th><th>Karyawan</th><th>Status</th><th>Selisih (m)</th><th>Alamat</th></tr>
@foreach($data as $i => $table)
<tr><td>{{ $i+1 }}</td><td>{{ $table->created_at?->format('Y-m-d H:i') }}</td><td>{{ $table->hasUser?->name }}</td><td>{{ $table->status }}</td><td>{{ formatJarak($table->selisih_radius_m, true) }}</td><td>{{ $table->address }}</td></tr>
@endforeach
</table>
</body>
</html>
