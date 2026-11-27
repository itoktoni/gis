<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Report Absen</title>
    <style>
        @page { margin: 110px 25px 75px; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 9px; color: #111; margin: 0; }
        h4 { font-size: 11px; margin: 12px 0 6px; }
        table.data { border-collapse: collapse; width: 100%; }
        table.data th, table.data td { border: 1px solid #999; padding: 3px 5px; text-align: left; }
        table.data th { background: #eee; font-weight: bold; }
    </style>
</head>
<body>
    @include('partials.report-kop', ['kop' => $kop ?? null, 'judul' => $judulReport ?? 'REPORT ABSEN', 'filterTitle' => ($filterTitle ?? '').' • '.count($summary ?? []).' karyawan / '.(is_countable($data ?? null) ? count($data) : 0).' baris', 'pdf' => true])

    <h4>Summary per Karyawan</h4>
    <table class="data">
        <thead><tr><th>No</th><th>Karyawan</th><th>Total</th><th>Checkin</th><th>Kunjungan</th><th>Valid</th><th>Luar Jangkauan</th><th>Palsu</th><th>Hari Hadir</th><th>% Valid</th></tr></thead>
        <tbody>
            @forelse($summary ?? [] as $i => $row)
            <tr><td>{{ $i + 1 }}</td><td>{{ $row['nama'] }}</td><td>{{ $row['total'] }}</td><td>{{ $row['checkin'] }}</td><td>{{ $row['kunjungan'] }}</td><td>{{ $row['valid'] }}</td><td>{{ $row['invalid'] }}</td><td>{{ $row['fake'] }}</td><td>{{ $row['hari_hadir'] }}</td><td>{{ $row['persen_valid'] }}%</td></tr>
            @empty
            <tr><td colspan="10" style="text-align:center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h4>Detail Check-In</h4>
    <table class="data">
        <thead><tr><th>No</th><th>Tanggal</th><th>Karyawan</th><th>Jenis</th><th>Titik</th><th>Status</th><th>Selisih (m)</th><th>Alamat</th></tr></thead>
        <tbody>
            @forelse($data as $i => $table)
            <tr><td>{{ $i + 1 }}</td><td>{{ $table->created_at?->format('Y-m-d H:i') }}</td><td>{{ $table->hasUser?->name }}</td><td>{{ $table->kunjungan_id ? 'Kunjungan' : 'Checkin' }}</td><td>{{ $table->hasKunjungan?->kunjungan_nama ?? '-' }}</td><td>{{ $table->status }}</td><td>{{ formatJarak($table->selisih_radius_m, true) }}</td><td>{{ $table->address }}</td></tr>
            @empty
            <tr><td colspan="8" style="text-align:center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('partials.report-footer', ['footer' => $footer ?? null, 'pdf' => true])
</body>
</html>
