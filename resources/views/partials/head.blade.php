<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="theme-color" content="#000000" />

@php
    $wsName = ($websiteSettings ?? [])['name'] ?? config('app.name', 'Laravel');
    $faviconUrl = \App\Models\WebsiteSetting::fileUrl(($websiteSettings ?? [])['favicon'] ?? null) ?? '/favicon.ico';
@endphp

<title>{{ filled($title ?? null) ? $title.' - '.$wsName : $wsName }}</title>

<link rel="icon" href="{{ $faviconUrl }}" sizes="any">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/manifest.json">

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Dynamic theme color overrides from website settings (sama seperti layouts/head) --}}
@php
    $__primary = \App\Models\WebsiteSetting::primaryColor();
    $__palette = \App\Models\WebsiteSetting::generatePalette($__primary);
@endphp
<style>
    :root {
        --color-primary: {{ $__primary }};
        --color-secondary: {{ $__primary }};
        --color-secondary-container: {{ $__primary }};
        --color-on-primary: {{ $__palette['on_primary'] }};
        --color-primary-container: {{ $__palette['primary_container'] }};
        --color-on-primary-container: {{ $__palette['on_primary_container'] }};
        --color-primary-fixed: {{ $__palette['primary_fixed'] }};
        --color-primary-fixed-dim: {{ $__palette['primary_fixed_dim'] }};
    }
</style>
