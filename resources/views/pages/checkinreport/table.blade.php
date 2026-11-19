<?php /** @var App\Models\Checkin $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => moduleLabel()]]" />
    <div class="content mt-4 mb-4 lg:mt-0">
        {{-- Filters --}}
        <form method="GET" action="{{ moduleRoute('getTable') }}">
            <x-card label="Filter Report Check-In">
                <x-select col="3" name="user_id" label="User" :options="$userOptions" :default="request('user_id')" placeholder="-- Semua User --" />
                <x-select col="3" name="status" label="Status" :options="$statusOptions" :default="request('status')" placeholder="-- Semua Status --" />
                <x-input col="3" type="date" name="from" label="Dari Tanggal" :value="request('from')" />
                <x-input col="3" type="date" name="to" label="Sampai Tanggal" :value="request('to')" />
            </x-card>

            <div class="flex flex-wrap gap-2 mt-4">
                <button type="submit" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg bg-primary px-4 text-sm font-semibold text-white transition-all hover:brightness-110">
                    <span class="material-symbols-outlined text-xl">visibility</span> Tampilkan
                </button>
                <button type="button" onclick="setPreset('today')" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">HARI</button>
                <button type="button" onclick="setPreset('week')" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">MGGU</button>
                <button type="button" onclick="setPreset('month')" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">BLN</button>
                <a href="{{ moduleRoute('getTable', ['all' => 1]) }}" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl">restart_alt</span> Semua
                </a>
                <a href="{{ moduleRoute('getSummary', array_filter(['from' => request('from'), 'to' => request('to'), 'user_id' => request('user_id')])) }}" wire:navigate class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl">summarize</span> Summary
                </a>
                <a href="{{ moduleRoute('getPrint', array_filter(['from' => request('from'), 'to' => request('to'), 'user_id' => request('user_id'), 'status' => request('status')])) }}" target="_blank" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl">print</span> Print
                </a>
                <a href="{{ moduleRoute('getExportExcel', array_filter(['from' => request('from'), 'to' => request('to'), 'user_id' => request('user_id'), 'status' => request('status')])) }}" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl">download</span> Excel
                </a>
            </div>
        </form>

        <script>
            function setPreset(mode) {
                var now = new Date();
                var pad = function (n) { return (n < 10 ? '0' : '') + n; };
                var fmt = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
                var from, to = fmt(now);
                if (mode === 'today') { from = to; }
                else if (mode === 'week') {
                    var day = (now.getDay() + 6) % 7; // Senin = 0
                    var mon = new Date(now); mon.setDate(now.getDate() - day);
                    from = fmt(mon);
                } else if (mode === 'month') {
                    from = fmt(now).slice(0, 7) + '-01';
                }
                var f = document.querySelector('input[name="from"]');
                var t = document.querySelector('input[name="to"]');
                if (f) f.value = from;
                if (t) t.value = to;
                f.closest('form').submit();
            }
        </script>

        <br>

        {{-- Table --}}
        @php
            $currentSort = request('sort.0', '');
            $sortField = str_replace(':desc','',str_replace(':asc','',$currentSort));
            $sortDir = str_contains($currentSort, ':desc') ? 'desc' : 'asc';
            $fmtStatus = function ($s) {
                return $s === 'invalid' ? 'DI LUAR JANGKAUAN' : ($s === 'fake' ? 'PALSU' : 'VALID');
            };
            $fmtSelisih = function ($v) {
                if ($v === null) return '-';
                $v = (float) $v;
                return ($v > 0 ? '+' : '').round($v).' m';
            };
        @endphp

        <x-table>
            <x-slot:head>
                <th>Act</th>
                <th>Foto</th>
                <x-table-sort field="created_at" label="Tanggal" :sortField="$sortField" :sortDir="$sortDir" />
                <th>User</th>
                <x-table-sort field="status" label="Status" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="selisih_radius_m" label="Selisih Radius" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="address" label="Alamat" :sortField="$sortField" :sortDir="$sortDir" />
            </x-slot:head>

            <x-slot:body>
                @foreach($data as $table)
                <tr class="report-row cursor-pointer hover:bg-surface-container" data-photo="{{ $table->photo_path ? Storage::url($table->photo_path) : '' }}" data-address="{{ $table->address ?? '-' }}">
                    <td class="w-15 whitespace-nowrap" onclick="event.stopPropagation()">
                        <div class="flex gap-2">
                            <a href="{{ moduleRoute('getShow', ['id' => $table->field_primary]) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors" title="Detail">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </a>
                        </div>
                    </td>
                    <td>
                        @if($table->photo_path)
                        <img src="{{ Storage::url($table->photo_path) }}" data-full="{{ Storage::url($table->photo_path) }}" class="photo-thumb w-12 h-12 rounded-lg object-cover cursor-zoom-in hover:opacity-80" alt="foto" loading="lazy">
                        @else
                        <span class="text-on-surface-variant">-</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap">{{ $table->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $table->hasUser?->name ?? '-' }}</td>
                    <td>
                        <x-badge :type="$table->status === 'valid' ? 'success' : ($table->status === 'invalid' ? 'warning' : 'error')">{{ $fmtStatus($table->status) }}</x-badge>
                    </td>
                    <td class="whitespace-nowrap {{ $table->selisih_radius_m !== null && $table->selisih_radius_m > 0 ? 'text-red-600 font-semibold' : '' }}">{{ $fmtSelisih($table->selisih_radius_m) }}</td>
                    <td class="max-w-xs truncate" title="{{ $table->address }}">{{ $table->address ?? '-' }}</td>
                </tr>
                @endforeach
            </x-slot:body>

            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data"/>
                <div class="p-3 space-y-3" id="mBody">
                    @foreach($data as $table)
                    <div class="report-card border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm cursor-pointer" data-id="{{ $table->field_primary }}" data-photo="{{ $table->photo_path ? Storage::url($table->photo_path) : '' }}" data-address="{{ $table->hasUser?->name ?? '' }} — {{ $table->address ?? '-' }}">
                        <div class="flex items-center gap-3 mb-3">
                            @if($table->photo_path)
                            <img src="{{ Storage::url($table->photo_path) }}" data-full="{{ Storage::url($table->photo_path) }}" class="photo-thumb w-12 h-12 rounded-xl object-cover cursor-zoom-in" alt="foto" loading="lazy">
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-on-surface truncate">{{ $table->hasUser?->name ?? '-' }}</p>
                                <p class="text-[11px] text-on-surface-variant">{{ $table->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                            </div>
                            <x-badge class="ml-auto" :type="$table->status === 'valid' ? 'success' : ($table->status === 'invalid' ? 'warning' : 'error')">{{ $fmtStatus($table->status) }}</x-badge>
                        </div>
                        <p class="text-xs text-on-surface-variant line-clamp-2 mb-3">{{ $table->address ?? '-' }}</p>
                        <p class="text-xs mb-3">Selisih radius: <span class="font-semibold {{ $table->selisih_radius_m !== null && $table->selisih_radius_m > 0 ? 'text-red-600' : '' }}">{{ $fmtSelisih($table->selisih_radius_m) }}</span></p>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
                            <a href="{{ moduleRoute('getShow', ['id' => $table->field_primary]) }}" wire:navigate class="inline-flex items-center gap-1 h-9 px-3 rounded-lg bg-primary/10 text-primary text-xs font-semibold">
                                <span class="material-symbols-outlined text-base">visibility</span> Detail
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-slot:mobile>

        </x-table>

        <x-pagination :paginator="$data" />

    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>

    {{-- Popup foto + alamat (lightbox) --}}
    <div id="photo-popup" class="hidden fixed inset-0 z-[100] overflow-y-auto bg-black/80 p-4 cursor-zoom-out">
        <div class="min-h-full flex items-center justify-center">
            <div class="max-w-3xl w-full" onclick="event.stopPropagation()">
                <img id="photo-popup-img" src="" class="w-full max-h-[70vh] object-contain rounded-xl shadow-2xl bg-black" alt="foto check-in" />
                <p id="photo-popup-addr" class="mt-2 text-sm text-white/90 text-center"></p>
                <p class="mt-1 text-xs text-white/60 text-center">Klik di luar gambar untuk menutup</p>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var popup = document.getElementById('photo-popup');
            var popupImg = document.getElementById('photo-popup-img');
            var popupAddr = document.getElementById('photo-popup-addr');
            if (!popup || !popupImg) return;
            function openPopup(src, addr) {
                if (!src) return;
                document.body.style.overflow = 'hidden';
                popupImg.src = src;
                if (popupAddr) popupAddr.textContent = addr || '';
                popup.classList.remove('hidden');
            }
            function closePopup() {
                document.body.style.overflow = '';
                popup.classList.add('hidden');
                popupImg.src = '';
            }
            document.addEventListener('click', function (e) {
                var thumb = e.target.closest ? e.target.closest('.photo-thumb') : null;
                if (thumb && thumb.dataset.full) {
                    var row = thumb.closest('tr');
                    openPopup(thumb.dataset.full, row ? row.dataset.address : '');
                    return;
                }
                var row = e.target.closest ? (e.target.closest('tr.report-row') || e.target.closest('.report-card')) : null;
                if (row && !e.target.closest('a,button')) {
                    openPopup(row.dataset.photo, (row.dataset.address || '') + '');
                    return;
                }
                if (!popup.classList.contains('hidden') && e.target.closest('#photo-popup')) {
                    closePopup();
                }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !popup.classList.contains('hidden')) closePopup();
            });
        })();
    </script>
</x-layouts::app>
