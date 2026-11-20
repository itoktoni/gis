<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('dashboard'), 'label' => 'Home'], ['url' => '', 'label' => 'Validasi Foto']]" />

    <form method="POST" action="{{ route('validate.check') }}" enctype="multipart/form-data">
        @csrf
        <x-card label="Validasi Foto">
            <p class="text-sm text-muted col-span-12">Unggah foto hasil check-in / dokumen bertanda tangan untuk memeriksa keaslian tanda tangan digitalnya.</p>

            @if($errors->any())
                <div class="col-span-12 rounded-lg bg-red-50 text-red-700 px-4 py-2 text-sm">{{ $errors->first() }}</div>
            @endif

            <x-file
                name="file"
                label="Foto"
                col="12"
                accept="image/png,image/jpeg"
                hint="PNG / JPG — Maks 10MB"
                helper="Unggah foto dari galeri atau ambil via kamera HP." />

            <div class="col-span-12 text-sm text-muted">
                <ol class="list-decimal pl-5 space-y-1">
                    <li>Foto yang <strong>asli dari aplikasi</strong> → VALID.</li>
                    <li>Foto yang <strong>diubah 1 piksel saja</strong> → TIDAK VALID.</li>
                    <li>Foto <strong>tanpa tanda tangan</strong> → TIDAK DITEMUKAN.</li>
                </ol>
            </div>
        </x-card>

        <div class="h-24"></div>

        <style>@media (min-width: 768px) { .validate-bar { bottom: 0 !important; } }</style>
        <div class="validate-bar fixed left-0 right-0 lg:left-72 bg-surface-container-lowest border-t border-outline-variant shadow-[0_-4px_12px_rgba(0,0,0,0.08)] px-3 md:px-6 py-2 md:py-3 z-[45]" style="bottom: 4rem">
            <div class="flex items-center justify-end max-w-full mx-auto gap-2 md:gap-3">
                <a href="{{ route('validate.form') }}" class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all shrink-0">Reset</a>
                <button type="submit" class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95 shrink-0">Periksa Keaslian</button>
            </div>
        </div>
    </form>
</x-layouts::app>
