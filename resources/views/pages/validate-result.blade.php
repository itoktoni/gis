@php
$st = $result['status'] ?? '-';
$cls = $st === 'VALID'
    ? 'bg-green-100 text-green-800'
    : (in_array($st, ['TIDAK VALID', 'PALSU', 'DICABUT'])
        ? 'bg-red-100 text-red-700'
        : (in_array($st, ['KEDALUWARSA']) ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600'));
$sig = $result['signature'] ?? [];
@endphp

<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('validate.form'), 'label' => 'Validasi Foto'], ['url' => '', 'label' => 'Hasil']]" />

    <x-card label="Hasil Validasi">
        <div class="col-span-12">
            <span class="inline-block px-4 py-2 rounded-full font-bold text-base {{ $cls }}">{{ $st }}</span>
            <p class="text-sm text-gray-600 mt-2">{{ $result['message'] ?? '' }}</p>
        </div>

        @if(!empty($checkin))
        <div class="col-span-12">
            <img src="{{ Storage::url($checkin->photo_path) }}" alt="Foto check-in" class="max-w-full rounded-xl border">
            <p class="text-[13px] text-gray-600 mt-2">{{ $checkin->address ?? '' }}</p>
        </div>
        @endif
    </x-card>

    {{-- Metadata keaslian: detail dari tanda tangan digital di dalam gambar --}}
    @if(!empty($sig))
    <x-card label="Metadata Keaslian" :noGrid="true">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">ID Tanda Tangan</dt><dd class="font-mono text-xs break-all font-semibold">{{ $sig['id'] ?? '-' }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Penanda</dt><dd class="font-semibold">{{ $signature->label ?? $sig['type'] ?? '-' }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">File Asli</dt><dd class="font-semibold break-all">{{ $signature->original_filename ?? '-' }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Ditandatangani</dt><dd class="font-semibold">{{ $sig['signed_at'] ?? '-' }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Kedaluwarsa</dt><dd class="font-semibold">{{ $sig['expired_at'] ?? 'Tidak ada' }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Pembuat</dt><dd class="font-semibold">{{ $sig['creator']['name'] ?? '-' }}</dd></div>
            <div class="flex gap-2 sm:col-span-2"><dt class="w-36 shrink-0 text-gray-500">Hash SHA256</dt><dd class="font-mono text-xs break-all">{{ $sig['content_hash'] ?? '-' }}</dd></div>
        </dl>
    </x-card>
    @endif

    {{-- Detail gambar check-in: lokasi, perangkat, dan status verifikasi --}}
    @if(!empty($checkin) || !empty($signature))
    <x-card label="Detail Gambar" :noGrid="true">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
            @if(!empty($checkin))
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Waktu HP</dt><dd class="font-semibold">{{ $checkin->client_timestamp?->format('d/m/Y H:i:s') ?? '-' }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Terekam Server</dt><dd class="font-semibold">{{ $checkin->created_at?->format('d/m/Y H:i:s') ?? '-' }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Koordinat</dt><dd class="font-mono text-xs font-semibold">{{ $checkin->latitude }}, {{ $checkin->longitude }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Akurasi GPS</dt><dd class="font-semibold">±{{ $checkin->accuracy ?? '-' }} m</dd></div>
            <div class="flex gap-2 sm:col-span-2"><dt class="w-36 shrink-0 text-gray-500">Alamat</dt><dd class="font-semibold">{{ $checkin->address ?? '-' }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Selisih Radius</dt><dd class="font-semibold">{{ $checkin->selisih_radius_m !== null ? ($checkin->selisih_radius_m > 0 ? '+' : '').$checkin->selisih_radius_m.' m' : '-' }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Status Data</dt><dd class="font-semibold uppercase">{{ $checkin->status }}</dd></div>
            @if($checkin->failure_reason)
            <div class="flex gap-2 sm:col-span-2"><dt class="w-36 shrink-0 text-gray-500">Alasan</dt><dd class="text-red-700">{{ $checkin->failure_reason }}</dd></div>
            @endif
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Offline</dt><dd class="font-semibold">{{ $checkin->is_offline ? 'Ya' : 'Tidak' }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">IP Pengirim</dt><dd class="font-mono text-xs">{{ $checkin->ip_address ?? '-' }}</dd></div>
            @if($checkin->original_latitude !== null)
            <div class="flex gap-2 sm:col-span-2"><dt class="w-36 shrink-0 text-gray-500">Dugaan Lokasi Asli</dt><dd class="font-mono text-xs">{{ $checkin->original_latitude }}, {{ $checkin->original_longitude }} ({{ $checkin->original_source }}, ±{{ $checkin->original_distance_m }} m)</dd></div>
            @endif
            @endif
            @if(!empty($signature))
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Mock GPS</dt><dd class="font-semibold">{{ $signature->mock ? 'Ya ⚠️' : 'Tidak' }}</dd></div>
            <div class="flex gap-2"><dt class="w-36 shrink-0 text-gray-500">Mode Developer</dt><dd class="font-semibold">{{ $signature->developer ? 'Ya ⚠️' : 'Tidak' }}</dd></div>
            @endif
        </dl>
    </x-card>
    @endif

    <div class="h-24"></div>

    <div class="fixed left-0 right-0 lg:left-72 bg-surface-container-lowest border-t border-outline-variant shadow-[0_-4px_12px_rgba(0,0,0,0.08)] px-3 md:px-6 py-2 md:py-3 z-[45]" style="bottom: 4rem">
        <div class="flex items-center justify-end max-w-full mx-auto gap-2 md:gap-3">
            <a href="{{ route('validate.form') }}" class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95 shrink-0">← Periksa Foto Lain</a>
        </div>
    </div>
</x-layouts::app>
