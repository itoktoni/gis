<?php /** @var App\Models\Objek $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="6" name="objek_nama" label="Nama Objek" />
                <x-select col="6" name="objek_jenis" label="Jenis" :options="$jenisOptions" />
                <x-select col="6" name="objek_id_kebun" label="Kebun" :options="$kebunOptions" />
                <x-input col="3" name="objek_lat" label="Latitude" type="number" step="any" />
                <x-input col="3" name="objek_lng" label="Longitude" type="number" step="any" />

                <div class="col-span-12">
                    <label class="text-sm font-medium">Tandai Lokasi — klik peta untuk menaruh pin objek</label>
                    <div class="mt-2 flex flex-col sm:flex-row gap-2">
                        <input type="text" id="objek-search" placeholder="Cari alamat... (cth: Pangkalan Bun)" autocomplete="off"
                            class="flex-1 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm" />
                        <button type="button" id="objek-search-btn"
                            class="rounded-lg bg-primary px-4 py-2 text-sm text-white hover:opacity-90">Cari</button>
                        <button type="button" id="objek-gps-btn"
                            class="rounded-lg border border-primary px-4 py-2 text-sm text-primary hover:bg-primary hover:text-white">📍 Lokasi Saya</button>
                    </div>
                    <div id="objek-search-results" class="mt-1 rounded-lg border border-outline-variant divide-y divide-outline-variant/60 hidden"></div>
                    <div id="objek-form-map" style="height: 320px;" class="relative z-0 rounded-xl border border-outline-variant mt-2"></div>
                    <div id="wilayah-info" class="mt-2 text-xs rounded-xl bg-surface-container px-4 py-3">
                        Klik peta untuk melihat nama wilayah (jalan, kelurahan, kecamatan, kab/kota, provinsi).
                    </div>
                </div>

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    <script>
        // Lihat layouts/head: L dimuat lazy via ensureLeaflet agar aman
        // dari "L is not defined" saat Livewire navigate.
        (function () {
            function bootMap() {
            var latInput = document.querySelector('[name="objek_lat"]');
            var lngInput = document.querySelector('[name="objek_lng"]');
            var infoEl = document.getElementById('wilayah-info');
            if (!latInput) return;

            var map = L.map('objek-form-map').setView(
                [parseFloat(latInput.value) || -2.5, parseFloat(lngInput.value) || 118],
                (latInput.value && lngInput.value) ? 14 : 5
            );
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

            var marker = null;
            function placePin(lat, lng) {
                if (marker) map.removeLayer(marker);
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', function () {
                    var ll = marker.getLatLng();
                    setCoords(ll.lat, ll.lng);
                });
            }
            function setCoords(lat, lng) {
                latInput.value = lat.toFixed(7);
                lngInput.value = lng.toFixed(7);
                placePin(lat, lng);
                lookupWilayah(lat, lng);
            }

            // shortcut: tampilkan nama wilayah dari koordinat (Nominatim OSM)
            var lastReq = 0;
            function lookupWilayah(lat, lng) {
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
                    infoEl.textContent = 'Nama wilayah tidak ditemukan (perlu internet). Koordinat tetap tersimpan.';
                });
            }

            map.on('click', function (e) { setCoords(e.latlng.lat, e.latlng.lng); });

            // Pilih kebun -> isi lat/lng dari titik tengah kebun bila masih kosong
            var kebunData = @json($kebunGeo ?? []);
            var kebunSelect = document.querySelector('[name="objek_id_kebun"]');
            if (kebunSelect) kebunSelect.addEventListener('change', function () {
                var row = kebunData[this.value];
                if (!row || row.lat === null || row.lng === null) return;
                if ((latInput && latInput.value) || (lngInput && lngInput.value)) return;
                setCoords(parseFloat(row.lat), parseFloat(row.lng));
            });

            // --- Pencarian alamat (Nominatim OSM, batasi Indonesia) ---
            var searchInput = document.getElementById('objek-search');
            var searchBtn = document.getElementById('objek-search-btn');
            var resultsEl = document.getElementById('objek-search-results');
            function runSearch() {
                var q = (searchInput.value || '').trim();
                if (q.length < 3) { resultsEl.classList.add('hidden'); return; }
                resultsEl.innerHTML = '<div class="px-3 py-2 text-sm text-on-surface-variant">Mencari...</div>';
                resultsEl.classList.remove('hidden');
                fetch('https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(q) + '&format=json&addressdetails=1&countrycodes=id&limit=5', {
                    headers: { 'Accept': 'application/json' }
                }).then(function (r) { return r.json(); }).then(function (list) {
                    if (!list.length) {
                        resultsEl.innerHTML = '<div class="px-3 py-2 text-sm text-on-surface-variant">Tidak ditemukan. Coba kata kunci lain.</div>';
                        return;
                    }
                    resultsEl.innerHTML = '';
                    list.forEach(function (item) {
                        var b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'w-full text-left px-3 py-2 text-sm hover:bg-surface-container';
                        b.textContent = item.display_name;
                        b.addEventListener('click', function () {
                            var lat = parseFloat(item.lat), lng = parseFloat(item.lon);
                            map.setView([lat, lng], 16);
                            setCoords(lat, lng);
                            resultsEl.classList.add('hidden');
                        });
                        resultsEl.appendChild(b);
                    });
                }).catch(function () {
                    resultsEl.innerHTML = '<div class="px-3 py-2 text-sm text-on-surface-variant">Gagal mencari (perlu internet).</div>';
                });
            }
            searchBtn.addEventListener('click', runSearch);
            searchInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); runSearch(); } });

            // --- Lokasi saya (GPS browser) ---
            var gpsBtn = document.getElementById('objek-gps-btn');
            gpsBtn.addEventListener('click', function () {
                if (!navigator.geolocation) { infoEl.textContent = 'Browser tidak mendukung GPS.'; return; }
                gpsBtn.disabled = true;
                gpsBtn.textContent = 'Mencari GPS...';
                navigator.geolocation.getCurrentPosition(function (pos) {
                    gpsBtn.disabled = false;
                    gpsBtn.textContent = '📍 Lokasi Saya';
                    map.setView([pos.coords.latitude, pos.coords.longitude], 16);
                    setCoords(pos.coords.latitude, pos.coords.longitude);
                }, function (err) {
                    gpsBtn.disabled = false;
                    gpsBtn.textContent = '📍 Lokasi Saya';
                    infoEl.textContent = 'Gagal dapat GPS: ' + (err.message || 'izin lokasi ditolak') + '. Nyalakan izin lokasi browser lalu coba lagi.';
                }, { enableHighAccuracy: true, timeout: 15000 });
            });

            if (latInput.value && lngInput.value) {
                placePin(parseFloat(latInput.value), parseFloat(lngInput.value));
                map.setView([parseFloat(latInput.value), parseFloat(lngInput.value)], 14);
            }
            } // end bootMap

            function initObjekFormMap() {
                var el = document.getElementById('objek-form-map');
                if (!el || el._leaflet_id) return; // cegah double-init Leaflet
                if (typeof window.ensureLeaflet === 'function') window.ensureLeaflet(bootMap);
                else if (window.L) bootMap();
            }
            document.addEventListener('livewire:navigated', initObjekFormMap);
            if (document.readyState !== 'loading') initObjekFormMap();
            else document.addEventListener('DOMContentLoaded', initObjekFormMap);
        })();
    </script>
</x-layouts::app>
