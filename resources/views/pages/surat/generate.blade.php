<?php /** @var App\Models\SuratTemplate $template */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => route('surat-template.getTable'), 'label' => 'Template Surat'], ['url' => '', 'label' => 'Generate: '.$template->surat_template_nama]]" />

    <x-form :model="$model" :action="moduleRoute('postGenerate')">
        <x-card :label="'Generate — '.$template->surat_template_nama">
            @bind($model ?? null)

                <input type="hidden" name="surat_id_template" value="{{ $template->surat_template_id }}" />
                <x-input col="4" name="surat_tanggal" label="Tanggal Surat" type="date" />
                <x-select col="4" name="surat_id_divisi" label="Divisi (kode utk nomor)" :options="$divisiOptions" />
                <x-input col="4" name="surat_lampiran" label="Lampiran" />
                <x-input col="12" name="surat_perihal" label="Perihal" />
                <x-input col="6" name="surat_dari" label="Dari" />
                <x-input col="6" name="surat_kepada" label="Kepada" />

                @foreach($placeholders as $key)
                <x-input col="6" name="data_{{ $key }}" label="{{ ucwords(str_replace('_', ' ', $key)) }}" />
                @endforeach

                <x-input col="12" name="surat_keterangan" label="Keterangan" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    <x-card label="Preview Template">
        <div class="prose max-w-none border border-outline-variant rounded-lg p-4 bg-white">
            {!! $template->surat_template_isi !!}
        </div>
    </x-card>

    <x-wysiwyg-script />
</x-layouts::app>
