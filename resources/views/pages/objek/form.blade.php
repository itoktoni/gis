<?php /** @var App\Models\Objek $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="6" name="objek_nama" label="Nama Objek" />
                <x-select col="6" name="objek_jenis" label="Jenis" :options="$jenisOptions" />
                <x-select col="6" name="objek_id_kebun" label="Kebun" :options="$kebunOptions" />
                <x-input col="3" name="objek_lat" label="Latitude" type="number" />
                <x-input col="3" name="objek_lng" label="Longitude" type="number" />

                <div class="col-span-12">
                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                    <label class="text-sm font-medium">Tandai Lokasi — klik peta untuk menaruh pin objek</label>
                    <div id="objek-form-map" style="height: 320px;" class="rounded-xl border border-outline-variant mt-2"></div>
                    <div id="wilayah-info" class="mt-2 text-xs rounded-xl bg-surface-container px-4 py-3">
                        Klik peta untuk melihat nama wilayah (jalan, kelurahan, kecamatan, kab/kota, provinsi).
                    </div>
                </div>

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
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
            if (latInput.value && lngInput.value) {
                placePin(parseFloat(latInput.value), parseFloat(lngInput.value));
                map.setView([parseFloat(latInput.value), parseFloat(lngInput.value)], 14);
            }
        })();
    </script>
</x-layouts::app>
