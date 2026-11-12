<?php /** @var App\Models\Voucher $table */ ?>

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

        {{-- Ringkasan --}}
        <x-card label="Ringkasan" :noGrid="true">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Total Nilai (halaman)</p>
                    <p class="text-lg font-bold text-primary">Rp {{ number_format($data->sum('voucher_nilai'), 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Sudah Dibayar</p>
                    <p class="text-lg font-bold text-success">Rp {{ number_format($data->where('voucher_status', 'selesai')->sum('voucher_nilai'), 0, ',', '.') }}</p>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Perlu Revisi</p>
                    <p class="text-lg font-bold text-error">{{ $data->where('voucher_status', 'revisi')->count() }} dokumen</p>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Dalam Proses</p>
                    <p class="text-lg font-bold">{{ $data->whereNotIn('voucher_status', ['selesai', 'revisi'])->count() }} dokumen</p>
                </div>
            </div>
        </x-card>

        {{-- Table --}}
        @php
            $currentSort = request('sort.0', '');
            $sortField = str_replace(':desc','',str_replace(':asc','',$currentSort));
            $sortDir = str_contains($currentSort, ':desc') ? 'desc' : 'asc';
            $badge = [
                'draft' => 'bg-surface-container text-on-surface-variant',
                'verifikasi_fa' => 'bg-amber-100 text-amber-700',
                'verifikasi_ap' => 'bg-blue-100 text-blue-700',
                'kirim_jakarta' => 'bg-violet-100 text-violet-700',
                'siap_bayar' => 'bg-green-100 text-green-700',
                'selesai' => 'bg-green-600 text-white',
                'revisi' => 'bg-red-100 text-red-700',
            ];
        @endphp

        <x-table>
            <x-slot:head>
                <x-table-checkbox :model="$model" onchange="toggleAll(this)" />
                <th>Actions</th>
                <th>No Voucher</th>
                <th>Vendor</th>
                <th>Estate</th>
                <th>Nilai</th>
                <th>Tanggal</th>
                <th>Status</th>
            </x-slot:head>

            <x-slot:body>
                @foreach($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary">
                        @can('show', $model)
                        <a href="{{ moduleRoute('getShow', ['id' => $table->field_primary]) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-info/10 text-info hover:bg-info/20 transition-colors" title="Tracking">
                            <span class="material-symbols-outlined text-lg">timeline</span>
                        </a>
                        @endcan
                    </x-table-action>
                    <td class="font-mono font-semibold">{{ $table->voucher_no }}</td>
                    <td>{{ $table->hasVendor?->vendor_nama ?? '-' }}</td>
                    <td>{{ $table->voucher_estate ?? $table->hasKebun?->kebun_nama ?? '-' }}</td>
                    <td class="text-right font-semibold">Rp {{ number_format($table->voucher_nilai, 0, ',', '.') }}</td>
                    <td>{{ $table->voucher_tanggal?->format('d/m/Y') ?? '-' }}</td>
                    <td><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badge[$table->voucher_status] ?? $badge['draft'] }}">{{ $table->status_label }}</span></td>
                </tr>
                @endforeach
            </x-slot:body>

            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @foreach($data as $table)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm" data-id="{{ $table->field_primary }}">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <p class="text-sm font-bold font-mono text-on-surface truncate">{{ $table->voucher_no }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0 {{ $badge[$table->voucher_status] ?? $badge['draft'] }}">{{ $table->status_label }}</span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mb-3">{{ $table->hasVendor?->vendor_nama ?? '-' }}</p>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Nilai</p>
                                <p class="text-xs font-bold text-primary">Rp {{ number_format($table->voucher_nilai, 0, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Tanggal</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->voucher_tanggal?->format('d M Y') ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <a href="{{ moduleRoute('getShow', ['id' => $table->field_primary]) }}" wire:navigate class="text-[11px] font-semibold text-primary hover:underline">Tracking →</a>
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
            @can('import', $model)
            <a href="{{ moduleRoute('getImport') }}" wire:navigate class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all shrink-0">
                <span class="material-symbols-outlined text-base md:text-xl">upload</span>
                <span class="hidden sm:inline">Import</span>
            </a>
            <a href="/templates/voucher-import-template.csv" download class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all shrink-0" title="Download contoh CSV import">
                <span class="material-symbols-outlined text-base md:text-xl">description</span>
                <span class="hidden sm:inline">Contoh</span>
            </a>
            @endcan
            @can('exportexcel', $model)
            <a href="{{ moduleRoute('getExportExcel') }}" class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all shrink-0">
                <span class="material-symbols-outlined text-base md:text-xl">table_view</span>
                <span class="hidden sm:inline">Excel</span>
            </a>
            @endcan
        </x-action>

        <div class="h-16"></div>
    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
</x-layouts::app>
