<?php /** @var App\Models\Vendor $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="4" name="vendor_kode" label="Kode Vendor" />
                <x-input col="4" name="vendor_nama" label="Nama Vendor" />
                <x-select col="4" name="vendor_jenis" label="Jenis" :options="$jenisOptions" />
                <x-input col="6" name="vendor_telepon" label="Telepon" />
                <x-input col="6" name="vendor_rekening" label="Rekening Bank" />
                <x-input col="12" name="vendor_alamat" label="Alamat" />
                <x-input col="12" name="vendor_keterangan" label="Keterangan" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
