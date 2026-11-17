<?php /** @var App\Models\Kebun $table */ ?>

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

        {{-- Table --}}
        @php
            $currentSort = request('sort.0', '');
            $sortField = str_replace(':desc','',str_replace(':asc','',$currentSort));
            $sortDir = str_contains($currentSort, ':desc') ? 'desc' : 'asc';
            // Label kolom master kebun mengikuti header file excel LHP
            $kebunLabels = [
                'kebun_nama' => 'Nama Kebun',
                'kebun_luas_ha' => 'Luas TM Planted (Ha)',
                'kebun_target_produksi_kg' => 'Target Produksi (Kg)',
                'kebun_rkap_kg' => 'Produksi TBS RKAP (Kg)',
            ];
        @endphp

        <x-table>
            <x-slot:head>
                <x-table-checkbox :model="$model" onchange="toggleAll(this)" />
                <th>Actions</th>
                @foreach ($model::$sortColumns as $column)
                <x-table-sort field="{{ $column }}" label="{{ $kebunLabels[$column] ?? formatLabel($column) }}" :sortField="$sortField" :sortDir="$sortDir" />
                @endforeach
                <th>Masalah</th>
            </x-slot:head>

            <x-slot:body>
                @foreach($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary" />
                    @foreach ($model::$sortColumns as $column)
                    <td>@if($column === 'kebun_id_pt'){{ $table->hasPt ? $table->hasPt->pt_kode.' - '.$table->hasPt->pt_nama : '-' }}@elseif($column === 'kebun_id_distrik'){{ $table->hasDistrik ? $table->hasDistrik->distrik_kode.' - '.$table->hasDistrik->distrik_nama : '-' }}@elseif(in_array($column, ['kebun_target_produksi_kg','kebun_rkap_kg'])){{ $table->$column !== null ? number_format($table->$column, 0, ',', '.') : '-' }}@else{{ $table->$column }}@endif</td>
                    @endforeach
                    <td>@if(($table->has_masalah_aktif_count ?? 0) > 0)<a href="/masalah/table?filters[masalah_id_kebun][$eq]={{ $table->field_primary }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 hover:underline"><span class="material-symbols-outlined text-sm">warning</span>{{ $table->has_masalah_aktif_count }}</a>@else<span class="text-on-surface-variant">-</span>@endif</td>
                </tr>
                @endforeach
            </x-slot:body>

            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @foreach($data as $table)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm" data-id="{{ $table->field_primary }}">
                        <p class="text-sm font-bold text-on-surface truncate mb-1">{{ $table->kebun_nama }}</p>
                        <p class="text-[11px] text-primary font-medium mb-1">{{ $table->hasPt ? $table->hasPt->pt_kode.' - '.$table->hasPt->pt_nama : 'Tanpa PT' }}</p>
                        <p class="text-[11px] text-on-surface-variant mb-3">{{ $table->hasDistrik ? $table->hasDistrik->distrik_kode.' - '.$table->hasDistrik->distrik_nama : 'Tanpa Distrik' }}</p>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Luas TM Planted</p>
                                <p class="text-xs font-medium text-primary truncate">{{ $table->kebun_luas_ha ?? '-' }} ha</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Pohon</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->kebun_jumlah_pohon ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Target Produksi</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->kebun_target_produksi_kg !== null ? number_format($table->kebun_target_produksi_kg, 0, ',', '.').' kg' : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Produksi TBS RKAP</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->kebun_rkap_kg !== null ? number_format($table->kebun_rkap_kg, 0, ',', '.').' kg' : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Status</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->kebun_status ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Koordinat</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->kebun_lat ?? '-' }}, {{ $table->kebun_lng ?? '-' }}</p>
                            </div>
                            @if(($table->has_masalah_aktif_count ?? 0) > 0)
                            <div class="col-span-2">
                                <a href="/masalah/table?filters[masalah_id_kebun][$eq]={{ $table->field_primary }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-100 text-red-700">
                                    <span class="material-symbols-outlined text-sm">warning</span>{{ $table->has_masalah_aktif_count }} permasalahan terbuka →
                                </a>
                            </div>
                            @endif
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
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
        <x-action :model="$model" :action="['create', 'delete']"/>

    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
</x-layouts::app>
