<?php /** @var App\Models\Lhp $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-select col="6" name="lhp_id_kebun" label="Nama Kebun" :options="$kebunOptions" class="search" />
                <x-input col="6" name="lhp_tanggal" label="Tanggal" type="date" />

                <x-input col="6" name="lhp_luas_panen" label="Luas Panen (Ha)" type="number" step="any" />
                <x-input col="6" name="lhp_tk_hk" label="TK Panen (HK)" type="number" step="any" />

                <x-input col="6" name="lhp_janjang_panen" label="Janjang Panen (Jjg)" type="number" step="any" />
                <x-input col="6" name="lhp_produksi_panen_kg" label="Produksi Panen (Kg)" type="number" step="any" />

                <x-input col="6" name="lhp_janjang_kirim" label="Janjang Kirim (Jjg)" type="number" step="any" />
                <x-input col="6" name="lhp_produksi_kirim_kg" label="Produksi TBS Kirim (Kg)" type="number" step="any" />

                <x-input col="6" name="lhp_brondolan_kg" label="Brondolan (Kg)" type="number" step="any" />
                <x-input col="6" name="lhp_blok_restan" label="Blok Restan" />

                <x-input col="4" name="lhp_up_le10" label="UP Ha <= 10 Hari" type="number" step="any" />
                <x-input col="4" name="lhp_up_11_15" label="UP Ha 11 s.d 15 Hari" type="number" step="any" />
                <x-input col="4" name="lhp_up_gt15" label="UP Ha > 15 Hari" type="number" step="any" />

                <x-input col="4" name="lhp_hujan_pagi" label="Pagi Jam 6 - 11 (mm)" type="number" step="any" />
                <x-input col="4" name="lhp_hujan_siang" label="Siang Jam 11 - 16 (mm)" type="number" step="any" />
                <x-input col="4" name="lhp_hujan_malam" label="Malam Jam 16 - 6 (mm)" type="number" step="any" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
