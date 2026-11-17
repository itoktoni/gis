<?php /** @var App\Models\Cuti $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model" enctype="multipart/form-data">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-select col="6" name="cuti_id_user" label="Karyawan" :options="$userOptions" />
                <x-input col="6" name="cuti_bagian" label="Bagian" />
                <x-select col="6" name="cuti_jenis" label="Jenis Cuti" :options="$jenisOptions" />
                <x-input col="4" type="date" name="cuti_tanggal_mulai" label="Tanggal Mulai" />
                <x-input col="4" type="date" name="cuti_tanggal_selesai" label="Tanggal Selesai" />
                <x-input col="4" name="cuti_jumlah_hari" label="Jumlah Hari (otomatis)" helper="Dihitung otomatis mulai s/d selesai" />
                <x-textarea col="12" name="cuti_keperluan" label="Keperluan" />
                <x-textarea col="12" name="cuti_alamat" label="Alamat Cuti" />

                <x-file
                    name="cuti_file"
                    label="Lampiran (surat dokter, dll)"
                    col="12"
                    accept="image/*,.pdf"
                    :preview="false"
                    helper="Opsional, maks 2MB" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
