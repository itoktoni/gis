<?php /** @var App\Models\Lhp $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-select col="6" name="lhp_id_kebun" label="Nama Kebun" :options="$kebunOptions" class="search" />
                <x-input col="6" name="lhp_tanggal" label="Tanggal" type="date" />

                <x-input col="4" name="lhp_luas_tm" label="Luas TM Planted (Ha)" type="number" />
                <x-input col="4" name="lhp_luas_panen" label="Luas Panen (Ha)" type="number" />
                <x-input col="4" name="lhp_tk_hk" label="TK Panen (HK)" type="number" />

                <x-input col="6" name="lhp_janjang_panen" label="Janjang Panen (Jjg)" type="number" />
                <x-input col="6" name="lhp_produksi_panen_kg" label="Produksi Panen (Kg)" type="number" />

                <x-input col="6" name="lhp_janjang_kirim" label="Janjang Kirim (Jjg)" type="number" />
                <x-input col="6" name="lhp_produksi_kirim_kg" label="Produksi TBS Kirim (Kg)" type="number" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
