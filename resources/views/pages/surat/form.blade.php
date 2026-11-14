<?php /** @var App\Models\Surat $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model" enctype="multipart/form-data">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-select col="4" name="surat_jenis" label="Jenis Surat" :options="$jenisOptions" />
                <x-input col="4" name="surat_tanggal" label="Tanggal" type="date" />
                <x-input col="4" name="surat_lampiran" label="Lampiran" helper="Cth: - , 1 (satu) berkas" />
                <x-select col="4" name="surat_id_divisi" label="Divisi (kode utk nomor)" :options="$divisiOptions" />
                <x-input col="6" name="surat_nomor" label="Nomor Surat" helper="Kosongkan untuk nomor otomatis: 001/JENIS/DIVISI/I/2026" />
                <x-select col="6" name="surat_id_template" label="Template (khusus keluar)" :options="$templateOptions" />
                <x-input col="12" name="surat_perihal" label="Perihal" />
                <x-input col="6" name="surat_dari" label="Dari / Pengirim" />
                <x-input col="6" name="surat_kepada" label="Kepada / Tujuan" />
                <x-wysiwyg col="12" name="surat_isi" label="Isi Surat" helper="Bisa copy-paste dari Word (bold, tabel, list ikut terbawa). Untuk surat dari template, isi otomatis dari placeholder saat generate. Bisa edit manual di sini." />
                <x-input col="6" name="surat_keterangan" label="Keterangan" />
                <x-file col="6" name="surat_file" label="Scan Surat TTD" accept=".pdf,.jpg,.jpeg,.png" :value="$model?->file_url" helper="Upload hasil scan setelah ditandatangani (PDF/JPG/PNG, maks 5MB)" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    <x-wysiwyg-script />
</x-layouts::app>
