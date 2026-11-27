<?php /** @var App\Models\Checkin $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => moduleLabel()]]" />
    <div class="content mt-4 mb-4 lg:mt-0">
        {{-- Filters --}}
        <form method="GET" action="{{ moduleRoute('getTable') }}">
            <x-card label="Filter Report Check-In">
                <x-select col="3" name="user_id" label="User" :options="$userOptions" :default="request('user_id')" placeholder="-- Semua User --" />
                <x-select col="3" name="jenis" label="Jenis" :options="$jenisOptions" :default="request('jenis')" placeholder="-- Semua Jenis --" />
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
                <a href="{{ moduleRoute('getSummary', array_filter(['from' => request('from'), 'to' => request('to'), 'user_id' => request('user_id'), 'jenis' => request('jenis')])) }}" wire:navigate class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl">summarize</span> Summary
                </a>
                <a href="{{ moduleRoute('getPrint', array_filter(['from' => request('from'), 'to' => request('to'), 'user_id' => request('user_id'), 'status' => request('status'), 'jenis' => request('jenis')])) }}" target="_blank" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl">print</span> Print
                </a>
                <a href="{{ moduleRoute('getExportPdf', array_filter(['from' => request('from'), 'to' => request('to'), 'user_id' => request('user_id'), 'status' => request('status'), 'jenis' => request('jenis')])) }}" target="_blank" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">
                    <span class="material-symbols-outlined text-xl">picture_as_pdf</span> PDF
                </a>
                <a href="{{ moduleRoute('getExportExcel', array_filter(['from' => request('from'), 'to' => request('to'), 'user_id' => request('user_id'), 'status' => request('status'), 'jenis' => request('jenis')])) }}" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-outline-variant px-4 text-sm font-semibold text-on-surface-variant transition-all hover:bg-surface-container">
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
                return formatJarak($v, true);
            };
        @endphp

        <x-table>
            <x-slot:head>
                <x-table-checkbox :model="$model" onchange="toggleAll(this)" />
                <th>Act</th>
                <th>Foto</th>
                <x-table-sort field="created_at" label="Tanggal" :sortField="$sortField" :sortDir="$sortDir" />
                <th>User</th>
                <th>Jenis</th>
                <x-table-sort field="status" label="Status" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="selisih_radius_m" label="Selisih Radius" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="address" label="Alamat" :sortField="$sortField" :sortDir="$sortDir" />
            </x-slot:head>

            <x-slot:body>
                @foreach($data as $table)
                <tr class="report-row cursor-pointer hover:bg-surface-container" data-photo="{{ $table->photo_path ? Storage::url($table->photo_path) : '' }}" data-address="{{ $table->address ?? '-' }}">
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <td class="w-15 whitespace-nowrap" onclick="event.stopPropagation()">
                        <div class="flex gap-2">
                            <a href="{{ moduleRoute('getShow', ['id' => $table->field_primary]) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors" title="Detail">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </a>
                            @can('delete', $model)
                            <a onclick="return confirm('Hapus check-in ini beserta fotonya?')" href="{{ moduleRoute('getDelete', ['id' => $table->field_primary]) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-error/10 text-error hover:bg-error/20 transition-colors" title="Hapus + foto">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </a>
                            @endcan
                        </div>
                    </td>
                    <td>
                        @if($table->photo_path)
                        <img src="{{ Storage::url($table->photo_path) }}" data-full="{{ Storage::url($table->photo_path) }}" class="photo-thumb w-12 h-12 rounded-lg object-cover cursor-zoom-in hover:opacity-80" alt="foto check-in {{ $table->hasUser?->name }}" loading="lazy" data-cap-user="{{ $table->hasUser?->name ?? '-' }}" data-cap-date="{{ $table->created_at?->format('d/m/Y H:i') ?? '-' }}" data-cap-jenis="{{ $table->kunjungan_id ? 'Kunjungan'.(($table->hasKunjungan?->kunjungan_nama ?? '') !== '' ? ' • '.$table->hasKunjungan->kunjungan_nama : '') : 'Checkin' }}" data-cap-status="{{ $fmtStatus($table->status) }}" data-cap-selisih="{{ $fmtSelisih($table->selisih_radius_m) }}" data-cap-alamat="{{ $table->address ?? '-' }}">
                        @else
                        <span class="text-on-surface-variant">-</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap">{{ $table->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $table->hasUser?->name ?? '-' }}</td>
                    <td>
                        @if($table->kunjungan_id)<x-badge type="info">Kunjungan</x-badge>@if($table->hasKunjungan?->kunjungan_nama)<span class="block text-[10px] text-on-surface-variant">{{ $table->hasKunjungan->kunjungan_nama }}</span>@endif@else<x-badge type="soft">Checkin</x-badge>@endif
                    </td>
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
                            <img src="{{ Storage::url($table->photo_path) }}" data-full="{{ Storage::url($table->photo_path) }}" class="photo-thumb w-12 h-12 rounded-xl object-cover cursor-zoom-in" alt="foto check-in {{ $table->hasUser?->name }}" loading="lazy" data-cap-user="{{ $table->hasUser?->name ?? '-' }}" data-cap-date="{{ $table->created_at?->format('d/m/Y H:i') ?? '-' }}" data-cap-jenis="{{ $table->kunjungan_id ? 'Kunjungan'.(($table->hasKunjungan?->kunjungan_nama ?? '') !== '' ? ' • '.$table->hasKunjungan->kunjungan_nama : '') : 'Checkin' }}" data-cap-status="{{ $fmtStatus($table->status) }}" data-cap-selisih="{{ $fmtSelisih($table->selisih_radius_m) }}" data-cap-alamat="{{ $table->address ?? '-' ?>">
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-on-surface truncate">{{ $table->hasUser?->name ?? '-' }}</p>
                                <p class="text-[11px] text-on-surface-variant">{{ $table->created_at?->format('d/m/Y H:i') ?? '-' }} &middot; {{ $table->kunjungan_id ? 'Kunjungan'.($table->hasKunjungan?->kunjungan_nama ? ' • '.$table->hasKunjungan->kunjungan_nama : '') : 'Checkin' }}</p>
                            </div>
                            <x-badge class="ml-auto" :type="$table->status === 'valid' ? 'success' : ($table->status === 'invalid' ? 'warning' : 'error')">{{ $fmtStatus($table->status) }}</x-badge>
                        </div>
                        <p class="text-xs text-on-surface-variant line-clamp-2 mb-3">{{ $table->address ?? '-' }}</p>
                        <p class="text-xs mb-3">Selisih radius: <span class="font-semibold {{ $table->selisih_radius_m !== null && $table->selisih_radius_m > 0 ? 'text-red-600' : '' }}">{{ $fmtSelisih($table->selisih_radius_m) }}</span></p>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
                            <div class="flex gap-2">
                                <a href="{{ moduleRoute('getShow', ['id' => $table->field_primary]) }}" wire:navigate class="inline-flex items-center gap-1 h-9 px-3 rounded-lg bg-primary/10 text-primary text-xs font-semibold">
                                    <span class="material-symbols-outlined text-base">visibility</span> Detail
                                </a>
                                @can('delete', $model)
                                <a onclick="return confirm('Hapus check-in ini beserta fotonya?')" href="{{ moduleRoute('getDelete', ['id' => $table->field_primary]) }}" class="inline-flex items-center gap-1 h-9 px-3 rounded-lg bg-error/10 text-error text-xs font-semibold">
                                    <span class="material-symbols-outlined text-base">delete</span> Hapus
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
        <x-action :model="$model" :action="['delete']"/>

    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>

    {{-- PhotoSwipe lightbox — zoom/pan + tombol download foto (untuk validasi) --}}
    <link rel="stylesheet" href="{{ asset('vendor/photoswipe/photoswipe.css') }}">
    <style>
        .pswp__custom-caption {
            position: absolute; left: 0; right: 0; bottom: 0; z-index: 160;
            padding: 16px 18px 14px; color: #fff; font-size: 13px; line-height: 1.55;
            background: linear-gradient(to top, rgba(0,0,0,.85), rgba(0,0,0,.4) 65%, transparent);
            pointer-events: none; text-align: left;
        }
        .pswp__custom-caption b { font-size: 14px; }
        .pswp__download-btn { cursor: pointer; }
    </style>
    <script src="{{ asset('vendor/photoswipe/photoswipe.umd.min.js') }}"></script>
    <script src="{{ asset('vendor/photoswipe/photoswipe-lightbox.umd.min.js') }}"></script>
    <script>
        (function () {
            if (window.__checkinPswpInit) return;
            window.__checkinPswpInit = true;

            function esc(s) {
                var d = document.createElement('div');
                d.textContent = (s == null ? '' : String(s));
                return d.innerHTML;
            }

            function captionHtml(cap) {
                return '<div><b>' + esc(cap.user) + '</b> &nbsp;&middot;&nbsp; ' + esc(cap.date) + '</div>'
                    + '<div>' + esc(cap.jenis) + ' &nbsp;&middot;&nbsp; Status: ' + esc(cap.status)
                    + ' &nbsp;&middot;&nbsp; Selisih: ' + esc(cap.selisih) + '</div>'
                    + '<div style="opacity:.9">' + esc(cap.alamat) + '</div>';
            }

            // Kumpulkan foto unik dari semua thumbnail (desktop + mobile duplikat).
            function collect() {
                var all = Array.prototype.slice.call(document.querySelectorAll('img.photo-thumb[data-full]'));
                var groups = {};
                all.forEach(function (el) {
                    var url = el.getAttribute('data-full');
                    if (!url) return;
                    (groups[url] = groups[url] || []).push(el);
                });

                var items = [];
                var byUrl = {};
                Object.keys(groups).forEach(function (url) {
                    var els = groups[url];
                    var host = els[0], w = 0, h = 0, vis = null;
                    for (var i = 0; i < els.length; i++) {
                        if (els[i].naturalWidth) { host = els[i]; w = els[i].naturalWidth; h = els[i].naturalHeight; break; }
                    }
                    for (var j = 0; j < els.length; j++) {
                        var r = els[j].getBoundingClientRect();
                        if (r.width > 0 && r.height > 0) { vis = els[j]; break; }
                    }
                    var cap = {
                        user: host.getAttribute('data-cap-user') || '-',
                        date: host.getAttribute('data-cap-date') || '-',
                        jenis: host.getAttribute('data-cap-jenis') || '-',
                        status: host.getAttribute('data-cap-status') || '-',
                        selisih: host.getAttribute('data-cap-selisih') || '-',
                        alamat: host.getAttribute('data-cap-alamat') || '-'
                    };
                    byUrl[url] = items.length;
                    items.push({
                        src: url,
                        w: w,
                        h: h,
                        msrc: url,
                        element: vis || host,
                        alt: cap.user + ' — ' + cap.date,
                        cap: cap,
                        captionHtml: captionHtml(cap)
                    });
                });

                return { items: items, byUrl: byUrl };
            }

            var lightbox = null;
            function ensureLightbox() {
                if (lightbox) return lightbox;

                lightbox = new PhotoSwipeLightbox({
                    pswpModule: PhotoSwipe,
                    arrowKeys: true,
                    escKey: true,
                    returnFocus: true,
                    counter: true,
                    zoom: true,
                    wheelToZoom: true,
                    close: true,
                    preload: [1, 2]
                });

                lightbox.on('uiRegister', function () {
                    // Tombol download foto (bukti check-in) di toolbar atas.
                    lightbox.pswp.ui.registerElement({
                        name: 'download-button',
                        ariaLabel: 'Download foto',
                        className: 'pswp__download-btn',
                        order: 8,
                        isButton: true,
                        html: '<svg aria-hidden="true" class="pswp__icn" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
                        onClick: function () {
                            var data = lightbox.pswp.currSlide.data;
                            var src = data.src || '';
                            var ext = (src.split('?')[0].split('.').pop() || 'jpg').toLowerCase();
                            var safe = function (s) {
                                return String(s || '').trim().replace(/[^a-zA-Z0-9-_]+/g, '-').replace(/^-+|-+$/g, '') || 'foto';
                            };
                            var name = 'checkin-' + safe(data.cap && data.cap.user) + '-' + safe(data.cap && data.cap.date) + '.' + ext;
                            var a = document.createElement('a');
                            a.href = src;
                            a.download = name;
                            a.rel = 'noopener';
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                        }
                    });

                    // Caption (info validasi) di bagian bawah foto.
                    lightbox.pswp.ui.registerElement({
                        name: 'custom-caption',
                        className: 'pswp__custom-caption',
                        appendTo: 'wrapper',
                        order: 9,
                        onInit: function (el, pswp) {
                            var render = function () {
                                el.innerHTML = (pswp.currSlide && pswp.currSlide.data.captionHtml) || '';
                            };
                            pswp.on('change', render);
                            render();
                        }
                    });
                });

                return lightbox;
            }

            function open(url) {
                if (!url || !window.PhotoSwipe || !window.PhotoSwipeLightbox) return;
                var c = collect();
                if (!c.items.length) return;
                var idx = Object.prototype.hasOwnProperty.call(c.byUrl, url) ? c.byUrl[url] : 0;

                // Pastikan dimensi tiap foto diketahui agar zoom/fit akurat.
                var need = c.items.filter(function (it) { return !it.w; });
                function go() { ensureLightbox().loadAndOpen(idx, c.items); }
                if (!need.length) { go(); return; }

                var pending = need.length;
                need.forEach(function (it) {
                    var im = new Image();
                    im.onload = im.onerror = function () {
                        it.w = im.naturalWidth || 1200;
                        it.h = im.naturalHeight || 1600;
                        if (--pending === 0) go();
                    };
                    im.src = it.src;
                });
            }

            document.addEventListener('click', function (e) {
                var t = e.target;
                if (!t || !t.closest) return;
                if (t.closest('a,button,input,label,select,textarea')) return;

                var thumb = t.closest('img.photo-thumb[data-full]');
                if (thumb && thumb.getAttribute('data-full')) {
                    open(thumb.getAttribute('data-full'));
                    return;
                }
                var row = t.closest('tr.report-row') || t.closest('.report-card');
                if (row && row.getAttribute('data-photo')) {
                    open(row.getAttribute('data-photo'));
                }
            });
        })();
    </script>
</x-layouts::app>
