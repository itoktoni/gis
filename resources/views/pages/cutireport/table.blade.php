<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Report Cuti']]" />

    <div class="content mt-4 lg:mt-0">
        <form method="GET" action="{{ moduleRoute('getPrint') }}" target="_blank">
            <x-card label="Filter Report Cuti">
                <x-input col="3" type="date" name="dari" label="Dari Tanggal" :value="request('dari', now()->startOfMonth()->toDateString())" />
                <x-input col="3" type="date" name="sampai" label="Sampai Tanggal" :value="request('sampai', now()->toDateString())" />
                <x-select col="3" name="cuti_id_user" label="Karyawan" :options="['' => 'Semua'] + $userOptions" :placeholder="false" :default="request('cuti_id_user')" />
                <x-select col="3" name="cuti_status" label="Status" :placeholder="false"
                    :options="['' => 'Semua', 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak']"
                    :default="request('cuti_status')" />
            </x-card>

            <x-action :action="['print']">
                <button type="submit" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg bg-primary px-4 text-sm font-semibold text-white transition-all hover:brightness-110">
                    <span class="material-symbols-outlined text-xl">visibility</span> Tampilkan
                </button>
                <button type="submit" formaction="{{ moduleRoute('getExportExcel') }}" formtarget="_self" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl">download</span> Export Excel
                </button>
            </x-action>
        </form>

        <div class="h-16"></div>
    </div>
</x-layouts::app>
