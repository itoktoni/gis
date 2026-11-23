<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Report LHP per Blok']]" />

    <div class="content mt-4 lg:mt-0">
        <form method="GET" action="{{ moduleRoute('getBlok') }}" target="_blank">
            <x-card label="Filter Report per Blok">
                <x-select col="4" name="kebun_id" label="Kebun" :options="$kebunOptions" />
                <x-input col="4" type="date" name="dari" label="Dari Tanggal" value="{{ now()->startOfMonth()->toDateString() }}" />
                <x-input col="4" type="date" name="sampai" label="Sampai Tanggal" value="{{ now()->toDateString() }}" />
            </x-card>

            <x-action :action="['print']">
                <button type="submit" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg bg-primary px-4 text-sm font-semibold text-white transition-all hover:brightness-110">
                    <span class="material-symbols-outlined text-xl">visibility</span> Tampilkan
                </button>
                <button type="submit" formaction="{{ moduleRoute('getExportBlok') }}" formtarget="_self" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl">download</span> Export Excel
                </button>
            </x-action>
        </form>

        <div class="h-16"></div>
    </div>
</x-layouts::app>
