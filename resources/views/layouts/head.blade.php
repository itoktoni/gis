<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="notification-enabled" content="{{ config('centrifugo.enabled') ? 'true' : 'false' }}"/>
    @if(config('centrifugo.url'))
    <meta name="centrifugo-url" content="{{ str_replace(['http://', 'https://'], ['ws://', 'wss://'], config('centrifugo.url')) }}/connection/websocket"/>
    @endif
    @auth
    <meta name="user-id" content="{{ auth()->id() }}"/>
    @endauth
    @php
        $faviconUrl = \App\Models\WebsiteSetting::fileUrl(\App\Models\WebsiteSetting::merged()['favicon'] ?? null) ?? '/favicon.ico';
    @endphp
    <title>{{ config('website.name', $title ?? 'CMS') }}</title>
    <link rel="icon" href="{{ $faviconUrl }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/notifications.js'])
    @livewireStyles
    {{-- Self-hosted apexcharts: CDN sync script di head memperlambat first paint saat reload --}}
    <script src="{{ asset('js/apexcharts.min.js') }}"></script>
    <script src="{{ asset('js/table.js') }}"></script>
    {{-- Dynamic theme color overrides from website settings --}}
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
    {{-- Leaflet/Turf lazy-loader — harus di head agar tersedia sebelum inline
        script peta di body jalan. Mencegah "Uncaught ReferenceError: L is not
        defined" saat Livewire navigate (inline L.map dieksekusi sebelum
        unpkg script selesai dimuat). Pakai: window.ensureLeaflet(fn). --}}
    <script>
        window.ensureLeaflet = function (cb) {
            function ensureCss() {
                if (!document.querySelector('link[data-leaflet]')) {
                    var l = document.createElement('link');
                    l.rel = 'stylesheet';
                    l.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    l.setAttribute('data-leaflet', '');
                    document.head.appendChild(l);
                }
            }
            ensureCss();
            if (window.L) { cb(); return; }
            var existing = document.querySelector('script[data-leaflet]');
            if (existing) {
                existing.addEventListener('load', cb, { once: true });
                return;
            }
            var s = document.createElement('script');
            s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            s.setAttribute('data-leaflet', '');
            s.onload = cb;
            s.onerror = function () { console.warn('Failed to load Leaflet from unpkg.'); };
            document.head.appendChild(s);
        };
        window.ensureTurf = function (cb) {
            if (window.turf) { cb(); return; }
            var existing = document.querySelector('script[data-turf]');
            if (existing) {
                existing.addEventListener('load', cb, { once: true });
                return;
            }
            var s = document.createElement('script');
            s.src = 'https://unpkg.com/@turf/turf@6/turf.min.js';
            s.setAttribute('data-turf', '');
            s.onload = cb;
            s.onerror = function () { console.warn('Failed to load Turf.js from unpkg.'); };
            document.head.appendChild(s);
        };
    </script>
    @include('components.printer-js')
    @stack('styles')
</head>
