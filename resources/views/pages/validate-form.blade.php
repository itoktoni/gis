<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Validasi Foto']]" />

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
                :preview="true"
                helper="Format PNG/JPG, maks 10MB. Bisa ambil via kamera HP." />

            <div class="col-span-12 text-sm text-muted">
                <ol class="list-decimal pl-5 space-y-1">
                    <li>Foto yang <strong>asli dari aplikasi</strong> → VALID.</li>
                    <li>Foto yang <strong>diubah 1 piksel saja</strong> → TIDAK VALID.</li>
                    <li>Foto <strong>tanpa tanda tangan</strong> → TIDAK DITEMUKAN.</li>
                </ol>
            </div>
        </x-card>

        <div class="sticky bottom-0 flex gap-2 py-3">
            <button type="submit" class="btn btn-primary flex-1">Periksa Keaslian</button>
            <a href="{{ route('validate.form') }}" class="btn btn-soft">Reset</a>
        </div>
    </form>
</x-layouts::app>
