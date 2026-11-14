<?php /** @var App\Models\Users $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model" enctype="multipart/form-data">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="6" name="name" />
                <x-input col="6" name="email" />
                <x-input col="6" type="password" name="password" />
                <x-select col="6" name="role" :options="$role"/>

                <x-select col="12" name="kebun_ids[]" label="Batasi Akses Kebun" :multiple="true" size="6" :options="$kebunOptions" :default="$model?->hasKebunList->pluck('kebun_id')->all() ?? []" helper="Kosongkan = akses semua kebun. Tahan Ctrl/Shift + klik untuk memilih beberapa (mis. hanya 2 kebun)." />

                <x-file
                    name="avatar"
                    label="Foto Profil"
                    col="12"
                    accept="image/*"
                    capture="environment"
                    :preview="true"
                    :value="$model?->avatar_url"
                    helper="Ambil foto via kamera di HP atau pilih dari galeri" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
