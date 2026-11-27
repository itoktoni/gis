{{-- Kop report: logo kiri + header kanan, tinggi sama. Layout normal (relative).
     Pakai: @include('partials.report-kop', ['judul' => $judul, 'filterTitle' => $filterTitle]) --}}
@php
    $kop = $kop ?? \App\Models\WebsiteSetting::report();
    $leftUrl = $kopImgLeft ?? \App\Models\WebsiteSetting::fileUrl($kop['header_left'] ?? null)
        ?? \App\Models\WebsiteSetting::fileUrl(config('website.logo'));
    $headerUrl = $kopImgRight ?? \App\Models\WebsiteSetting::fileUrl($kop['header_image'] ?? null);
    $isPdf = $pdf ?? false;
@endphp
@if(! isset($kop['show_header']) || ! empty($kop['show_header']))
@if($isPdf)
{{-- Mode PDF (DomPDF): fixed = berulang di SEMUA halaman --}}
<div style="position:fixed;top:-85px;left:0;right:0;">
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="vertical-align:middle;text-align:left;">
                @if($leftUrl)<img src="{{ $leftUrl }}" alt="Logo" style="height:70px;">@endif
            </td>
            <td style="vertical-align:middle;text-align:right;">
                @if($headerUrl)<img src="{{ $headerUrl }}" alt="Kop" style="height:70px;">@endif
            </td>
        </tr>
    </table>
    <div style="border-bottom:3px double #111;margin-top:4px;"></div>
</div>
@else
<table style="position:relative;width:100%;border-collapse:collapse;margin-bottom:6px;">
    <tr>
        <td style="width:150px;vertical-align:middle;text-align:left;">
            @if($leftUrl)
            <img src="{{ $leftUrl }}" alt="Logo" style="height:110px;max-width:140px;object-fit:contain;">
            @endif
        </td>
        <td style="vertical-align:middle;text-align:right;">
            @if($headerUrl)
            <img src="{{ $headerUrl }}" alt="Kop" style="height:110px;max-width:100%;object-fit:contain;">
            @else
            <div style="font-size:17px;font-weight:bold;letter-spacing:.04em;">{{ strtoupper($kop['instansi'] ?? config('website.name')) }}</div>
            @if(! empty($kop['kop_alamat']))<div style="font-size:10px;color:#333;">{{ $kop['kop_alamat'] }}</div>@endif
            @endif
        </td>
    </tr>
</table>
<div style="position:relative;border-bottom:3px double #111;margin-bottom:12px;"></div>
@endif
@endif {{-- show_header --}}
@if(! empty($judul))
<div style="position:relative;text-align:center;margin-bottom:2px;font-size:15px;font-weight:bold;letter-spacing:.05em;">{{ strtoupper($judul) }}</div>
@endif
@if(! empty($filterTitle))
<div style="position:relative;text-align:center;margin-bottom:12px;font-size:11px;color:#333;">{{ $filterTitle }}</div>
@endif
