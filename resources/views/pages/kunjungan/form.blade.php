<?php /** @var App\Models\Kunjungan $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-select col="6" name="kunjungan_id_user" label="User" :options="$userOptions" />
                <x-input col="6" name="kunjungan_tanggal" label="Tanggal & Jam" type="datetime-local" />
                <x-input col="12" name="kunjungan_nama" label="Nama Titik" />
                <x-select col="6" name="kunjungan_id_kebun" label="Kebun" :options="$kebunOptions" />
                <x-select col="6" name="kunjungan_id_objek" label="Objek / POS" :options="$objekOptions" />
                <x-input col="4" name="kunjungan_lat" label="Latitude" type="number" step="any" />
                <x-input col="4" name="kunjungan_lng" label="Longitude" type="number" step="any" />
                <x-input col="4" name="kunjungan_radius_m" label="Radius (m)" type="number" />
                <x-input col="6" name="kunjungan_status" label="Status" readonly disabled :value="$model?->kunjungan_status ?? 'pending'" helper="Status dikelola aplikasi Android (pending/done)." />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    <script>
        (function () {
            var objekData = @json($objekGeo ?? []);
            var objekSelect = document.querySelector('[name="kunjungan_id_objek"]');
            var namaInput = document.querySelector('[name="kunjungan_nama"]');
            var latInput = document.querySelector('[name="kunjungan_lat"]');
            var lngInput = document.querySelector('[name="kunjungan_lng"]');
            if (!objekSelect) return;
            objekSelect.addEventListener('change', function () {
                var row = objekData[this.value];
                if (!row) return;
                if (namaInput && !namaInput.value) namaInput.value = row.nama ?? '';
                if (latInput && row.lat !== null) latInput.value = row.lat;
                if (lngInput && row.lng !== null) lngInput.value = row.lng;
            });
        })();
    </script>
</x-layouts::app>
