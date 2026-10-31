<?php /** @var App\Models\Kebun $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="6" name="kebun_nama" label="Nama Kebun" />
                <x-select col="6" name="kebun_status" label="Status" :options="$statusOptions" />
                <x-input col="4" name="kebun_luas_ha" label="Luas (ha)" type="number" />
                <x-input col="4" name="kebun_jumlah_pohon" label="Jumlah Pohon" type="number" />
                <x-input col="2" name="kebun_lat" label="Latitude" type="number" />
                <x-input col="2" name="kebun_lng" label="Longitude" type="number" />

                <div class="col-span-12">
                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                    <label class="text-sm font-medium">Marking Area Sawit — klik peta untuk tambah titik, geser titik untuk ubah bentuk</label>
                    <div id="kebun-form-map" style="height: 380px;" class="rounded-xl border border-outline-variant mt-2"></div>
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

                <x-textarea col="12" name="kebun_polygon" label="Polygon GeoJSON" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
    <script>
        (function () {
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

            var points = []; // [lat, lng]
            var markers = [];
            var polygon = L.polygon([], { weight: 3, fillOpacity: 0.45 }).addTo(map);
            var marking = true;

            function geojson() {
                var ring = points.map(function (p) { return [p[1], p[0]]; });
                if (ring.length) ring.push(ring[0].slice());
                return { type: 'Polygon', coordinates: [ring] };
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
                if (points.length < 3) {
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

            // Muat polygon lama (mode edit) atau tempelan GeoJSON
            function loadFromTextarea(fit) {
                var raw = (polyInput.value || '').trim();
                if (!raw) { points = []; redraw(); renderList(); return; }
                try {
                    var gj = JSON.parse(raw);
                    var geom = gj.type === 'Feature' ? gj.geometry : gj;
                    var ring = (geom.coordinates && geom.coordinates[0]) || [];
                    points = ring.slice(0, -1).map(function (c) { return [c[1], c[0]]; });
                    redraw(); renderList();
                    if (fit && points.length) map.fitBounds(polygon.getBounds().pad(0.2));
                } catch (e) {}
            }
            polyInput.addEventListener('change', function () { loadFromTextarea(true); });
            loadFromTextarea(!!polyInput.value.trim());
        })();
    </script>
</x-layouts::app>
