<?php /** @var string|null $pertanyaan */ /** @var array|null $hasil */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => 'Tanya KMS']]" />

    <x-form :model="$model" :action="moduleRoute('postChat')">
        <x-card label="Tanya KMS">
            @bind($model ?? null)

                <x-textarea col="12" name="pertanyaan" rows="3"
                    placeholder="Contoh: Bagaimana prosedur pengajuan cuti tahunan?"
                    :value="$pertanyaan ?? null"
                    helper="Tulis pertanyaan seputar dokumen yang tersimpan di KMS." />

                <x-select col="6" name="divisi_ids[]" label="Filter Divisi"
                    :multiple="true" class="search"
                    :options="$divisiOptions"
                    :default="old('divisi_ids', $filterDivisi ?? [])"
                    helper="Kosong = semua divisi." />

                <x-select col="6" name="tag_ids[]" label="Filter Tag"
                    :multiple="true" class="search"
                    :options="$tagOptions"
                    :default="old('tag_ids', $filterTag ?? [])"
                    helper="Kosong = semua tag." />

            @endbind
        </x-card>

        <x-action :model="$model" :cancel="moduleRoute('getTable')">
            <button type="submit" class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95 shrink-0">
                <span class="material-symbols-outlined text-base md:text-xl">forum</span>
                <span class="hidden sm:inline">Tanya</span>
            </button>
        </x-action>
    </x-form>

    @if($hasil)
    <x-card label="Jawaban">
        <div class="col-span-12 space-y-4">
            @if(!empty($filterDivisi) || !empty($filterTag))
            <p class="text-[11px] text-on-surface-variant rounded-lg bg-surface-container px-3 py-2">
                Filter aktif:
                {{ !empty($filterDivisi) ? 'Divisi: '.collect($filterDivisi)->map(fn($id) => $divisiOptions[$id] ?? $id)->implode(', ') : '' }}
                {{ (!empty($filterDivisi) && !empty($filterTag)) ? ' • ' : '' }}
                {{ !empty($filterTag) ? 'Tag: '.collect($filterTag)->map(fn($id) => $tagOptions[$id] ?? $id)->implode(', ') : '' }}
            </p>
            @endif
            <div class="rounded-xl bg-surface-container px-4 py-3 text-sm text-on-surface whitespace-pre-line">{{ $hasil['jawaban'] }}</div>

            @if(!empty($hasil['sumber']))
            <div>
                <p class="font-body-sm text-body-sm font-bold text-on-surface-variant mb-2">Dokumen terkait ({{ count($hasil['sumber']) }})</p>
                <div class="space-y-2">
                    @foreach($hasil['sumber'] as $s)
                    <div class="border border-outline-variant rounded-xl p-3 bg-surface-container-lowest flex items-start gap-3">
                        <span class="shrink-0 inline-flex items-center justify-center min-w-8 h-8 px-2 rounded-lg bg-primary/10 text-primary text-xs font-bold">{{ $s['label'] }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-on-surface truncate">{{ $s['dokumen'] }}</p>
                            <p class="text-[11px] font-semibold text-primary uppercase tracking-wide mt-0.5">{{ $s['lokasi'] }}</p>
                            <p class="text-xs text-on-surface-variant mt-1 line-clamp-3">{{ $s['cuplikan'] }}</p>
                        </div>
                        <a href="{{ route('kms-document.getDownload', ['id' => $s['dokumen_id']]) }}"
                            class="shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg bg-surface-container text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors"
                            title="Unduh dokumen">
                            <span class="material-symbols-outlined text-[20px]">download</span>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <p class="text-xs text-on-surface-variant">Tidak ada dokumen terkait ditemukan.</p>
            @endif
        </div>
    </x-card>
    @endif
</x-layouts::app>
