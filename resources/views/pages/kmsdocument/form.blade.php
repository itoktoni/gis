<?php /** @var App\Models\KmsDocument $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model" enctype="multipart/form-data">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="6" name="kms_document_judul" label="Judul Dokumen" />
                <x-select col="6" name="kms_document_status" label="Status" :options="$statusOptions" />

                <div class="col-span-12 md:col-span-6">
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Divisi Terkait (bisa beberapa)</label>
                    <select name="divisi_ids[]" multiple class="search w-full font-body-sm" id="select-divisi_ids">
                        @foreach($divisiOptions as $key => $text)
                        <option value="{{ $key }}" {{ in_array($key, old('divisi_ids', isset($model) && $model->exists ? $model->hasDivisis->pluck('divisi_id')->all() : [])) ? 'selected' : '' }}>{{ $text }}</option>
                        @endforeach
                    </select>
                    @error('divisi_ids')<span class="font-label-caps text-label-caps text-error mt-1 block">{{ $message }}</span>@enderror
                </div>

                <div class="col-span-12 md:col-span-6">
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Tag (1 file bisa beberapa)</label>
                    <select name="tag_ids[]" multiple class="search w-full font-body-sm" id="select-tag_ids">
                        @foreach($tagOptions as $key => $text)
                        <option value="{{ $key }}" {{ in_array($key, old('tag_ids', isset($model) && $model->exists ? $model->hasTags->pluck('id')->all() : [])) ? 'selected' : '' }}>{{ $text }}</option>
                        @endforeach
                    </select>
                    @error('tag_ids')<span class="font-label-caps text-label-caps text-error mt-1 block">{{ $message }}</span>@enderror
                </div>

                <x-textarea col="12" name="kms_document_deskripsi" label="Deskripsi" />

                <x-file
                    name="kms_document_file"
                    label="File Dokumen"
                    col="12"
                    accept=".pdf,.docx,.xlsx,.txt,.md,.csv"
                    :preview="false"
                    helper="PDF, DOCX, XLSX, TXT, MD, CSV — maks 10 MB. Teks diekstrak otomatis per halaman (PDF) untuk sitasi." />

                @if(isset($model) && $model->exists && $model->kms_document_file)
                <div class="col-span-12">
                    <p class="font-label-caps text-label-caps text-on-surface-variant">
                        File saat ini: {{ $model->kms_document_file }}
                        ({{ $model->kms_document_jumlah_halaman }} hlm, {{ $model->kms_document_jumlah_chunk }} chunk)
                        — <a href="{{ route('kms-document.getDownload', ['id' => $model->field_primary]) }}" class="text-primary hover:underline">unduh</a>
                    </p>
                    <label class="inline-flex items-center gap-1.5 mt-1 text-xs text-on-surface-variant cursor-pointer">
                        <input type="checkbox" name="remove_kms_document_file" value="1" class="rounded border-outline-variant text-error focus:ring-error"> Hapus file saat ini
                    </label>
                    @if($model->kms_document_error)
                    <p class="font-label-caps text-label-caps text-error mt-1">Error: {{ $model->kms_document_error }}</p>
                    @endif
                </div>
                @endif

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
