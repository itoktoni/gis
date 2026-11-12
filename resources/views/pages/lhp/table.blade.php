<?php /** @var App\Models\Lhp $table */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => moduleLabel()]]" />
    <div class="content mt-4 lg:mt-0">
        {{-- Filters --}}
        <x-filter :per-page="25" :fields="$fields">
            <x-slot:advanced>
                @foreach ($fields as $key => $advance)
                <x-filter-item :label="$advance" :name="$key"/>
                @endforeach

                <x-button variant="primary" class="btn-block" onclick="applyAdvanced()">Apply</x-button>
                <x-button variant="soft" class="btn-block" onclick="resetAdvanced()">Reset</x-button>
            </x-slot:advanced>
        </x-filter>

        {{-- Ringkasan halaman ini --}}
        <x-card label="Ringkasan Halaman Ini" :noGrid="true">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Produksi Panen (halaman)</p>
                    <p class="text-lg font-bold text-primary">{{ number_format($data->sum('lhp_produksi_panen_kg'), 0, ',', '.') }} kg</p>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Produksi Kirim (halaman)</p>
                    <p class="text-lg font-bold text-primary">{{ number_format($data->sum('lhp_produksi_kirim_kg'), 0, ',', '.') }} kg</p>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Janjang Panen</p>
                    <p class="text-lg font-bold">{{ number_format($data->sum('lhp_janjang_panen'), 0, ',', '.') }} jjg</p>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Janjang Kirim</p>
                    <p class="text-lg font-bold">{{ number_format($data->sum('lhp_janjang_kirim'), 0, ',', '.') }} jjg</p>
                </div>
            </div>
        </x-card>

        {{-- Table --}}
        @php
            $currentSort = request('sort.0', '');
            $sortField = str_replace(':desc','',str_replace(':asc','',$currentSort));
            $sortDir = str_contains($currentSort, ':desc') ? 'desc' : 'asc';
        @endphp

        <x-table>
            <x-slot:head>
                <x-table-checkbox :model="$model" onchange="toggleAll(this)" />
                <th>Actions</th>
                @foreach ($model::$sortColumns as $column)
                <x-table-sort field="{{ $column }}" label="{{ formatLabel($column) }}" :sortField="$sortField" :sortDir="$sortDir" />
                @endforeach
                <th>BJR Panen</th>
            </x-slot:head>

            <x-slot:body>
                @foreach($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary" />
                    @foreach ($model::$sortColumns as $column)
                    <td>@if($column === 'lhp_id_kebun'){{ $table->hasKebun?->kebun_nama ?? $table->$column }}@if($table->hasKebun?->hasPt)<span class="block text-[10px] text-on-surface-variant">{{ $table->hasKebun->hasPt->pt_nama }}</span>@endif@elseif(in_array($column, ['lhp_produksi_panen_kg','lhp_produksi_kirim_kg'])){{ number_format($table->$column, 0, ',', '.') }}@elseif($column === 'lhp_tanggal'){{ $table->$column?->format('d/m/Y') }}@else{{ $table->$column }}@endif</td>
                    @endforeach
                    <td>{{ $table->bjr_panen }}</td>
                </tr>
                @endforeach
            </x-slot:body>

            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @foreach($data as $table)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm" data-id="{{ $table->field_primary }}">
                        <p class="text-sm font-bold text-on-surface truncate mb-1">{{ $table->hasKebun?->kebun_nama ?? '-' }}</p>
                        <p class="text-[11px] text-on-surface-variant mb-3">{{ $table->lhp_tanggal?->format('d M Y') }} • {{ $table->lhp_hari }}</p>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Luas Panen</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->lhp_luas_panen ?? '-' }} ha</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">TK (HK)</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->lhp_tk_hk ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Panen</p>
                                <p class="text-xs font-medium text-primary">{{ number_format($table->lhp_janjang_panen, 0, ',', '.') }} jjg / {{ number_format($table->lhp_produksi_panen_kg, 0, ',', '.') }} kg</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Kirim</p>
                                <p class="text-xs font-medium text-on-surface">{{ number_format($table->lhp_janjang_kirim, 0, ',', '.') }} jjg / {{ number_format($table->lhp_produksi_kirim_kg, 0, ',', '.') }} kg</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">BJR {{ $table->bjr_panen }}</span>
                            <div class="flex gap-1" onclick="event.stopPropagation()">
                                <x-table-action :model="$model" :id="$table->field_primary" />
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-slot:mobile>

        </x-table>

        <x-pagination :paginator="$data" />
        <x-action :model="$model" :action="['create', 'delete']">
            @can('exportexcel', $model)
            <a href="{{ route('lhp.getExportExcel', ['tanggal' => now()->toDateString()]) }}" class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all shrink-0">
                <span class="material-symbols-outlined text-base md:text-xl">table_view</span>
                <span class="hidden sm:inline">Hari Ini</span>
            </a>
            <a href="{{ route('lhp.getExportExcel', ['all' => 1]) }}" class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all shrink-0">
                <span class="material-symbols-outlined text-base md:text-xl">download</span>
                <span class="hidden sm:inline">Semua Data</span>
            </a>
            @endcan
        </x-action>

        {{-- Jarak bawah agar scrollbar horizontal tabel tidak tertutup action bar fixed --}}
        <div class="h-16"></div>

    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
</x-layouts::app>
