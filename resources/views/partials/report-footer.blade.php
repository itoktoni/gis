{{-- Footer report: blok TTD + gambar footer, layout normal (relative) di akhir dokumen.
     Pakai: @include('partials.report-footer') --}}
@php
    $footer = $footer ?? \App\Models\WebsiteSetting::report();
    $footerUrl = $kopImgFooter ?? \App\Models\WebsiteSetting::fileUrl($footer['footer_image'] ?? null);
    $kota = $footer['kota'] ?? '';
    $tanggal = \Carbon\Carbon::now()->translatedFormat('d F Y');
    $isPdf = $pdf ?? false;
@endphp
@if($isPdf && (! isset($footer['show_footer']) || ! empty($footer['show_footer'])) && $footerUrl)
{{-- Mode PDF (DomPDF): fixed = berulang di SEMUA halaman --}}
<div style="position:fixed;bottom:-60px;left:0;right:0;text-align:center;">
    <img src="{{ $footerUrl }}" alt="Footer" style="width:100%;max-height:50px;">
</div>
@endif
<table style="position:relative;width:100%;border-collapse:collapse;margin-top:28px;">
    <tr>
        <td style="width:55%;font-size:10px;color:#444;vertical-align:top;">
            @if(! empty($footer['show_print_meta']))
            <div>Dicetak {{ now()->format('d/m/Y H:i') }} oleh {{ auth()->user()->name ?? 'Sistem' }}</div>
            @endif
        </td>
        <td style="width:45%;text-align:center;font-size:11px;vertical-align:top;">
            {{ $kota ? $kota.', ' : '' }}{{ $tanggal }}<br>
            {{ $footer['jabatan'] ?? 'Mengetahui,' }}<br><br><br><br>
            <div style="text-decoration:underline;font-weight:bold;">{{ $footer['nama_tertanda'] ?: '( ............................................ )' }}</div>
            @if(! empty($footer['nip']))<div>NIP. {{ $footer['nip'] }}</div>@endif
        </td>
    </tr>
</table>
@if(!$isPdf && (! isset($footer['show_footer']) || ! empty($footer['show_footer'])) && $footerUrl)
<div style="position:relative;margin-top:12px;text-align:center;">
    <img src="{{ $footerUrl }}" alt="Footer" style="width:100%;max-height:80px;object-fit:contain;">
</div>
@endif
