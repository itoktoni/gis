<?php /** @var App\Models\Kebun $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="6" name="kebun_nama" label="Nama Kebun" />
                <x-select col="3" name="kebun_id_pt" label="PT / Grup" :options="$ptOptions" class="search" />
                <x-select col="3" name="kebun_id_distrik" label="Distrik" :options="$distrikOptions" class="search" />
                <x-select col="6" name="kebun_status" label="Status" :options="$statusOptions" />
                <x-input col="4" name="kebun_luas_ha" label="Luas TM Planted (Ha)" type="number" step="any" />
                <x-input col="4" name="kebun_target_produksi_kg" label="Target Produksi (Kg)" type="number" step="any" />
                <x-input col="4" name="kebun_rkap_kg" label="Produksi TBS RKAP (Kg)" type="number" step="any" />
                <x-input col="4" name="kebun_jumlah_pohon" label="Jumlah Pohon" type="number" step="any" />
                <x-input col="2" name="kebun_lat" label="Latitude" type="number" step="any" />
                <x-input col="2" name="kebun_lng" label="Longitude" type="number" step="any" />

                <div class="col-span-12">
                    <label class="text-sm font-medium">Marking Area Sawit — klik peta untuk tambah titik, geser titik untuk ubah bentuk</label>
                    <div class="mt-2 flex flex-col sm:flex-row gap-2">
                        <input type="text" id="kebun-search" placeholder="Cari alamat... (cth: Pangkalan Bun)" autocomplete="off"
                            class="flex-1 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm" />
                        <button type="button" id="kebun-search-btn"
                            class="rounded-lg bg-primary px-4 py-2 text-sm text-white hover:opacity-90">Cari</button>
                        <button type="button" id="kebun-gps-btn"
                            class="rounded-lg border border-primary px-4 py-2 text-sm text-primary hover:bg-primary hover:text-white">📍 Lokasi Saya</button>
                    </div>
                    <div id="kebun-search-results" class="mt-1 rounded-lg border border-outline-variant divide-y divide-outline-variant/60 hidden"></div>
                    <div id="kebun-form-map" style="height: 380px;" class="relative z-0 rounded-xl border border-outline-variant mt-2"></div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <button type="button" id="btn-mark-mode" class="btn">Mode tambah: ON</button>
                        <button type="button" id="btn-undo-point" class="btn">Hapus titik terakhir</button>
                        <button type="button" id="btn-clear-points" class="btn">Hapus semua</button>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-1">Minimal 3 titik membentuk area. Luas + titik tengah terisi otomatis.</p>
                    <div id="wilayah-info" class="mt-2 text-xs rounded-xl bg-surface-container px-4 py-3">
                        Klik peta untuk melihat nama wilayah (jalan, kelurahan, kecamatan, kab/kota, provinsi).
                    </div>
                    <div id="titik-list" class="mt-2 space-y-1 text-xs"></div>
                </div>

                <x-textarea col="12" name="kebun_polygon" label="Polygon GeoJSON"
                    helper="Tempel hasil export QGIS (GeoJSON, 1 atau banyak area) atau upload file .geojson. Area banyak otomatis tersimpan sebagai MultiPolygon." />

                <div class="col-span-12">
                    <input type="file" id="kebun_polygon_file" accept=".geojson,.json" class="text-xs" />
                </div>

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    @if(isset($model) && $model->exists)
    @php $bloks = $model->hasBlok()->orderBy('blok_kode')->get(); @endphp
    <x-card label="Daftar Blok Sawit ({{ $bloks->count() }})" :noGrid="true">
        @if($bloks->isEmpty())
        <p class="text-sm text-on-surface-variant">Belum ada blok. <a href="{{ route('blok.getCreate') }}" class="text-primary hover:underline font-semibold">Tambah blok pertama</a>.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-on-surface-variant border-b border-outline-variant">
                        <th class="py-2 pr-3">Kode</th>
                        <th class="py-2 pr-3">Nama Blok</th>
                        <th class="py-2 pr-3 text-right">Luas (Ha)</th>
                        <th class="py-2 pr-3 text-right">Pohon</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bloks as $b)
                    <tr class="border-b border-outline-variant/50">
                        <td class="py-2 pr-3 font-bold text-primary">{{ $b->blok_kode }}</td>
                        <td class="py-2 pr-3">{{ $b->blok_nama }}</td>
                        <td class="py-2 pr-3 text-right">{{ $b->blok_luas_ha ?? '-' }}</td>
                        <td class="py-2 pr-3 text-right">{{ number_format($b->blok_jumlah_pohon) }}</td>
                        <td class="py-2 text-right"><a href="{{ route('blok.getUpdate', ['id' => $b->blok_id]) }}" class="text-primary hover:underline text-xs font-semibold">Ubah</a></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold">
                        <td class="py-2 pr-3" colspan="2">Total ({{ $bloks->count() }} blok)</td>
                        <td class="py-2 pr-3 text-right">{{ $bloks->sum('blok_luas_ha') }}</td>
                        <td class="py-2 pr-3 text-right">{{ number_format($bloks->sum('blok_jumlah_pohon')) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
        <div class="mt-3 flex gap-2">
            <a href="{{ route('blok.getCreate') }}" class="inline-flex items-center gap-1 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">+ Tambah Blok</a>
            <a href="{{ route('blok.getTable') }}" class="inline-flex items-center gap-1 rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold">Kelola Semua Blok</a>
        </div>
    </x-card>
    @endif

    <script>
        // Lihat layouts/head: L/turf dimuat lazy via ensureLeaflet/ensureTurf
        // agar aman dari "L is not defined" saat Livewire navigate.
        (function () {
            function bootMap() {
            var polyInput = document.querySelector('[name="kebun_polygon"]');
            var latInput = document.querySelector('[name="kebun_lat"]');
            var lngInput = document.querySelector('[name="kebun_lng"]');
            var luasInput = document.querySelector('[name="kebun_luas_ha"]');
            var listEl = document.getElementById('titik-list');
            var modeBtn = document.getElementById('btn-mark-mode');
            var infoEl = document.getElementById('wilayah-info');
            if (!polyInput) return;

            var map = L.map('kebun-form-map').setView(
                [parseFloat(latInput?.value) || -2.5, parseFloat(lngInput?.value) || 118],
                (latInput?.value && lngInput?.value) ? 13 : 5
            );
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

            var points = []; // [lat, lng] — ring yang sedang diedit
            var markers = [];
            var polygon = L.polygon([], { weight: 3, fillOpacity: 0.45 }).addTo(map);
            var otherRings = []; // ring [lng,lat] lain (read-only, dari MultiPolygon)
            var otherLayer = L.geoJSON(null, { style: { color: '#9333ea', weight: 2, fillColor: '#9333ea', fillOpacity: 0.25 } }).addTo(map);
            var marking = true;

            function currentGeometry() {
                var ring = points.map(function (p) { return [p[1], p[0]]; });
                if (ring.length) ring.push(ring[0].slice());
                var all = ring.length ? [ring].concat(otherRings) : otherRings.slice();
                if (all.length > 1) return { type: 'MultiPolygon', coordinates: all.map(function (r) { return [r]; }) };
                return { type: 'Polygon', coordinates: all.length ? [all[0]] : [] };
            }

            function geojson() { return currentGeometry(); }

            function redrawOthers() {
                otherLayer.clearLayers();
                if (!otherRings.length) return;
                otherLayer.addData({ type: 'Feature', properties: {}, geometry: { type: 'MultiPolygon', coordinates: otherRings.map(function (r) { return [r]; }) } });
            }

            function redraw() {
                markers.forEach(function (m) { map.removeLayer(m); });
                markers = points.map(function (p, i) {
                    var m = L.marker(p, { draggable: true }).addTo(map);
                    m.on('dragend', function () {
                        var ll = m.getLatLng();
                        points[i] = [ll.lat, ll.lng];
                        redraw();
                    });
                    return m;
                });
                polygon.setLatLngs(points.length >= 3 ? [points] : []);
            }

            function sync() {
                redraw();
                redrawOthers();
                if (points.length < 3 && !otherRings.length) {
                    if (points.length === 0) polyInput.value = '';
                    renderList();
                    return;
                }
                var gj = geojson();
                polyInput.value = JSON.stringify(gj);
                try {
                    var feat = { type: 'Feature', properties: {}, geometry: gj };
                    var c = turf.centroid(feat);
                    if (latInput) latInput.value = c.geometry.coordinates[1].toFixed(7);
                    if (lngInput) lngInput.value = c.geometry.coordinates[0].toFixed(7);
                    if (luasInput) luasInput.value = (turf.area(feat) / 10000).toFixed(2);
                } catch (e) {}
                renderList();
            }

            function renderList() {
                if (!listEl) return;
                if (!points.length) { listEl.innerHTML = '<p class="text-on-surface-variant">Belum ada titik — klik peta.</p>'; return; }
                listEl.innerHTML = points.map(function (p, i) {
                    return '<div class="flex items-center gap-2">'
                        + '<span class="font-mono">#' + (i + 1) + ' ' + p[0].toFixed(5) + ', ' + p[1].toFixed(5) + '</span>'
                        + '<button type="button" data-i="' + i + '" class="btn-del-titik underline">hapus</button>'
                        + '</div>';
                }).join('');
                listEl.querySelectorAll('.btn-del-titik').forEach(function (b) {
                    b.addEventListener('click', function () {
                        points.splice(parseInt(b.getAttribute('data-i'), 10), 1);
                        sync();
                    });
                });
            }

            map.on('click', function (e) {
                if (!marking) return;
                points.push([e.latlng.lat, e.latlng.lng]);
                sync();
                lookupWilayah(e.latlng.lat, e.latlng.lng);
            });

            // shortcut: tampilkan nama wilayah dari koordinat (Nominatim OSM)
            var lastReq = 0;
            function lookupWilayah(lat, lng) {
                if (!infoEl) return;
                var req = ++lastReq;
                infoEl.textContent = 'Mencari nama wilayah...';
                fetch('https://nominatim.openstreetmap.org/reverse?lat=' + lat + '&lon=' + lng + '&format=json&addressdetails=1', {
                    headers: { 'Accept': 'application/json' }
                }).then(function (r) { return r.json(); }).then(function (j) {
                    if (req !== lastReq) return;
                    var a = j.address || {};
                    var jalan = a.road || '-';
                    var kelurahan = a.village || a.suburb || a.neighbourhood || a.hamlet || '-';
                    var kecamatan = a.city_district || a.suburb || a.town || '-';
                    var kabkota = a.city || a.county || a.regency || '-';
                    var provinsi = a.state || '-';
                    infoEl.innerHTML = '<b>Wilayah:</b> ' + jalan + ' • Kel. ' + kelurahan + ' • Kec. ' + kecamatan + ' • ' + kabkota + ' • ' + provinsi;
                }).catch(function () {
                    if (req !== lastReq) return;
                    infoEl.textContent = 'Nama wilayah tidak ditemukan (perlu internet).';
                });
            }

            modeBtn.addEventListener('click', function () {
                marking = !marking;
                modeBtn.textContent = 'Mode tambah: ' + (marking ? 'ON' : 'OFF');
            });
            document.getElementById('btn-undo-point').addEventListener('click', function () { points.pop(); sync(); });
            document.getElementById('btn-clear-points').addEventListener('click', function () { points = []; sync(); });

            // --- Pencarian alamat (Nominatim OSM, batasi Indonesia): geser peta ke hasil ---
            var kSearchInput = document.getElementById('kebun-search');
            var kSearchBtn = document.getElementById('kebun-search-btn');
            var kResultsEl = document.getElementById('kebun-search-results');
            function runKebunSearch() {
                var q = (kSearchInput.value || '').trim();
                if (q.length < 3) { kResultsEl.classList.add('hidden'); return; }
                kResultsEl.innerHTML = '<div class="px-3 py-2 text-sm text-on-surface-variant">Mencari...</div>';
                kResultsEl.classList.remove('hidden');
                fetch('https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(q) + '&format=json&addressdetails=1&countrycodes=id&limit=5', {
                    headers: { 'Accept': 'application/json' }
                }).then(function (r) { return r.json(); }).then(function (list) {
                    if (!list.length) {
                        kResultsEl.innerHTML = '<div class="px-3 py-2 text-sm text-on-surface-variant">Tidak ditemukan. Coba kata kunci lain.</div>';
                        return;
                    }
                    kResultsEl.innerHTML = '';
                    list.forEach(function (item) {
                        var b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'w-full text-left px-3 py-2 text-sm hover:bg-surface-container';
                        b.textContent = item.display_name;
                        b.addEventListener('click', function () {
                            map.setView([parseFloat(item.lat), parseFloat(item.lon)], 15);
                            lookupWilayah(parseFloat(item.lat), parseFloat(item.lon));
                            kResultsEl.classList.add('hidden');
                        });
                        kResultsEl.appendChild(b);
                    });
                }).catch(function () {
                    kResultsEl.innerHTML = '<div class="px-3 py-2 text-sm text-on-surface-variant">Gagal mencari (perlu internet).</div>';
                });
            }
            kSearchBtn.addEventListener('click', runKebunSearch);
            kSearchInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); runKebunSearch(); } });

            // --- Lokasi saya (GPS browser): geser peta agar mudah marking di sekitar posisi ---
            var kGpsBtn = document.getElementById('kebun-gps-btn');
            kGpsBtn.addEventListener('click', function () {
                if (!navigator.geolocation) { if (infoEl) infoEl.textContent = 'Browser tidak mendukung GPS.'; return; }
                kGpsBtn.disabled = true;
                kGpsBtn.textContent = 'Mencari GPS...';
                navigator.geolocation.getCurrentPosition(function (pos) {
                    kGpsBtn.disabled = false;
                    kGpsBtn.textContent = '📍 Lokasi Saya';
                    map.setView([pos.coords.latitude, pos.coords.longitude], 16);
                    lookupWilayah(pos.coords.latitude, pos.coords.longitude);
                }, function (err) {
                    kGpsBtn.disabled = false;
                    kGpsBtn.textContent = '📍 Lokasi Saya';
                    if (infoEl) infoEl.textContent = 'Gagal dapat GPS: ' + (err.message || 'izin lokasi ditolak') + '. Nyalakan izin lokasi browser lalu coba lagi.';
                }, { enableHighAccuracy: true, timeout: 15000 });
            });

            // Muat polygon lama (mode edit) atau tempelan GeoJSON.
            // MultiPolygon (export QGIS banyak area): ring terbesar bisa diedit,
            // sisanya tampil read-only dan ikut tersimpan saat Save.
            function ringsOf(gj) {
                var geom = gj.type === 'Feature' ? gj.geometry : gj;
                if (!geom) return [];
                if (geom.type === 'Polygon') return [geom.coordinates[0] || []];
                if (geom.type === 'MultiPolygon') {
                    return (geom.coordinates || []).map(function (p) { return p[0] || []; });
                }
                if (gj.type === 'FeatureCollection') {
                    var out = [];
                    (gj.features || []).forEach(function (f) {
                        ringsOf(f).forEach(function (r) { out.push(r); });
                    });
                    return out;
                }
                return [];
            }
            function loadFromTextarea(fit) {
                var raw = (polyInput.value || '').trim();
                if (!raw) { points = []; otherRings = []; redraw(); redrawOthers(); renderList(); return; }
                try {
                    var gj = JSON.parse(raw);
                    var rings = ringsOf(gj).filter(function (r) { return r.length >= 3; });
                    rings.sort(function (a, b) { return b.length - a.length; });
                    var first = rings.shift() || [];
                    points = first.slice(0, -1).map(function (c) { return [c[1], c[0]]; });
                    otherRings = rings;
                    redraw(); redrawOthers(); renderList();
                    if (fit) {
                        var b = null;
                        try { b = polygon.getBounds().extend(otherLayer.getBounds()); } catch (e) { b = null; }
                        if (b && b.isValid && b.isValid()) map.fitBounds(b.pad(0.2));
                        else if (points.length) map.fitBounds(polygon.getBounds().pad(0.2));
                    }
                } catch (e) {}
            }
            polyInput.addEventListener('change', function () { loadFromTextarea(true); });
            // File .geojson (export QGIS) -> tempel ke textarea.
            var kebunPolyFile = document.getElementById('kebun_polygon_file');
            if (kebunPolyFile) kebunPolyFile.addEventListener('change', function () {
                var f = this.files && this.files[0];
                if (!f) return;
                var r = new FileReader();
                r.onload = function () { polyInput.value = r.result; loadFromTextarea(true); };
                r.readAsText(f);
            });
            loadFromTextarea(!!polyInput.value.trim());
            } // end bootMap

            function initKebunFormMap() {
                var el = document.getElementById('kebun-form-map');
                if (!el || el._leaflet_id) return; // cegah double-init Leaflet
                if (typeof turf === 'undefined' && typeof window.ensureTurf === 'function') {
                    window.ensureTurf(function () {
                        if (typeof window.ensureLeaflet === 'function') window.ensureLeaflet(bootMap);
                        else if (window.L) bootMap();
                    });
                    return;
                }
                if (typeof window.ensureLeaflet === 'function') window.ensureLeaflet(bootMap);
                else if (window.L) bootMap();
            }
            document.addEventListener('livewire:navigated', initKebunFormMap);
            if (document.readyState !== 'loading') initKebunFormMap();
            else document.addEventListener('DOMContentLoaded', initKebunFormMap);
        })();
    </script>
</x-layouts::app>
