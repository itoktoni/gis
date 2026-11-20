<?php /** @var App\Models\Lhp $model */ ?>
@php
    $existingRows = isset($model) && $model->exists
        ? $model->hasBlokRows()->with('hasBlok')->get()->mapWithKeys(fn ($r) => [$r->lhp_blok_id_blok => [
            'luas_panen' => $r->lhp_blok_luas_panen,
            'janjang_panen' => $r->lhp_blok_janjang_panen,
            'janjang_kirim' => $r->lhp_blok_janjang_kirim,
            'brondolan_panen' => $r->lhp_blok_brondolan_panen,
            'brondolan_kirim' => $r->lhp_blok_brondolan_kirim,
            'restan' => (bool) $r->lhp_blok_restan,
        ]])->all()
        : [];
@endphp

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-select col="6" name="lhp_id_kebun" label="Nama Kebun" :options="$kebunOptions" class="search" />
                <x-input col="6" name="lhp_tanggal" label="Tanggal" type="date" :value="old('lhp_tanggal', $model?->lhp_tanggal?->format('Y-m-d') ?? now()->toDateString())" />

                <x-input col="6" name="lhp_tk_hk" label="TK Panen (HK)" type="number" step="any" />
                <x-input col="6" name="lhp_produksi_panen_kg" label="Produksi Panen (Kg)" type="number" step="any" />
                <x-input col="6" name="lhp_produksi_kirim_kg" label="Produksi TBS Kirim (Kg)" type="number" step="any" />
                <div class="col-span-12 md:col-span-6"></div>

            @endbind
        </x-card>

        <x-card label="Rincian Panen per Blok" :noGrid="true">
            <p class="text-sm text-on-surface-variant mb-3">Pilih kebun untuk memuat daftar bloknya. Total luas, janjang, brondolan & restan dihitung otomatis ke header.</p>
            <div class="mb-3 flex gap-2">
                <input type="text" id="blok-search" placeholder="Cari kode / nama blok… (cth: A1)"
                    class="flex-1 rounded-lg border border-outline-variant bg-surface px-3 py-2 text-sm" />
                <button type="button" id="blok-search-clear" class="rounded-lg border border-outline-variant px-3 text-sm">✕</button>
            </div>
            <p class="text-xs text-on-surface-variant mb-2"><span id="blok-count">0</span> blok • <span id="blok-filled">0</span> terisi</p>
            <div id="blok-rows" class="space-y-2"></div>
            <div id="blok-empty" class="text-sm text-on-surface-variant">Pilih kebun dulu…</div>
            <div class="mt-3 grid grid-cols-2 sm:grid-cols-5 gap-2 text-sm">
                <div class="rounded-lg bg-surface-container px-3 py-2"><p class="text-[11px] text-on-surface-variant">Luas Panen</p><p class="font-bold" id="sum-luas">0</p></div>
                <div class="rounded-lg bg-surface-container px-3 py-2"><p class="text-[11px] text-on-surface-variant">Jjg Panen</p><p class="font-bold" id="sum-jj-panen">0</p></div>
                <div class="rounded-lg bg-surface-container px-3 py-2"><p class="text-[11px] text-on-surface-variant">Jjg Kirim</p><p class="font-bold" id="sum-jj-kirim">0</p></div>
                <div class="rounded-lg bg-surface-container px-3 py-2"><p class="text-[11px] text-on-surface-variant">Brondol (Kg)</p><p class="font-bold" id="sum-bro">0</p></div>
                <div class="rounded-lg bg-surface-container px-3 py-2"><p class="text-[11px] text-on-surface-variant">Restan</p><p class="font-bold" id="sum-restan">-</p></div>
            </div>

            {{-- Total header (readonly, dihitung dari rincian) --}}
            <div class="mt-3 grid grid-cols-12 gap-3">
                <div class="col-span-6 md:col-span-3"><label class="text-xs font-semibold">Luas Panen (Ha) — auto</label><input type="number" step="any" name="lhp_luas_panen" id="hdr-luas" readonly class="mt-1 w-full rounded-lg border border-outline-variant bg-surface-container px-3 py-2 text-sm" value="{{ old('lhp_luas_panen', $model?->lhp_luas_panen) }}"></div>
                <div class="col-span-6 md:col-span-3"><label class="text-xs font-semibold">Janjang Panen — auto</label><input type="number" step="any" name="lhp_janjang_panen" id="hdr-jj-panen" readonly class="mt-1 w-full rounded-lg border border-outline-variant bg-surface-container px-3 py-2 text-sm" value="{{ old('lhp_janjang_panen', $model?->lhp_janjang_panen) }}"></div>
                <div class="col-span-6 md:col-span-3"><label class="text-xs font-semibold">Janjang Kirim — auto</label><input type="number" step="any" name="lhp_janjang_kirim" id="hdr-jj-kirim" readonly class="mt-1 w-full rounded-lg border border-outline-variant bg-surface-container px-3 py-2 text-sm" value="{{ old('lhp_janjang_kirim', $model?->lhp_janjang_kirim) }}"></div>
                <div class="col-span-6 md:col-span-3"><label class="text-xs font-semibold">Brondolan (Kg) — auto</label><input type="number" step="any" name="lhp_brondolan_kg" id="hdr-bro" readonly class="mt-1 w-full rounded-lg border border-outline-variant bg-surface-container px-3 py-2 text-sm" value="{{ old('lhp_brondolan_kg', $model?->lhp_brondolan_kg) }}"></div>
                <div class="col-span-12"><label class="text-xs font-semibold">Blok Restan — auto</label><input type="text" name="lhp_blok_restan" id="hdr-restan" readonly class="mt-1 w-full rounded-lg border border-outline-variant bg-surface-container px-3 py-2 text-sm" value="{{ old('lhp_blok_restan', $model?->lhp_blok_restan) }}"></div>
            </div>
        </x-card>

        @if(config('lhp.up_enabled') || config('lhp.hujan_enabled'))
        <x-card label="UP & Curah Hujan">
            @bind($model ?? null)

                @if(config('lhp.up_enabled'))
                <x-input col="4" name="lhp_up_le10" label="UP Ha <= 10 Hari" type="number" step="any" />
                <x-input col="4" name="lhp_up_11_15" label="UP Ha 11 s.d 15 Hari" type="number" step="any" />
                <x-input col="4" name="lhp_up_gt15" label="UP Ha > 15 Hari" type="number" step="any" />
                @endif

                @if(config('lhp.hujan_enabled'))
                <x-input col="4" name="lhp_hujan_pagi" label="Pagi Jam 6 - 11 (mm)" type="number" step="any" />
                <x-input col="4" name="lhp_hujan_siang" label="Siang Jam 11 - 16 (mm)" type="number" step="any" />
                <x-input col="4" name="lhp_hujan_malam" label="Malam Jam 16 - 6 (mm)" type="number" step="any" />
                @endif

            @endbind
        </x-card>
        @endif

        <x-action :model="$model" :action="['save']" />
    </x-form>

    <script>
        (function () {
            var blokByKebun = @json($blokByKebun ?? []);
            var existing = @json($existingRows ?? []);
            var kebunSel = document.querySelector('[name="lhp_id_kebun"]');
            var box = document.getElementById('blok-rows');
            var empty = document.getElementById('blok-empty');
            if (!kebunSel || !box) return;

            function num(v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; }

            function recalc() {
                var sLuas = 0, sJp = 0, sJk = 0, sBp = 0, sBk = 0, restan = [], filled = 0;
                box.querySelectorAll('[data-blok]').forEach(function (row) {
                    var id = row.getAttribute('data-blok');
                    var g = function (n) { var el = row.querySelector('[name="blok[' + id + '][' + n + ']"]'); return el ? el.value : ''; };
                    var hasAny = g('luas_panen') !== '' || g('janjang_panen') !== '' || g('janjang_kirim') !== '' || g('brondolan_panen') !== '' || g('brondolan_kirim') !== '';
                    if (hasAny) filled++;
                    sLuas += num(g('luas_panen')); sJp += parseInt(g('janjang_panen')) || 0;
                    sJk += parseInt(g('janjang_kirim')) || 0;
                    sBp += num(g('brondolan_panen')); sBk += num(g('brondolan_kirim'));
                    var r = row.querySelector('[name="blok[' + id + '][restan]"]');
                    if (r && r.checked) restan.push(row.getAttribute('data-kode'));
                });
                setText('sum-luas', sLuas); setText('sum-jj-panen', sJp); setText('sum-jj-kirim', sJk);
                setText('sum-bro', sBp + sBk); setText('sum-restan', restan.join(', ') || '-');
                var cc = document.getElementById('blok-count'); if (cc) cc.textContent = box.querySelectorAll('[data-blok]').length;
                var cf = document.getElementById('blok-filled'); if (cf) cf.textContent = filled;
                setVal('hdr-luas', sLuas || ''); setVal('hdr-jj-panen', sJp || '');
                setVal('hdr-jj-kirim', sJk || ''); setVal('hdr-bro', (sBp + sBk) || '');
                setVal('hdr-restan', restan.join(', '));
            }
            function setText(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; }
            function setVal(id, v) { var el = document.getElementById(id); if (el) el.value = v; }

            function render() {
                var kid = kebunSel.value;
                var list = (blokByKebun && blokByKebun[kid]) || [];
                box.innerHTML = '';
                if (!list.length) { empty.style.display = ''; recalc(); return; }
                empty.style.display = 'none';
                list.forEach(function (b) {
                    var ex = existing[b.id] || {};
                    var row = document.createElement('div');
                    row.className = 'grid grid-cols-12 gap-2 items-end rounded-xl border border-outline-variant p-3';
                    row.setAttribute('data-blok', b.id);
                    row.setAttribute('data-kode', b.kode);
                    row.setAttribute('data-nama', (b.nama || '').toLowerCase());
                    row.innerHTML =
                        '<div class="col-span-12"><p class="font-bold text-primary">' + b.kode + ' <span class="font-normal text-on-surface-variant text-xs">— ' + b.nama + '</span></p></div>' +
                        fld(b.id, 'luas_panen', 'Luas (Ha)', ex.luas_panen) +
                        fld(b.id, 'janjang_panen', 'Jjg Panen', ex.janjang_panen) +
                        fld(b.id, 'janjang_kirim', 'Jjg Kirim', ex.janjang_kirim) +
                        fld(b.id, 'brondolan_panen', 'Brd Panen (Kg)', ex.brondolan_panen) +
                        fld(b.id, 'brondolan_kirim', 'Brd Kirim (Kg)', ex.brondolan_kirim) +
                        '<div class="col-span-6 sm:col-span-2"><label class="text-xs">Restan</label><br>' +
                        '<input type="checkbox" name="blok[' + b.id + '][restan]" value="1" class="mt-1 h-6 w-6 accent-red-600"' + (ex.restan ? ' checked' : '') + '></div>';
                    box.appendChild(row);
                });
                box.querySelectorAll('input').forEach(function (el) {
                    el.addEventListener('input', recalc);
                    el.addEventListener('change', recalc);
                });
                recalc();
                applySearch();
            }
            function fld(id, name, label, val) {
                return '<div class="col-span-6 sm:col-span-2"><label class="text-xs">' + label + '</label>' +
                    '<input type="number" step="any" min="0" name="blok[' + id + '][' + name + ']" value="' + (val ?? '') + '" class="mt-1 w-full rounded-lg border border-outline-variant px-2 py-1.5 text-sm"></div>';
            }

            kebunSel.addEventListener('change', function () { existing = {}; render(); });
            var searchEl = document.getElementById('blok-search');
            var clearEl = document.getElementById('blok-search-clear');
            function applySearch() {
                var q = ((searchEl && searchEl.value) || '').trim().toLowerCase();
                box.querySelectorAll('[data-blok]').forEach(function (row) {
                    var hay = ((row.getAttribute('data-kode') || '') + ' ' + (row.getAttribute('data-nama') || '')).toLowerCase();
                    row.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
                });
            }
            if (searchEl) searchEl.addEventListener('input', applySearch);
            if (clearEl) clearEl.addEventListener('click', function () { if (searchEl) searchEl.value = ''; applySearch(); if (searchEl) searchEl.focus(); });
            render();
        })();
    </script>
</x-layouts::app>
