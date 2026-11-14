<?php /** @var App\Models\Surat $table */ ?>

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
                    <x-table-action :model="$model" :id="$table->field_primary">
                        @can('exportpdf', $model)
                        <a href="{{ moduleRoute('getExportPdf', ['id' => $table->field_primary]) }}" title="Export PDF" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-error/10 text-red-600 hover:bg-error/20 transition-colors">
                            <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                        </a>
                        @endcan
                        @can('exportdocx', $model)
                        <a href="{{ moduleRoute('getExportDocx', ['id' => $table->field_primary]) }}" title="Export DOCX" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-blue-600 hover:bg-primary/20 transition-colors">
                            <span class="material-symbols-outlined text-lg">description</span>
                        </a>
                        @endcan
                        @if($table->surat_file)
                        <a href="{{ $table->file_url }}" target="_blank" title="Download Scan" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-tertiary/10 text-green-600 hover:bg-tertiary/20 transition-colors">
                            <span class="material-symbols-outlined text-lg">download</span>
                        </a>
                        @endif
                    </x-table-action>
                    @foreach ($model::$sortColumns as $column)
                    <td>@if($column === 'surat_nomor')<span class="font-mono font-semibold">{{ $table->$column }}</span>@else{{ $table->$column }}@endif</td>
                    @endforeach
                </tr>
                @endforeach
            </x-slot:body>

            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @foreach($data as $table)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm" data-id="{{ $table->field_primary }}">
                        <p class="text-sm font-bold text-on-surface truncate mb-3"><span class="font-mono">{{ $table->surat_nomor ?? '-' }}</span></p>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Jenis</p>
                                <p class="text-xs font-medium text-primary">{{ $table->surat_jenis }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Tanggal</p>
                                <p class="text-xs font-medium text-primary">{{ formatDate($table->surat_tanggal) }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Perihal</p>
                                <p class="text-xs font-medium text-primary truncate">{{ $table->surat_perihal ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
                            <div class="flex gap-1" onclick="event.stopPropagation()">
                                @can('update', $model)
                                <a href="{{ moduleRoute('getUpdate', ['id' => $table->field_primary]) }}" wire:navigate title="Edit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </a>
                                @endcan
                                @can('exportpdf', $model)
                                <a href="{{ moduleRoute('getExportPdf', ['id' => $table->field_primary]) }}" title="Export PDF" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-error/10 text-red-600 hover:bg-error/20 transition-colors">
                                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                                </a>
                                @endcan
                                @can('exportdocx', $model)
                                <a href="{{ moduleRoute('getExportDocx', ['id' => $table->field_primary]) }}" title="Export DOCX" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-blue-600 hover:bg-primary/20 transition-colors">
                                    <span class="material-symbols-outlined text-lg">description</span>
                                </a>
                                @endcan
                                @if($table->surat_file)
                                <a href="{{ $table->file_url }}" target="_blank" title="Download Scan" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-tertiary/10 text-green-600 hover:bg-tertiary/20 transition-colors">
                                    <span class="material-symbols-outlined text-lg">download</span>
                                </a>
                                @endif
                                @can('delete', $model)
                                <a onclick="return confirm('Are you sure you want to delete?')" href="{{ moduleRoute('getDelete', ['id' => $table->field_primary]) }}" title="Delete" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </a>
                                @endcan
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
