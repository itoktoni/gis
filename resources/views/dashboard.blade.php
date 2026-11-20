<x-layouts::app title="Dashboard">
    <div>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-2xl font-bold text-on-surface">Dashboard</h2>
            <a href="{{ route('lhp.getCreate') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-primary text-on-primary px-4 py-2 text-sm font-semibold hover:bg-primary/90">
                <span class="material-symbols-outlined text-lg">add</span>
                Input LHP Hari Ini
            </a>
        </div>

        {{-- Peta Area Sawit --}}
        <x-card label="Peta Area Sawit" :noGrid="true">
            <div class="flex flex-col sm:flex-row gap-3 mb-3">
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Total Kebun</p>
                    <p class="text-lg font-bold">{{ $kebunMap->count() }} kebun</p>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Bermasalah</p>
                    <p class="text-lg font-bold text-error">{{ $kebunMap->where('masalah', '>', 0)->count() }} kebun</p>
                </div>
                <a href="{{ route('kebun.getTable') }}" wire:navigate class="sm:ml-auto inline-flex items-center justify-center gap-2 self-start rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-variant hover:bg-surface-container">
                    <span class="material-symbols-outlined text-lg">map</span> Kelola Kebun
                </a>
            </div>
            <div id="dashboard-map" style="height: 380px;" class="rounded-xl border border-outline-variant z-0"></div>
            <div class="flex flex-wrap gap-4 mt-2 text-xs text-on-surface-variant">
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full" style="background:#3388ff"></span> Blok kebun (area)</span>
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full" style="background:#dc2626"></span> Bermasalah</span>
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full" style="background:#16a34a"></span> Objek / POS</span>
            </div>
        </x-card>

        {{-- LHP Harian --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card mb-5">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-4 mb-4 border-b border-outline-variant">
                <h3 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">agriculture</span>
                    LHP Harian — {{ $lhpStats['tanggal'] ?? '-' }}
                </h3>
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <input type="date" name="tanggal" value="{{ $lhpStats['tanggal_raw'] }}" class="h-10 px-3 bg-white border border-outline-variant rounded-lg text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary" onchange="this.form.submit()">
                    <a href="{{ route('lhp.getTable') }}" wire:navigate class="text-sm text-primary hover:underline font-medium">Semua data</a>
                </form>
            </div>
            <div class="flex flex-wrap items-center gap-2 mb-5 rounded-xl bg-surface-container px-4 py-3">
                <span class="material-symbols-outlined text-primary">download</span>
                <span class="text-sm text-on-surface-variant">Export {{ number_format($lhpPerKebun->count(), 0, ',', '.') }} baris tanggal ini ke Excel (16 kolom format DB_All)</span>
                <span class="ml-auto flex items-center gap-2">
                    <a href="{{ route('lhp.getExportExcel', ['tanggal' => $lhpStats['tanggal_raw']]) }}" class="inline-flex items-center gap-2 rounded-lg bg-primary text-on-primary px-4 py-2 text-sm font-semibold hover:bg-primary/90">
                        <span class="material-symbols-outlined text-lg">table_view</span>
                        Export Excel
                    </a>
                    <a href="{{ route('lhp.getExportExcel', ['all' => 1]) }}" class="inline-flex items-center gap-2 rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-variant hover:bg-surface-container">
                        Semua Data
                    </a>
                </span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary">scale</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Produksi Panen</span>
                    </div>
                    <span class="text-2xl font-bold text-primary">{{ number_format($lhpStats['produksi_panen'], 0, ',', '.') }}</span>
                    <span class="text-xs text-on-surface-variant">kg • {{ number_format($lhpStats['janjang_panen'], 0, ',', '.') }} jjg</span>
                </div>
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-success">local_shipping</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Produksi Kirim (TBS)</span>
                    </div>
                    <span class="text-2xl font-bold text-success">{{ number_format($lhpStats['produksi_kirim'], 0, ',', '.') }}</span>
                    <span class="text-xs text-on-surface-variant">kg • {{ number_format($lhpStats['janjang_kirim'], 0, ',', '.') }} jjg</span>
                </div>
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-warning">group</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Kebun Lapor</span>
                    </div>
                    <span class="text-2xl font-bold">{{ $lhpStats['kebun_lapor'] }}<span class="text-sm text-on-surface-variant font-normal"> / {{ $lhpStats['total_kebun'] }}</span></span>
                    <span class="text-xs text-on-surface-variant block">{{ number_format($lhpStats['luas_panen'], 2, ',', '.') }} ha • {{ number_format($lhpStats['tk_hk'], 0, ',', '.') }} HK</span>
                </div>
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-info">monitoring</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Rata-rata BJR</span>
                    </div>
                    @php $avgBjr = $lhpStats['janjang_kirim'] ? $lhpStats['produksi_kirim'] / $lhpStats['janjang_kirim'] : 0; @endphp
                    <span class="text-2xl font-bold">{{ number_format($avgBjr, 2, ',', '.') }}</span>
                    <span class="text-xs text-on-surface-variant">kg/jjg kirim</span>
                </div>
            </div>
            @if($lhpPerKebun->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[720px]">
                    <thead>
                        <tr class="text-left text-xs text-on-surface-variant uppercase border-b border-outline-variant">
                            <th class="pb-3 pr-4">Kebun</th>
                            <th class="pb-3 pr-4 text-right">Luas Panen</th>
                            <th class="pb-3 pr-4 text-right">TK</th>
                            <th class="pb-3 pr-4 text-right">Jjg Panen</th>
                            <th class="pb-3 pr-4 text-right">Prod Panen</th>
                            <th class="pb-3 pr-4 text-right">Jjg Kirim</th>
                            <th class="pb-3 text-right">Prod Kirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lhpPerKebun as $row)
                        <tr class="border-b border-outline-variant/50">
                            <td class="py-2 pr-4 font-medium">{{ $row->hasKebun?->kebun_nama ?? '-' }}@if($row->hasKebun?->hasPt)<span class="block text-[10px] font-normal text-on-surface-variant">{{ $row->hasKebun->hasPt->pt_nama }}</span>@endif</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($row->lhp_luas_panen, 2, ',', '.') }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($row->lhp_tk_hk, 0, ',', '.') }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($row->lhp_janjang_panen, 0, ',', '.') }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($row->lhp_produksi_panen_kg, 0, ',', '.') }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($row->lhp_janjang_kirim, 0, ',', '.') }}</td>
                            <td class="py-2 text-right font-semibold text-primary">{{ number_format($row->lhp_produksi_kirim_kg, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8 text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl mb-2 block">agriculture</span>
                <p class="text-sm mb-3">Belum ada LHP tanggal ini.</p>
                <a href="{{ route('lhp.getCreate') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg bg-primary text-on-primary px-4 py-2 text-sm font-semibold">Input sekarang</a>
            </div>
            @endif
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card mb-5">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">analytics</span>
                System Overview
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary">people</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Total Users</span>
                    </div>
                    <span class="text-2xl font-bold text-primary">{{ $stats['total_users'] }}</span>
                </div>
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-info">notifications</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Notifications</span>
                    </div>
                    <span class="text-2xl font-bold text-info">{{ $stats['total_notifications'] }}</span>
                </div>
                <div class="bg-surface-container rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-warning">mark_email_unread</span>
                        </div>
                        <span class="text-xs font-semibold text-on-surface-variant uppercase">Unread</span>
                    </div>
                    <span class="text-2xl font-bold text-warning">{{ $stats['unread_notifications'] }}</span>
                </div>
            </div>
        </div>
        {{-- Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
            <div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card min-w-0 overflow-hidden">
                <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">trending_up</span>
                    User Registrations
                </h3>
                <div class="min-w-0">
                    {!! $userChart->container() !!}
                </div>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card min-w-0 overflow-hidden">
                <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">pie_chart</span>
                    Notifications
                </h3>
                <div class="bg-surface-container rounded-lg p-4 min-w-0 overflow-hidden">
                    {!! $notifChart->container() !!}
                </div>
            </div>
        </div>


        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">person</span>
                Recent Users
            </h3>
            @if($recentUsers->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-on-surface-variant uppercase border-b border-outline-variant">
                            <th class="pb-3 pr-4">Name</th>
                            <th class="pb-3 pr-4">Email</th>
                            <th class="pb-3 pr-4">Role</th>
                            <th class="pb-3">Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentUsers as $user)
                        <tr class="border-b border-outline-variant/50">
                            <td class="py-3 pr-4 font-medium">{{ $user->name }}</td>
                            <td class="py-3 pr-4 text-on-surface-variant">{{ $user->email }}</td>
                            <td class="py-3 pr-4">
                                <span class="bg-surface-container text-xs px-2 py-1 rounded-full">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td class="py-3 text-on-surface-variant">{{ $user->created_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8 text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl mb-2 block">person_off</span>
                <p class="text-sm">No users found</p>
            </div>
            @endif
        </div>
    </div>

    @push('scripts')
        {!! $userChart->script() !!}
        {!! $notifChart->script() !!}
    @endpush

    <script>
        // Peta dashboard — pola sama seperti halaman Kebun Sawit.
        (function () {
            function bootMap() {
            var rows = @json($kebunMap);
            // Renderer canvas: ribuan area (MultiPolygon banyak lokasi) tetap lancar.
            var map = L.map('dashboard-map', { renderer: L.canvas() }).setView([-2.5, 118], 5);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            var bounds = [];
            rows.forEach(function (r) {
                var layer = null;
                var bermasalah = (r.masalah || 0) > 0;
                var warna = bermasalah ? '#dc2626' : '#3388ff';
                function popupHtml() {
                    var h = '<b>' + (r.nama || '') + '</b><br>' + (r.luas || '-') + ' ha';
                    if (bermasalah) {
                        h += '<br><a href="/masalah/table?filters[masalah_id_kebun][$eq]=' + r.id + '" style="color:#dc2626;font-weight:bold;">⚠ ' + r.masalah + ' permasalahan terbuka →</a>';
                    }
                    return h;
                }
                if (r.polygon) {
                    try {
                        var gj = JSON.parse(r.polygon);
                        var geo = gj.type === 'Feature' ? gj : { type: 'Feature', properties: {}, geometry: gj };
                        layer = L.geoJSON(geo, { style: { color: warna, weight: 3, fillColor: warna, fillOpacity: 0.45 } }).addTo(map);
                        layer.bindPopup(popupHtml());
                        layer.eachLayer(function (l) { bounds.push(l.getBounds()); });
                    } catch (e) { layer = null; }
                }
                if (r.lat && r.lng) {
                    var dot = L.circleMarker([r.lat, r.lng], { radius: bermasalah ? 9 : 7, color: warna, fillColor: warna, weight: 2, fillOpacity: 0.9 }).addTo(map);
                    dot.bindPopup(popupHtml());
                    if (!layer) bounds.push(L.latLngBounds([[r.lat, r.lng], [r.lat, r.lng]]));
                    layer = layer || dot;
                }
            });
            if (bounds.length) {
                var b = bounds[0];
                for (var i = 1; i < bounds.length; i++) { b.extend(bounds[i]); }
                map.fitBounds(b.pad(0.2));
            }
            var objekRows = @json($objekMap);
            objekRows.forEach(function (o) {
                if (!o.objek_lat || !o.objek_lng) return;
                var pin = L.circleMarker([o.objek_lat, o.objek_lng], { radius: 9, color: '#16a34a', fillColor: '#16a34a', fillOpacity: 0.9, weight: 2 }).addTo(map);
                pin.bindTooltip(o.objek_nama || 'Objek');
                pin.bindPopup('<b>' + (o.objek_nama || 'Objek') + '</b><br>' + (o.objek_jenis || ''));
                pin.bringToFront();
            });
            } // end bootMap

            function initDashboardMap() {
                var el = document.getElementById('dashboard-map');
                if (!el || el._leaflet_id) return; // cegah double-init Leaflet
                if (typeof window.ensureLeaflet === 'function') window.ensureLeaflet(bootMap);
                else if (window.L) bootMap();
            }
            document.addEventListener('livewire:navigated', initDashboardMap);
            if (document.readyState !== 'loading') initDashboardMap();
            else document.addEventListener('DOMContentLoaded', initDashboardMap);
        })();
    </script>
</x-layouts::app>
