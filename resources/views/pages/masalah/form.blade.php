<?php /** @var App\Models\Masalah $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model" enctype="multipart/form-data">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-select col="6" name="masalah_id_kebun" label="Lokasi Kebun" :options="$kebunOptions" class="search" />
                <x-input col="3" name="masalah_tanggal" label="Tanggal" type="date" />
                <x-select col="3" name="masalah_status" label="Status" :options="$statusOptions" />

                <x-file
                    name="masalah_file"
                    label="Foto Kondisi Lapangan"
                    col="12"
                    accept="image/*"
                    capture="environment"
                    :preview="true"
                    :value="$model?->file_url"
                    helper="Ambil foto via kamera di HP atau pilih dari galeri (maks 5MB)" />

                <x-textarea col="12" name="masalah_permasalahan" label="Permasalahan" />
                <x-textarea col="6" name="masalah_kondisi" label="Kondisi Saat Ini" />
                <x-textarea col="6" name="masalah_dampak" label="Dampak" />
                <x-textarea col="6" name="masalah_penyebab" label="Penyebab" />
                <x-textarea col="6" name="masalah_rtl" label="Corrective Action / RTL" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
