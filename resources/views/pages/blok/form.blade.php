<?php /** @var App\Models\Blok $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-select col="6" name="blok_id_kebun" label="Kebun" :options="$kebunOptions" class="search" />
                <x-input col="6" name="blok_kode" label="Kode Blok" />
                <x-input col="12" name="blok_nama" label="Nama Blok" />
                <x-input col="6" name="blok_luas_ha" label="Luas (Ha)" type="number" step="any" />
                <x-input col="6" name="blok_jumlah_pohon" label="Jumlah Pohon" type="number" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
