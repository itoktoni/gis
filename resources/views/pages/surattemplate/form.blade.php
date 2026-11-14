<?php /** @var App\Models\SuratTemplate $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="4" name="surat_template_kode" label="Kode (mis. ST, CT)" helper="Kode dipakai di nomor surat: 001/ST/HRD/I/2026" />
                <x-input col="8" name="surat_template_nama" label="Nama Template" />
                <x-wysiwyg col="12" name="surat_template_isi" label="Isi Template" helper="Bisa copy-paste dari Word (bold, tabel, list ikut terbawa). Gunakan placeholder @{{nama}}, @{{tanggal}}, dst." />
                <x-input col="12" name="surat_template_keterangan" label="Keterangan" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    <x-wysiwyg-script />
</x-layouts::app>
