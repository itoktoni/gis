<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => 'Import']]" />

    <div class="content mt-4 lg:mt-0">
        <x-card label="Import Titik Kunjungan dari Excel/CSV" :noGrid="true">
            <div class="rounded-xl bg-surface-container px-4 py-3 mb-4 text-sm text-on-surface-variant">
                <p class="font-semibold text-on-surface mb-1">Format header (baris 1):</p>
                <p class="font-mono text-xs">Nama Titik | User (Email) | Kebun | Objek | Tanggal (Y-m-d H:i) | Latitude | Longitude | Radius (m) | Status</p>
                <ul class="text-xs mt-2 list-disc pl-5 space-y-1">
                    <li>Pemisah kolom otomatis: koma / titik-koma / tab / pipe (copy-paste dari Excel bisa disimpan sebagai .txt/.csv).</li>
                    <li>Angka gaya Indonesia diterima: koma desimal ("-2,719885") maupun titik ribuan ("111.643.005").</li>
                    <li>Tanggal fleksibel: "20/11/2026 06:00", "20-11-2026", "2026-11-20" — kosong = selamanya.</li>
                <ul class="text-xs mt-2 list-disc pl-5 space-y-1">
                    <li>User dicari by email; baris dilewati bila user tidak ketemu.</li>
                    <li>Kebun & objek dicari by nama; kebun wajib ada.</li>
                    <li>Tanggal kosong = berlaku selamanya (pos patroli, bisa check-in berulang).</li>
                    <li>Status: pending / done (kosong = pending).</li>
                    <li>User otomatis didaftarkan sebagai anggota titik (multi-user).</li>
                </ul>
                <p class="mt-3">
                    <a href="/templates/kunjungan-import-template.csv" download class="inline-flex items-center gap-2 rounded-lg border border-outline-variant px-4 py-2 text-xs font-semibold text-primary hover:bg-surface-container">
                        <span class="material-symbols-outlined text-base">download</span>
                        Download Contoh CSV
                    </a>
                </p>
            </div>
            <form method="POST" action="{{ moduleRoute('postImport') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="file" name="file" accept=".xlsx,.csv,.txt" required
                    class="w-full text-sm text-on-surface-variant file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-on-primary hover:file:bg-primary/90 file:cursor-pointer">
                @error('file')<p class="text-xs text-error">{{ $message }}</p>@enderror
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary text-on-primary px-5 h-11 text-sm font-semibold hover:bg-primary/90">
                        <span class="material-symbols-outlined">upload</span> Import Sekarang
                    </button>
                    <a href="{{ moduleRoute('getTable') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-outline-variant px-5 h-11 text-sm font-semibold text-on-surface-variant hover:bg-surface-container">Batal</a>
                </div>
            </form>
        </x-card>
        <div class="h-16"></div>
    </div>
</x-layouts::app>
