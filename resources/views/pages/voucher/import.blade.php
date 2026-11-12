<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => 'Import']]" />

    <div class="content mt-4 lg:mt-0">
        <x-card label="Import Voucher dari Excel" :noGrid="true">
            <div class="rounded-xl bg-surface-container px-4 py-3 mb-4 text-sm text-on-surface-variant">
                <p class="font-semibold text-on-surface mb-1">Format header (baris 1):</p>
                <p class="font-mono text-xs">Nomor Voucher | Kontraktor | Redaksi | Nomor Invoice | Quantity | Satuan | Nilai Voucher</p>
                <p class="text-xs mt-2">Vendor baru otomatis dibuat (kode VND001 dst). Nomor voucher duplikat dilewati.</p>
                <p class="mt-3">
                    <a href="/templates/voucher-import-template.csv" download class="inline-flex items-center gap-2 rounded-lg border border-outline-variant px-4 py-2 text-xs font-semibold text-primary hover:bg-surface-container">
                        <span class="material-symbols-outlined text-base">download</span>
                        Download Contoh CSV
                    </a>
                </p>
            </div>
            <form method="POST" action="{{ moduleRoute('postImport') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="file" name="file" accept=".xlsx,.csv" required
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
