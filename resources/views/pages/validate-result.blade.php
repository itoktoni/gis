@php
$st = $result['status'] ?? '-';
$cls = $st === 'VALID'
    ? 'bg-green-100 text-green-800'
    : (in_array($st, ['TIDAK VALID', 'PALSU', 'DICABUT'])
        ? 'bg-red-100 text-red-700'
        : (in_array($st, ['KEDALUWARSA']) ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-muted'));
@endphp

<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('validate.form'), 'label' => 'Validasi Foto'], ['url' => '', 'label' => 'Hasil']]" />

    <x-card label="Hasil Validasi">
        <div class="col-span-12">
            <span class="inline-block px-4 py-2 rounded-full font-bold text-base {{ $cls }}">{{ $st }}</span>
            <p class="text-sm text-muted mt-2">{{ $result['message'] ?? '' }}</p>
        </div>

        @if(!empty($result['signature']))
            <div class="col-span-12 grid grid-cols-[130px_1fr] gap-x-3 gap-y-1 text-sm">
                <span class="text-muted">ID</span><span class="font-semibold break-all font-mono text-xs">{{ $result['signature']['id'] }}</span>
                <span class="text-muted">Penanda</span><span class="font-semibold">{{ $signature->label ?? $result['signature']['type'] ?? '-' }}</span>
                <span class="text-muted">Ditandatangani</span><span class="font-semibold">{{ $result['signature']['signed_at'] ?? '-' }}</span>
                <span class="text-muted">Pembuat</span><span class="font-semibold">{{ $result['signature']['creator']['name'] ?? '-' }}</span>
            </div>
        @endif

        @if(!empty($checkin))
            <div class="col-span-12">
                <img src="{{ Storage::url($checkin->photo_path) }}" alt="Foto check-in" class="max-w-full rounded-xl border">
                <p class="text-[13px] text-muted mt-2">{{ $checkin->address ?? '' }}</p>
            </div>
        @endif
    </x-card>

    <div class="sticky bottom-0 flex gap-2 py-3">
        <a href="{{ route('validate.form') }}" class="btn btn-primary flex-1 text-center">← Periksa Foto Lain</a>
    </div>
</x-layouts::app>
