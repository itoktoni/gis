<?php /** @var array $info */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('dashboard'), 'label' => 'Dashboard'], ['url' => '', 'label' => 'Update APK']]" />

    <form method="POST" action="{{ route('apk-update.save') }}" enctype="multipart/form-data" class="mt-4 lg:mt-0">
        @csrf
        <x-card label="Versi Aktif">
            <div class="col-span-12 rounded-xl bg-surface-container px-4 py-3 text-sm">
                <span class="font-bold">v{{ $info['version_name'] }}</span>
                <span class="text-on-surface-variant">(code {{ $info['version_code'] }})</span>
                @if($info['force'])
                <span class="ml-2 rounded-full bg-error/10 text-error px-2 py-0.5 text-xs font-bold">WAJIB UPDATE</span>
                @endif
                <p class="text-on-surface-variant mt-1 whitespace-pre-line">{{ $info['changelog'] ?: '—' }}</p>
                <a href="{{ url('apk/apn-monitoring.apk') }}" class="text-primary text-xs font-semibold mt-1 inline-block">Unduh APK saat ini</a>
            </div>
        </x-card>

        <x-card label="Publish Versi Baru">
            <x-input col="6" name="version_code" type="number" label="Version Code"
                :value="old('version_code', $info['version_code'] + 1)"
                helper="Harus naik, mis. {{ $info['version_code'] + 1 }}." />
            <x-input col="6" name="version_name" label="Version Name"
                :value="old('version_name', $info['version_name'])"
                helper="Mis. 1.3.0." />
            <x-textarea col="12" name="changelog" label="Changelog"
                :value="old('changelog')"
                helper="Tampil di popup update HP." />
            <x-file col="12" name="apk" label="File APK (opsional)"
                accept=".apk"
                hint="APK — Maks 100MB. Kosongkan jika hanya ubah info versi."
                helper="Harus signed dengan keystore yang sama." />
            <div class="col-span-12">
                <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                    <input type="checkbox" name="force" value="1" {{ old('force', $info['force']) ? 'checked' : '' }} class="rounded border-outline-variant text-primary focus:ring-primary">
                    Paksa update (user tidak bisa lewati)
                </label>
            </div>
        </x-card>

        <div class="h-24"></div>

        <style>@media (min-width: 768px) { .validate-bar { bottom: 0 !important; } }</style>
        <div class="validate-bar fixed left-0 right-0 lg:left-72 bg-surface-container-lowest border-t border-outline-variant shadow-[0_-4px_12px_rgba(0,0,0,0.08)] px-3 md:px-6 py-2 md:py-3 z-[45]" style="bottom: 4rem">
            <div class="flex items-center justify-end max-w-full mx-auto gap-2 md:gap-3">
                <button type="submit" class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95 shrink-0">Publish</button>
            </div>
        </div>
    </form>
</x-layouts::app>
