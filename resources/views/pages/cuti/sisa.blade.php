<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Sisa Cuti']]" />
    <x-card :label="'Sisa Cuti ' . ($user ?? '') . ' — ' . ($tahun ?? '')">
        <p>Kuota: <b>{{ $kuota ?? 12 }}</b> hari/tahun</p>
        <p>Sisa: <b>{{ $sisa ?? 0 }}</b> hari</p>
        <p>Terpakai: <b>{{ ($kuota ?? 12) - ($sisa ?? 0) }}</b> hari</p>
        <a href="{{ moduleRoute('getTable') }}" class="btn mt-4">Kembali</a>
    </x-card>
</x-layouts::app>
