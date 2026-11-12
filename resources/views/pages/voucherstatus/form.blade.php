<?php /** @var App\Models\VoucherStatus $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="4" name="voucher_status_kode" label="Kode (jangan diubah bila sudah dipakai)" />
                <x-input col="4" name="voucher_status_nama" label="Nama Status" />
                <x-input col="4" name="voucher_status_no" label="No Urut (0 = finish)" type="number" />
                <x-input col="12" name="voucher_status_keterangan" label="Keterangan (kosongkan No untuk cabang revisi)" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
