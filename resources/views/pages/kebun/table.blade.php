<?php /** @var App\Models\Kebun $table */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => moduleLabel()]]" />
    <div class="content mt-4 lg:mt-0">
        {{-- Filters --}}
        <x-filter :per-page="25" :fields="$fields">
            <x-slot:advanced>
                @foreach ($fields as $key => $advance)
                <x-filter-item :label="$advance" :name="$key"/>
                @endforeach

                <x-button variant="primary" class="btn-block" onclick="applyAdvanced()">Apply</x-button>
                <x-button variant="soft" class="btn-block" onclick="resetAdvanced()">Reset</x-button>
            </x-slot:advanced>
        </x-filter>

        {{-- Aggregate GIS map --}}
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <x-card label="Peta Area Sawit" :noGrid="true">
            <div class="flex flex-col sm:flex-row gap-3 mb-3">
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Total Luas (halaman ini)</p>
                    <p class="text-lg font-bold">{{ number_format($data->sum('kebun_luas_ha'), 2, ',', '.') }} ha</p>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Total Pohon (halaman ini)</p>
                    <p class="text-lg font-bold">{{ number_format($data->sum('kebun_jumlah_pohon'), 0, ',', '.') }} pohon</p>
                </div>
            </div>
            <div id="kebun-map" style="height: 380px;" class="rounded-xl border border-outline-variant"></div>
            <div class="flex flex-wrap gap-4 mt-2 text-xs text-on-surface-variant">
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full" style="background:#3388ff"></span> Blok kebun (area)</span>
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full" style="background:#e11d48"></span> Objek / POS</span>
            </div>
        </x-card>

        {{-- Table --}}
        @php
            $currentSort = request('sort.0', '');
            $sortField = str_replace(':desc','',str_replace(':asc','',$currentSort));
            $sortDir = str_contains($currentSort, ':desc') ? 'desc' : 'asc';
        @endphp

        <x-table>
            <x-slot:head>
                <x-table-checkbox :model="$model" onchange="toggleAll(this)" />
                <th>Actions</th>
                @foreach ($model::$sortColumns as $column)
                <x-table-sort field="{{ $column }}" label="{{ formatLabel($column) }}" :sortField="$sortField" :sortDir="$sortDir" />
                @endforeach
            </x-slot:head>

            <x-slot:body>
                @foreach($data as $table)
                <tr data-kebun="{{ $table->field_primary }}" title="Klik untuk lihat di peta">
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary" />
                    @foreach ($model::$sortColumns as $column)
                    <td>{{ $table->$column }}</td>
                    @endforeach
                </tr>
                @endforeach
            </x-slot:body>

            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @foreach($data as $table)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm" data-id="{{ $table->field_primary }}">
                        <p class="text-sm font-bold text-on-surface truncate mb-3">{{ $table->kebun_nama }}</p>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Luas</p>
                                <p class="text-xs font-medium text-primary truncate">{{ $table->kebun_luas_ha ?? '-' }} ha</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Pohon</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->kebun_jumlah_pohon ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Status</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->kebun_status ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Koordinat</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->kebun_lat ?? '-' }}, {{ $table->kebun_lng ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
                            <div class="flex gap-1" onclick="event.stopPropagation()">
                                <x-table-action :model="$model" :id="$table->field_primary" />
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-slot:mobile>

        </x-table>

        <x-pagination :paginator="$data" />
        <x-action :model="$model" :action="['create', 'delete']"/>

    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
    @php
        $mapRows = [];
        foreach ($data as $row) {
            $mapRows[] = [
                'id' => $row->kebun_id,
                'nama' => $row->kebun_nama,
                'luas' => $row->kebun_luas_ha,
                'pohon' => $row->kebun_jumlah_pohon,
                'lat' => $row->kebun_lat,
                'lng' => $row->kebun_lng,
                'polygon' => $row->kebun_polygon,
            ];
        }
    @endphp
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            var rows = @json($mapRows);
            var map = L.map('kebun-map').setView([-2.5, 118], 5);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
            var bounds = [];
            var layersById = {};
            rows.forEach(function (r) {
                var layer = null;
                if (r.polygon) {
                    try {
                        var gj = JSON.parse(r.polygon);
                        var geo = gj.type === 'Feature' ? gj : { type: 'Feature', properties: {}, geometry: gj };
                        layer = L.geoJSON(geo, { style: { weight: 3, fillOpacity: 0.45 } }).addTo(map);
                        layer.bindPopup('<b>' + (r.nama || '') + '</b><br>' + (r.luas || '-') + ' ha • ' + (r.pohon || 0) + ' pohon');
                        layer.eachLayer(function (l) { bounds.push(l.getBounds()); });
                    } catch (e) { layer = null; }
                }
                if (r.lat && r.lng) {
                    var dot = L.circleMarker([r.lat, r.lng], { radius: 7, weight: 2, fillOpacity: 0.9 }).addTo(map);
                    dot.bindPopup('<b>' + (r.nama || '') + '</b><br>' + (r.luas || '-') + ' ha • ' + (r.pohon || 0) + ' pohon');
                    if (!layer) bounds.push(L.latLngBounds([[r.lat, r.lng], [r.lat, r.lng]]));
                    layer = layer || dot;
                }
                if (layer) layersById[String(r.id)] = layer;
            });
            if (bounds.length) {
                var b = bounds[0];
                for (var i = 1; i < bounds.length; i++) { b.extend(bounds[i]); }
                map.fitBounds(b.pad(0.2));
            }
            @php
                $objekRows = [];
                foreach ($objekMap ?? [] as $o) {
                    $objekRows[] = ['nama' => $o->objek_nama, 'jenis' => $o->objek_jenis, 'lat' => $o->objek_lat, 'lng' => $o->objek_lng];
                }
            @endphp
            var objekRows = @json($objekRows);
            objekRows.forEach(function (o) {
                if (!o.lat || !o.lng) return;
                var pin = L.circleMarker([o.lat, o.lng], { radius: 9, color: '#e11d48', fillColor: '#e11d48', fillOpacity: 0.9, weight: 2 }).addTo(map);
                pin.bindTooltip(o.nama || 'Objek');
                pin.bindPopup('<b>' + (o.nama || 'Objek') + '</b><br>' + (o.jenis || ''));
                pin.bringToFront();
            });
            function focusKebun(id) {
                var layer = layersById[String(id)];
                if (!layer) return;
                var lb = layer.getBounds ? layer.getBounds() : null;
                if (lb && lb.isValid()) {
                    map.flyToBounds(lb.pad(0.5), { maxZoom: 15 });
                } else if (layer.getLatLng) {
                    map.flyTo(layer.getLatLng(), 15);
                }
                if (layer.openPopup) setTimeout(function () { layer.openPopup(); }, 600);
                document.getElementById('kebun-map').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            document.querySelectorAll('tr[data-kebun]').forEach(function (tr) {
                tr.style.cursor = 'pointer';
                tr.addEventListener('click', function (ev) {
                    if (ev.target.closest('a, button, input')) return;
                    focusKebun(tr.getAttribute('data-kebun'));
                });
            });
            document.querySelectorAll('#mBody [data-id]').forEach(function (card) {
                card.style.cursor = 'pointer';
                card.addEventListener('click', function (ev) {
                    if (ev.target.closest('a, button, input')) return;
                    focusKebun(card.getAttribute('data-id'));
                });
            });
        })();
    </script>
</x-layouts::app>
