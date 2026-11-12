<?php /** @var string|null $pertanyaan */ /** @var array|null $hasil */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Tanya KMS']]" />

    <div class="content mt-4 lg:mt-0 max-w-3xl mx-auto space-y-4">
        <x-card label="Tanya KMS">
            <form method="POST" action="{{ moduleRoute('postChat') }}" class="col-span-12 space-y-3">
                @csrf
                <div>
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Pertanyaan</label>
                    <textarea name="pertanyaan" rows="3" required maxlength="2000"
                        placeholder="Contoh: Bagaimana prosedur pengajuan cuti tahunan?"
                        class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none">{{ old('pertanyaan', $pertanyaan) }}</textarea>
                    @error('pertanyaan')
                    <span class="font-label-caps text-label-caps text-error mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Filter Divisi (opsional — kosong = semua)</label>
                        <select name="divisi_ids[]" multiple class="search w-full font-body-sm">
                            @foreach($divisiOptions as $key => $text)
                            <option value="{{ $key }}" {{ in_array($key, old('divisi_ids', $filterDivisi ?? [])) ? 'selected' : '' }}>{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Filter Tag (opsional — kosong = semua)</label>
                        <select name="tag_ids[]" multiple class="search w-full font-body-sm">
                            @foreach($tagOptions as $key => $text)
                            <option value="{{ $key }}" {{ in_array($key, old('tag_ids', $filterTag ?? [])) ? 'selected' : '' }}>{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit"
                    class="inline-flex items-center gap-2 h-10 px-4 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95">
                    <span class="material-symbols-outlined text-[20px]">forum</span> Tanya
                </button>
            </form>
        </x-card>

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
    </div>
</x-layouts::app>
