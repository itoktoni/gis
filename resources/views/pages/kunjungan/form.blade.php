<?php /** @var App\Models\Kunjungan $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-select col="12" name="user_ids[]" label="User (bisa pilih banyak)" :options="$userOptions" :multiple="true" size="6" :default="$model?->assignedUserIds() ?? []" helper="Satu titik untuk banyak satpam. Anggota pertama menjadi penanggung jawab utama." />
                <x-input col="6" name="kunjungan_tanggal" label="Tanggal & Jam (kosongkan = selamanya)" type="datetime-local" />
                <x-input col="12" name="kunjungan_nama" label="Nama Titik" />
                <x-select col="6" name="kunjungan_id_kebun" label="Kebun" :options="$kebunOptions" />
                <x-select col="6" name="kunjungan_id_objek" label="Objek / POS" :options="$objekOptions" />
                <x-input col="4" name="kunjungan_lat" label="Latitude" type="number" step="any" />
                <x-input col="4" name="kunjungan_lng" label="Longitude" type="number" step="any" />
                <x-input col="4" name="kunjungan_radius_m" label="Radius (m)" type="number" />
                <x-select col="6" name="kunjungan_status" label="Status" :options="$statusOptions" placeholder="-- Null --" helper="Null = belum ditentukan. Done dikunci di HP, Pending bisa check-in (titik permanen)." />

                @if(isset($model) && $model->exists && ($model->kunjungan_status === 'done' || $model->kunjungan_id_checkin))
                <div class="col-span-12">
                    @can('reset', $model)
                    <form method="POST" action="{{ moduleRoute('postReset', ['id' => $model->field_primary]) }}" onsubmit="return confirm('Set NULL? Status kembali pending dan tautan check-in dilepas.')">
                        @csrf
                        <button class="inline-flex items-center gap-2 rounded-lg bg-error px-4 py-2 text-sm font-semibold text-white">
                            <span class="material-symbols-outlined text-lg">restart_alt</span> Set NULL (Reset ke Pending)
                        </button>
                    </form>
                    @endcan
                </div>
                @endif

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    <script>
        (function () {
            var objekData = @json($objekGeo ?? []);
            var kebunData = @json($kebunGeo ?? []);
            var objekSelect = document.querySelector('[name="kunjungan_id_objek"]');
            var kebunSelect = document.querySelector('[name="kunjungan_id_kebun"]');
            var namaInput = document.querySelector('[name="kunjungan_nama"]');
            var latInput = document.querySelector('[name="kunjungan_lat"]');
            var lngInput = document.querySelector('[name="kunjungan_lng"]');
            if (!objekSelect) return;
            objekSelect.addEventListener('change', function () {
                var row = objekData[this.value];
                if (!row) return;
                if (namaInput && (!namaInput.value || namaInput.value.indexOf('Kunjungan ') === 0)) namaInput.value = row.nama ?? '';
                if (latInput && row.lat !== null) latInput.value = row.lat;
                if (lngInput && row.lng !== null) lngInput.value = row.lng;
            });
            // Pilih kebun -> isi nama + lat/lng bila masih kosong
            if (kebunSelect) kebunSelect.addEventListener('change', function () {
                var row = kebunData[this.value];
                if (!row) return;
                if (namaInput && !namaInput.value && row.nama) namaInput.value = 'Kunjungan ' + row.nama;
                if (latInput && !latInput.value && row.lat !== null) latInput.value = row.lat;
                if (lngInput && !lngInput.value && row.lng !== null) lngInput.value = row.lng;
            });
        })();
    </script>
</x-layouts::app>
