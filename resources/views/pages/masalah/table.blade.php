<?php /** @var App\Models\Masalah $table */ ?>

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
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Terbuka</p>
                    <p class="text-lg font-bold text-error">{{ $data->where('masalah_status', 'open')->count() }} laporan</p>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Diproses</p>
                    <p class="text-lg font-bold text-warning">{{ $data->where('masalah_status', 'proses')->count() }} laporan</p>
                </div>
                <div class="rounded-xl bg-surface-container px-4 py-3">
                    <p class="text-[10px] uppercase tracking-wide text-on-surface-variant">Selesai</p>
                    <p class="text-lg font-bold text-success">{{ $data->where('masalah_status', 'selesai')->count() }} laporan</p>
                </div>
            </div>
        </x-card>

        {{-- Table --}}
        @php
            $currentSort = request('sort.0', '');
            $sortField = str_replace(':desc','',str_replace(':asc','',$currentSort));
            $sortDir = str_contains($currentSort, ':desc') ? 'desc' : 'asc';
            $badge = [
                'open' => 'bg-red-100 text-red-700',
                'proses' => 'bg-amber-100 text-amber-700',
                'selesai' => 'bg-green-100 text-green-700',
            ];
        @endphp

        <x-table>
            <x-slot:head>
                <x-table-checkbox :model="$model" onchange="toggleAll(this)" />
                <th>Actions</th>
                <th>Tanggal</th>
                <th>PT / Kebun</th>
                <th>Permasalahan</th>
                <th>Status</th>
            </x-slot:head>

            <x-slot:body>
                @foreach($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary" />
                    <td class="whitespace-nowrap">{{ $table->masalah_tanggal?->format('d/m/Y') ?? '-' }}</td>
                    <td>
                        <span class="font-semibold">{{ $table->hasKebun?->kebun_nama ?? '-' }}</span>
                        @if($table->hasKebun?->hasDistrik)<span class="block text-[10px] text-on-surface-variant">{{ $table->hasKebun->hasDistrik->distrik_kode }} • {{ $table->hasKebun->kebun_kabupaten }}</span>@endif
                    </td>
                    <td class="max-w-md">
                        <div class="flex items-start gap-3">
                            @if($table->masalah_file)
                            <a href="{{ $table->file_url }}" target="_blank" class="shrink-0">
                                <img src="{{ $table->file_url }}" alt="Foto" class="w-14 h-14 rounded-lg object-cover border border-outline-variant hover:ring-2 hover:ring-primary/30 transition-all">
                            </a>
                            @endif
                            <div class="min-w-0">
                                <p class="font-medium">{{ \Illuminate\Support\Str::limit($table->masalah_permasalahan, 80) }}</p>
                                @if($table->masalah_rtl)<p class="text-[11px] text-on-surface-variant mt-0.5">RTL: {{ \Illuminate\Support\Str::limit($table->masalah_rtl, 80) }}</p>@endif
                            </div>
                        </div>
                    </td>
                    <td><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badge[$table->masalah_status] ?? $badge['open'] }}">{{ $table->status_label }}</span></td>
                </tr>
                @endforeach
            </x-slot:body>

            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @foreach($data as $table)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm" data-id="{{ $table->field_primary }}">
                        @if($table->masalah_file)
                        <a href="{{ $table->file_url }}" target="_blank" class="block mb-3 -mt-1 -mx-1">
                            <img src="{{ $table->file_url }}" alt="Foto" class="w-full h-40 rounded-lg object-cover border border-outline-variant">
                        </a>
                        @endif
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <p class="text-sm font-bold text-on-surface truncate">{{ $table->hasKebun?->kebun_nama ?? '-' }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0 {{ $badge[$table->masalah_status] ?? $badge['open'] }}">{{ $table->status_label }}</span>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mb-2">{{ $table->masalah_tanggal?->format('d M Y') }}</p>
                        <p class="text-xs text-on-surface mb-1">{{ $table->masalah_permasalahan }}</p>
                        @if($table->masalah_rtl)<p class="text-[11px] text-on-surface-variant">RTL: {{ $table->masalah_rtl }}</p>@endif
                        <div class="flex items-center justify-end pt-2 mt-2 border-t border-outline-variant/50">
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
