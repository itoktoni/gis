<?php /** @var App\Models\Lhp $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => 'Import Excel']]" />

    <x-form :model="$model" action="{{ moduleRoute('postImport') }}" enctype="multipart/form-data">
        <x-card label="Import LHP dari Excel">
            @bind($model ?? null)
                <div class="col-span-12 text-sm text-on-surface-variant space-y-1 rounded-xl bg-surface-container px-4 py-3">
                    <p class="font-semibold text-on-surface">File yang didukung: LHP Distrik (tab per PT) atau sheet DB_All.</p>
                    <p>Kolom ter-mapping otomatis dari header: Tanggal, Hari, Nama Kebun, Luas Panen, TK, Janjang/Kg Panen &amp; Kirim, Brondolan, UP (3 kolom), Curah hujan (3 kolom), Blok Restan.</p>
                    <p>Luas TM, Target, dan RKAP masuk ke <b>master kebun</b> (hanya ditimpa bila &gt; 0). BJR, Output, % Kutip, Restan, dan Total dihitung otomatis.</p>
                    <p>Kebun yang belum ada akan <b>dibuat otomatis</b> (direlasikan ke PT bila namanya cocok). Baris (kebun, tanggal) yang sama akan <b>diperbarui</b>, bukan digandakan.</p>
                    <p class="mt-2"><b>Format per-blok</b> (satu baris per blok): bila ada kolom <b>Blok</b>, file dibaca per blok — Tanggal | Nama Kebun | Blok | Luas Panen | Janjang Panen | Janjang Kirim | Brondolan Panen | Brondolan Kirim | Restan (Y/T) | TK | Produksi Panen | Produksi Kirim. Blok baru dibuat otomatis, total header dihitung dari rincian.</p>
                    <p class="mt-2 flex flex-wrap gap-2">
                        <a href="/templates/lhp-blok-template.csv" download class="inline-flex items-center gap-2 rounded-lg border border-outline-variant px-4 py-2 text-xs font-semibold text-primary hover:bg-surface-container">
                            <span class="material-symbols-outlined text-base">download</span>
                            Download Contoh Per-Blok
                        </a>
                    </p>
                </div>

                <div class="col-span-12">
                    <label class="text-sm font-medium">File Excel (.xlsx, maks 20 MB)</label>
                    <input type="file" name="file" accept=".xlsx,.csv" required
                        class="mt-1 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-on-primary hover:file:bg-primary/90 border border-outline-variant rounded-lg px-3 py-2" />
                    @error('file')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                </div>
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']" :cancel="moduleRoute('getTable')" />
    </x-form>

    @isset($summary)
    <x-card label="Hasil Import">
        <div class="col-span-6 md:col-span-3 rounded-xl bg-surface-container px-4 py-3">
            <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Baris dibaca</p>
            <p class="text-lg font-bold">{{ number_format($summary['rows_read'], 0, ',', '.') }}</p>
        </div>
        <div class="col-span-6 md:col-span-3 rounded-xl bg-surface-container px-4 py-3">
            <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Tersimpan</p>
            <p class="text-lg font-bold text-success">{{ number_format($summary['upserted'], 0, ',', '.') }}</p>
        </div>
        <div class="col-span-6 md:col-span-3 rounded-xl bg-surface-container px-4 py-3">
            <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Kebun baru</p>
            <p class="text-lg font-bold text-primary">{{ count($summary['kebun_created']) }}</p>
        </div>
        <div class="col-span-6 md:col-span-3 rounded-xl bg-surface-container px-4 py-3">
            <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Sheet</p>
            <p class="text-lg font-bold">{{ count($summary['sheets']) }}</p>
        </div>
        @if($summary['kebun_created'])
        <div class="col-span-12">
            <p class="text-sm font-semibold mb-1">Kebun baru dibuat:</p>
            <ul class="text-sm text-on-surface-variant list-disc ml-5">
                @foreach($summary['kebun_created'] as $nama)
                <li>{{ $nama }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        <div class="col-span-12">
            <p class="text-sm font-semibold mb-1">Detail sheet:</p>
            <ul class="text-sm text-on-surface-variant list-disc ml-5">
                @foreach($summary['sheets'] as $s)
                <li>{{ $s }}</li>
                @endforeach
            </ul>
        </div>
    </x-card>
    @endisset
</x-layouts::app>
