<?php /** @var App\Models\VoucherStatus $table */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => moduleLabel()]]" />
    <div class="content mt-4 lg:mt-0">
        <x-filter :per-page="25" :fields="$fields">
            <x-slot:advanced>
                @foreach ($fields as $key => $advance)
                <x-filter-item :label="$advance" :name="$key"/>
                @endforeach

                <x-button variant="primary" class="btn-block" onclick="applyAdvanced()">Apply</x-button>
                <x-button variant="soft" class="btn-block" onclick="resetAdvanced()">Reset</x-button>
            </x-slot:advanced>
        </x-filter>

        <div class="rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 mb-5 text-xs text-on-surface-variant">
            Nomor urut langkah: <strong>1..N</strong> alur maju, <strong>0</strong> = finish (tidak ada langkah lagi), <strong>kosong</strong> = cabang (revisi). Jangan ubah <strong>kode</strong> yang sudah dipakai dokumen.
        </div>

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
            </x-slot:head>

            <x-slot:body>
                @foreach($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary" />
                    @foreach ($model::$sortColumns as $column)
                    <td>@if($column === 'voucher_status_no')<span class="inline-flex items-center justify-center min-w-8 h-8 px-2 rounded-lg {{ $table->$column === 0 ? 'bg-green-600 text-white' : ($table->$column === null ? 'bg-red-100 text-red-700' : 'bg-primary/10 text-primary') }} text-xs font-bold">{{ $table->$column ?? '∞' }}</span>@elseif($column === 'voucher_status_kode')<span class="font-mono font-semibold">{{ $table->$column }}</span>@else{{ $table->$column }}@endif</td>
                    @endforeach
                </tr>
                @endforeach
            </x-slot:body>

            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @foreach($data as $table)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm" data-id="{{ $table->field_primary }}">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="inline-flex items-center justify-center min-w-8 h-8 px-2 rounded-lg {{ $table->voucher_status_no === 0 ? 'bg-green-600 text-white' : ($table->voucher_status_no === null ? 'bg-red-100 text-red-700' : 'bg-primary/10 text-primary') }} text-xs font-bold">{{ $table->voucher_status_no ?? '∞' }}</span>
                            <p class="text-sm font-bold text-on-surface truncate">{{ $table->voucher_status_nama }}</p>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->voucher_status_kode }}</span>
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

        <div class="h-16"></div>
    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
</x-layouts::app>
