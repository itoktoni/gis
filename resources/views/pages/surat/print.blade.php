<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; line-height: 1.6; color: #111; }
        .kop { text-align: center; border-bottom: 3px double #111; padding-bottom: 10px; margin-bottom: 20px; }
        .kop h2 { margin: 0; font-size: 18pt; }
        .kop p { margin: 2px 0 0; font-size: 10pt; }
        .meta { margin-bottom: 16px; }
        .meta td { vertical-align: top; padding: 1px 6px 1px 0; }
        .isi { text-align: justify; }
        .isi table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .ttd { margin-top: 40px; width: 100%; }
        .ttd td { text-align: center; vertical-align: top; }
    </style>
</head>
<body>
    <div class="kop">
        <h2>{{ config('app.name', 'Perusahaan') }}</h2>
        @if($surat->hasDivisi)<p>{{ $surat->hasDivisi->divisi_nama }}</p>@endif
    </div>

    <table class="meta">
        <tr><td>Nomor</td><td>:</td><td>{{ $surat->surat_nomor ?? '-' }}</td></tr>
        <tr><td>Lampiran</td><td>:</td><td>{{ $surat->surat_lampiran ?? '-' }}</td></tr>
        @if($surat->surat_perihal)<tr><td>Perihal</td><td>:</td><td>{{ $surat->surat_perihal }}</td></tr>@endif
        @if($surat->surat_kepada)<tr><td>Kepada</td><td>:</td><td>{{ $surat->surat_kepada }}</td></tr>@endif
        @if($surat->surat_dari)<tr><td>Dari</td><td>:</td><td>{{ $surat->surat_dari }}</td></tr>@endif
    </table>

    <div class="isi">{!! $surat->surat_isi !!}</div>

    <table class="ttd">
        <tr>
            <td></td>
            <td>
                {{ formatDate($surat->surat_tanggal) }}<br><br><br><br>
                ( ........................................ )
            </td>
        </tr>
    </table>
</body>
</html>
