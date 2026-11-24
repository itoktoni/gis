<?php /** @var App\Models\Checkin $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => 'Detail Check-In']]" />

    @php
        $isValid = strtolower($model->status ?? '') === 'valid';
        $isInvalid = strtolower($model->status ?? '') === 'invalid';
        $statusLabel = $isValid ? 'VALID' : ($isInvalid ? 'DI LUAR JANGKAUAN' : 'PALSU');
        $reasons = $model->failure_reason ? array_values(array_filter(array_map('trim', explode(';', $model->failure_reason)))) : [];
        $payload = is_string($model->payload_json) ? json_decode($model->payload_json, true) : (is_array($model->payload_json) ? $model->payload_json : []);
        $payload = is_array($payload) ? $payload : [];
        $photoUrl = $model->photo_path ? fileUrl($model->photo_path) : '';

        $haversine = function ($lat1, $lng1, $lat2, $lng2) {
            if (!is_numeric($lat1) || !is_numeric($lng1) || !is_numeric($lat2) || !is_numeric($lng2)) return null;
            $r = 6371000;
            $dLat = deg2rad($lat2 - $lat1); $dLng = deg2rad($lng2 - $lng1);
            return 2 * $r * asin(sqrt(sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2));
        };
        $netDist = $haversine($model->latitude, $model->longitude, $payload['net_lat'] ?? null, $payload['net_lng'] ?? null);
        $origDist = $haversine($model->latitude, $model->longitude, $model->original_latitude, $model->original_longitude);

        // Perbandingan semua kandidat sumber lokasi (dari payload HP)
        $mkCand = function ($label, $lat, $lng, $acc = null) use ($haversine, $model) {
            if (!is_numeric($lat) || !is_numeric($lng)) return null;
            $d = $haversine($model->latitude, $model->longitude, $lat, $lng);
            return ['label' => $label, 'lat' => (float) $lat, 'lng' => (float) $lng,
                'acc' => is_numeric($acc) ? (float) $acc : null, 'dist' => $d,
                'url' => sprintf('https://www.google.com/maps?q=%.7f,%.7f', $lat, $lng)];
        };
        $candidates = array_values(array_filter([
            $mkCand('Check-in (klaim HP)', $model->latitude, $model->longitude, $model->accuracy),
            $mkCand('GPS mentah', $payload['gps_raw_lat'] ?? null, $payload['gps_raw_lng'] ?? null, $payload['gps_raw_acc'] ?? null),
            $mkCand('Jaringan (menara/WiFi)', $payload['net_lat'] ?? null, $payload['net_lng'] ?? null, $payload['net_acc'] ?? null),
            $mkCand('IP publik', $payload['ip_lat'] ?? null, $payload['ip_lng'] ?? null, null),
        ]));

        $signals = [
            ['label' => 'Flag mock HP', 'value' => !empty($payload['mock']) ? 'YA — lokasi dari mock provider' : 'Tidak', 'bad' => !empty($payload['mock'])],
            ['label' => 'Perangkat root', 'value' => !empty($payload['rooted']) ? 'YA — berisiko fake GPS sistem' : 'Tidak', 'bad' => !empty($payload['rooted'])],
            ['label' => 'Framework Xposed', 'value' => !empty($payload['xposed']) ? 'YA — berisiko bypass deteksi mock' : 'Tidak', 'bad' => !empty($payload['xposed'])],
            ['label' => 'Developer options', 'value' => !empty($payload['dev_mode']) ? 'Aktif saat check-in' : 'Mati', 'bad' => !empty($payload['dev_mode'])],
            ['label' => 'Lokasi jaringan vs GPS', 'value' => $netDist === null ? 'Tidak ada data jaringan' : sprintf('selisih %s', $netDist >= 1000 ? number_format($netDist / 1000, 1).' km' : round($netDist).' m'), 'bad' => $netDist !== null && $netDist > 50000],
            ['label' => 'Satelit GNSS terkunci', 'value' => isset($payload['sat_used_in_fix']) ? $payload['sat_used_in_fix'].' satelit (terlihat: '.($payload['sat_count'] ?? '?').')' : 'Tidak ada data GNSS', 'bad' => isset($payload['accuracy'], $payload['sat_used_in_fix']) && (float) $payload['accuracy'] < 20 && (int) $payload['sat_used_in_fix'] === 0],
            ['label' => 'Umur fix lokasi', 'value' => isset($payload['loc_age_ms']) ? (int) $payload['loc_age_ms'].' ms' : 'Tidak ada data', 'bad' => isset($payload['loc_age_ms']) && ((int) $payload['loc_age_ms'] < 0 || (int) $payload['loc_age_ms'] > 60000)],
            ['label' => 'Sensor gerak', 'value' => !isset($payload['motion_still']) ? 'Tidak ada data sensor' : (!empty($payload['motion_still']) ? 'Diam (variance '.round((float) ($payload['motion_accel_var'] ?? 0), 3).')' : 'Bergerak'), 'bad' => false],
            ['label' => 'Mode kirim', 'value' => $model->is_offline ? 'Offline (disimpan di HP dulu)' : 'Online', 'bad' => false],
        ];
    @endphp

    {{-- Status hero --}}
    <div class="mt-4 lg:mt-0 rounded-xl border p-5 flex flex-wrap items-center gap-4 {{ $isValid ? 'bg-green-50 border-green-200' : ($isInvalid ? 'bg-amber-50 border-amber-200' : 'bg-red-50 border-red-200') }}">
        <span class="material-symbols-outlined text-4xl {{ $isValid ? 'text-green-600' : ($isInvalid ? 'text-amber-600' : 'text-red-600') }}">{{ $isValid ? 'verified' : ($isInvalid ? 'location_off' : 'gpp_bad') }}</span>
        <div class="flex-1 min-w-52">
            <div class="text-lg font-bold {{ $isValid ? 'text-green-800' : ($isInvalid ? 'text-amber-800' : 'text-red-800') }}">{{ $model->kunjungan_id ? 'Kunjungan' : 'Check-in' }} {{ $statusLabel }}</div>
            <div class="text-sm text-on-surface-variant">{{ $model->hasUser?->name ?? '-' }} &middot; {{ $model->created_at?->format('d M Y H:i:s') ?? '-' }}</div>
        </div>
        <a href="{{ moduleRoute('getTable') }}" class="text-sm px-4 py-2 rounded-lg border border-outline-variant bg-surface-container-lowest hover:underline">Kembali</a>
    </div>

    @if(!$isValid && count($reasons))
    <div class="mt-4 rounded-xl border {{ $isInvalid ? 'border-amber-200' : 'border-red-200' }} bg-surface-container-lowest p-5">
        <div class="font-semibold text-sm {{ $isInvalid ? 'text-amber-700' : 'text-red-700' }} mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-lg">warning</span>Alasan ditandai {{ $statusLabel }} ({{ count($reasons) }})</div>
        <ul class="list-disc ml-5 space-y-1 text-sm text-on-surface">
            @foreach($reasons as $r)
            <li>{{ formatJarakText($r) }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Peta full di paling atas --}}
    <x-card :label="'Peta Lokasi'" :icon="'map'">
        <div class="col-span-12">
            <div id="checkin-map" style="height: 420px; border-radius: 12px;" class="border border-outline-variant"></div>
            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-on-surface-variant">
                <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-600 align-middle"></span> Titik check-in</span>
                @if($kunjungan && $kunjungan->kunjungan_lat && $kunjungan->kunjungan_lng)
                <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-green-600 align-middle"></span> Titik kunjungan + radius</span>
                @endif
                @if($model->original_latitude && $model->original_longitude)
                <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-600 align-middle"></span> Dugaan lokasi asli</span>
                @endif
            </div>
        </div>
    </x-card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
        <div>
            <x-card :label="'Foto Check-In'" :icon="'photo_camera'">
                @if($photoUrl)
                <div class="col-span-12">
                    <a href="{{ $photoUrl }}" target="_blank"><img src="{{ $photoUrl }}" class="w-full rounded-xl border border-outline-variant" alt="foto check-in" loading="lazy" /></a>
                    <div class="mt-2 flex gap-3 text-sm">
                        <a href="{{ $photoUrl }}" target="_blank" class="text-primary hover:underline">Buka ukuran penuh</a>
                        <a href="{{ $photoUrl }}" download class="text-primary hover:underline">Unduh foto</a>
                    </div>
                </div>
                @else
                <p class="col-span-12 text-sm text-on-surface-variant">Tidak ada foto.</p>
                @endif
            </x-card>
        </div>

        <div>
            <x-card :label="'Ringkasan'" :icon="'person'">
                <dl class="col-span-12 grid grid-cols-3 gap-y-3 text-sm">
                    <dt class="text-on-surface-variant">Karyawan</dt><dd class="col-span-2 font-medium">{{ $model->hasUser?->name ?? '-' }} <span class="text-on-surface-variant">({{ $model->hasUser?->email ?? '-' }})</span></dd>
                    <dt class="text-on-surface-variant">Waktu server</dt><dd class="col-span-2">{{ $model->created_at?->format('d M Y H:i:s') ?? '-' }}</dd>
                    <dt class="text-on-surface-variant">Waktu HP</dt><dd class="col-span-2">{{ $model->client_timestamp?->format('d M Y H:i:s') ?? '-' }}</dd>
                    <dt class="text-on-surface-variant">Koordinat</dt><dd class="col-span-2 font-mono text-[13px]">{{ $model->latitude }}, {{ $model->longitude }} <span class="text-on-surface-variant">(±{{ $model->accuracy ?? '?' }} m)</span></dd>
                    <dt class="text-on-surface-variant">Alamat</dt><dd class="col-span-2">{{ $model->address ?? '-' }}</dd>
                    @if($kunjungan)
                    <dt class="text-on-surface-variant">Titik kunjungan</dt><dd class="col-span-2">{{ $kunjungan->kunjungan_nama }} <span class="text-on-surface-variant">(radius {{ $kunjungan->kunjungan_radius_m }} m &middot; {{ $kunjungan->kunjungan_status }})</span></dd>
                    <dt class="text-on-surface-variant">Selisih radius</dt>
                    <dd class="col-span-2 font-semibold {{ $model->selisih_radius_m !== null && $model->selisih_radius_m > 0 ? 'text-red-700' : 'text-green-700' }}">
                        @if($model->selisih_radius_m === null)
                        <span class="font-normal text-on-surface-variant">Tanpa titik kunjungan</span>
                        @elseif($model->selisih_radius_m > 0)
                        {{ formatJarak($model->selisih_radius_m, true) }} di luar radius
                        @else
                        {{ formatJarak($model->selisih_radius_m) }} (di dalam radius)
                        @endif
                    </dd>
                    @endif
                    @if($model->original_latitude && $model->original_longitude)
                    <dt class="text-on-surface-variant">Dugaan lokasi asli</dt>
                    <dd class="col-span-2 font-mono text-[13px] text-red-700">{{ $model->original_latitude }}, {{ $model->original_longitude }}
                        @if($model->original_distance_m !== null)<span class="text-on-surface-variant">({{ $model->original_distance_m >= 1000 ? number_format($model->original_distance_m/1000, 1).' km' : round($model->original_distance_m).' m' }} dari titik check-in)</span>@endif
                        <div class="font-sans text-sm mt-1">
                            <span class="inline-block px-2 py-0.5 rounded bg-surface-container text-on-surface-variant text-xs">sumber: {{ $model->original_source === 'gps_raw' ? 'GPS mentah HP' : ($model->original_source === 'network' ? 'jaringan' : ($model->original_source === 'ip' ? 'IP publik (kasar)' : '-')) }}</span>
                            @if($model->original_accuracy)<span class="text-on-surface-variant text-xs">±{{ round($model->original_accuracy) }} m</span>@endif
                            @if($model->original_map_url)<a href="{{ $model->original_map_url }}" target="_blank" class="text-primary hover:underline text-xs ml-1">Buka di Google Maps</a>@endif
                        </div>
                        @if($model->original_address)<div class="font-sans text-sm text-on-surface-variant">{{ $model->original_address }}</div>@endif
                    </dd>
                    @endif
                </dl>
            </x-card>

            <x-card :label="'Detail Teknis'" :icon="'terminal'">
                <dl class="col-span-12 grid grid-cols-3 gap-y-2 text-sm">
                    <dt class="text-on-surface-variant">IP pengirim</dt><dd class="col-span-2 font-mono text-[13px]">{{ $model->ip_address ?? '-' }}</dd>
                    <dt class="text-on-surface-variant">Signature</dt><dd class="col-span-2 font-mono text-[13px] break-all">{{ $model->signature_id ?? '-' }}</dd>
                    <dt class="text-on-surface-variant">Content hash</dt><dd class="col-span-2 font-mono text-[13px] break-all">{{ $model->content_hash ?? $model->hasSignature?->content_hash ?? '-' }}</dd>
                </dl>
                @if(!empty($payload))
                <details class="col-span-12 mt-2 text-sm">
                    <summary class="cursor-pointer text-primary hover:underline">Payload mentah dari HP</summary>
                    <pre class="mt-2 p-3 rounded-lg bg-surface-container text-xs overflow-x-auto">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </details>
                @endif
            </x-card>
        </div>
    </div>

    <x-card :label="'Sinyal Keaslian dari HP'" :icon="'radar'">
                <div class="col-span-12 divide-y divide-outline-variant/60">
                    @foreach($signals as $s)
                    <div class="flex items-start gap-2 py-2 text-sm">
                        <span class="material-symbols-outlined text-lg mt-px {{ $s['bad'] ? 'text-red-600' : 'text-green-600' }}">{{ $s['bad'] ? 'cancel' : 'check_circle' }}</span>
                        <div class="flex-1">
                            <div class="text-on-surface-variant text-xs uppercase tracking-wide">{{ $s['label'] }}</div>
                            <div class="font-medium">{{ $s['value'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-card>

            @if(count($candidates) > 1)
            <x-card :label="'Perbandingan Sumber Lokasi'" :icon="'compare_arrows'">
                <div class="col-span-12 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-on-surface-variant border-b border-outline-variant">
                                <th class="py-2 pr-3">Sumber</th>
                                <th class="py-2 pr-3">Koordinat</th>
                                <th class="py-2 pr-3">Akurasi</th>
                                <th class="py-2 pr-3">Jarak ke klaim HP</th>
                                <th class="py-2">Peta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/60">
                            @foreach($candidates as $i => $c)
                            <tr>
                                <td class="py-2 pr-3 font-medium">{{ $c['label'] }}@if($i === 0) <span class="text-xs text-on-surface-variant">(acuan)</span>@endif</td>
                                <td class="py-2 pr-3 font-mono text-[13px]">{{ $c['lat'] }}, {{ $c['lng'] }}</td>
                                <td class="py-2 pr-3">{{ $c['acc'] !== null ? '±'.round($c['acc']).' m' : '-' }}</td>
                                <td class="py-2 pr-3 font-semibold {{ $c['dist'] !== null && $c['dist'] > 50000 ? 'text-red-600' : '' }}">{{ $c['dist'] === null ? '-' : ($c['dist'] === 0.0 ? '0 m' : ($c['dist'] >= 1000 ? number_format($c['dist']/1000, 1).' km' : round($c['dist']).' m')) }}</td>
                                <td class="py-2"><a href="{{ $c['url'] }}" target="_blank" class="text-primary hover:underline text-xs">Maps</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="text-xs text-on-surface-variant mt-2">Bila GPS mentah/jaringan dekat dengan klaim HP tapi IP jauh → indikasi IP tidak akurat, bukan lokasi palsu. Bila semuanya jauh → lokasi HP patut dicurigai.</p>
                </div>
            </x-card>
            @endif

    @push('scripts')
    <script>
        window.ensureLeaflet(function () {
            var lat = {{ (float) $model->latitude }};
            var lng = {{ (float) $model->longitude }};
            var map = L.map('checkin-map').setView([lat, lng], 16);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
            @if($model->accuracy)
            L.circle([lat, lng], { radius: {{ (float) $model->accuracy }}, color: 'blue', weight: 1, fillOpacity: 0.08 }).addTo(map);
            @endif
            L.marker([lat, lng]).addTo(map).bindPopup(@json($model->address ?? 'Titik check-in')).openPopup();
            @if($kunjungan && $kunjungan->kunjungan_lat && $kunjungan->kunjungan_lng)
            var kLat = {{ (float) $kunjungan->kunjungan_lat }};
            var kLng = {{ (float) $kunjungan->kunjungan_lng }};
            L.circle([kLat, kLng], { radius: {{ (int) ($kunjungan->kunjungan_radius_m ?? 100) }}, color: 'green', weight: 2, fillOpacity: 0.1 }).addTo(map);
            L.marker([kLat, kLng]).addTo(map).bindPopup(@json('Titik: ' . ($kunjungan->kunjungan_nama ?? '-')));
            map.fitBounds(L.latLngBounds([[lat, lng], [kLat, kLng]]).pad(0.3));
            @endif
            @if($model->original_latitude && $model->original_longitude)
            L.marker([{{ (float) $model->original_latitude }}, {{ (float) $model->original_longitude }}], {
                icon: L.divIcon({ className: '', html: '<div style="width:14px;height:14px;border-radius:50%;background:#dc2626;border:2px solid #fff;box-shadow:0 0 4px rgba(0,0,0,.4)"></div>', iconSize: [14, 14] })
            }).addTo(map).bindPopup(@json('Dugaan lokasi asli' . ($model->original_address ? ': '.$model->original_address : '')));
            @endif
        });
    </script>
    @endpush
</x-layouts::app>
