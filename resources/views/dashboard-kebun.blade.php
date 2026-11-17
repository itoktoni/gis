<x-layouts::app title="Dashboard Kebun">
    <div>
        <div class="mb-6 space-y-3">
            <h2 class="text-2xl font-bold text-on-surface">Dashboard Kebun</h2>
            <form method="GET" action="{{ route('dashboard-kebun') }}" class="flex flex-nowrap items-center gap-2 overflow-x-auto py-1">
                <select name="distrik_id" id="f-distrik" onchange="this.form.submit()" class="h-9 shrink-0 px-2 bg-white border border-outline-variant rounded-lg text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary max-w-36">
                    <option value="">Semua Distrik</option>
                    @foreach($distrikOptions as $id => $nama)
                    <option value="{{ $id }}" {{ (string) $stats['distrik_id'] === (string) $id ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
                <select name="kebun_id" id="f-kebun" onchange="this.form.submit()" style="width:200px;max-width:200px" class="h-9 shrink-0 px-2 bg-white border border-outline-variant rounded-lg text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary truncate">
                    <option value="">Semua Kebun</option>
                    @foreach($kebunOptions as $k)
                    <option value="{{ $k['id'] }}" data-distrik="{{ $k['distrik'] }}" {{ (string) $stats['kebun_id'] === (string) $k['id'] ? 'selected' : '' }}>{{ $k['nama'] }}</option>
                    @endforeach
                </select>
                <div class="flex gap-1 rounded-lg bg-surface-container-low p-1 shrink-0" role="tablist" aria-label="Rentang">
                    @foreach(['mingguan' => 'Mingguan (7 hari)', 'bulanan' => 'Bulanan (30 hari)', 'tahunan' => 'Tahunan (365 hari)'] as $key => $label)
                    <a href="{{ route('dashboard-kebun', array_merge(request()->except(['dari', 'sampai', 'range']), ['range' => $key])) }}" wire:navigate class="px-3 py-1.5 text-sm font-semibold rounded-md whitespace-nowrap {{ $stats['range'] === $key ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-high' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <label class="text-xs font-semibold text-on-surface-variant uppercase shrink-0">Dari</label>
                <input type="date" name="dari" value="{{ $stats['dari'] }}" max="{{ now()->toDateString() }}" onchange="this.form.submit()" class="h-9 shrink-0 w-36 px-2 bg-white border border-outline-variant rounded-lg text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                <label class="text-xs font-semibold text-on-surface-variant uppercase shrink-0">Sampai</label>
                <input type="date" name="sampai" value="{{ $stats['sampai'] }}" max="{{ now()->toDateString() }}" onchange="this.form.submit()" class="h-9 shrink-0 w-36 px-2 bg-white border border-outline-variant rounded-lg text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                <input type="hidden" name="range" value="{{ $stats['range'] }}">
            </form>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-success">local_shipping</span>
                    </div>
                    <span class="text-xs font-semibold text-on-surface-variant uppercase">Tonase Kirim</span>
                </div>
                <span class="text-2xl font-bold text-success">{{ number_format($stats['tonase_kirim'], 2, ',', '.') }}</span>
                <span class="text-xs text-on-surface-variant">ton TBS</span>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary">scale</span>
                    </div>
                    <span class="text-xs font-semibold text-on-surface-variant uppercase">Tonase Panen</span>
                </div>
                <span class="text-2xl font-bold text-primary">{{ number_format($stats['tonase_panen'], 2, ',', '.') }}</span>
                <span class="text-xs text-on-surface-variant">ton TBS</span>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-warning">agriculture</span>
                    </div>
                    <span class="text-xs font-semibold text-on-surface-variant uppercase">Kebun Lapor</span>
                </div>
                <span class="text-2xl font-bold">{{ $stats['kebun_lapor'] }}<span class="text-sm text-on-surface-variant font-normal"> / {{ $stats['total_kebun'] }}</span></span>
                <span class="text-xs text-on-surface-variant block">kebun</span>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-info">monitoring</span>
                    </div>
                    <span class="text-xs font-semibold text-on-surface-variant uppercase">Capaian vs RKAP</span>
                </div>
                <span class="text-2xl font-bold text-info">{{ number_format($stats['capaian'], 1, ',', '.') }}%</span>
                <span class="text-xs text-on-surface-variant">realisasi ÷ estimasi</span>
            </div>
        </div>

        {{-- Leaderboard: 2 kolom terpisah --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card min-w-0 overflow-hidden">
                <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">leaderboard</span>
                    Leaderboard Distrik
                </h3>
                @if($board->isNotEmpty())
                <div class="space-y-4">
                    @foreach($board as $i => $row)
                    @php $pct = $maxBoardKg > 0 ? round($row->kg / $maxBoardKg * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-6 h-6 rounded-full text-xs font-bold flex items-center justify-center shrink-0 {{ $i === 0 ? 'bg-amber-400 text-amber-950' : ($i === 1 ? 'bg-slate-300 text-slate-800' : ($i === 2 ? 'bg-amber-700/70 text-white' : 'bg-surface-container text-on-surface-variant')) }}">{{ $i + 1 }}</span>
                            <span class="text-sm font-semibold text-on-surface truncate">{{ $row->nama }}</span>
                            <span class="ml-auto text-sm font-bold text-primary whitespace-nowrap">{{ number_format($row->kg / 1000, 2, ',', '.') }} ton</span>
                        </div>
                        <div class="h-2 rounded-full bg-surface-container overflow-hidden ml-8">
                            <div class="h-full rounded-full bg-primary" style="width: {{ $pct }}%"></div>
                        </div>
                        <p class="text-[11px] text-on-surface-variant ml-8 mt-0.5">{{ $row->laporan }} laporan • panen {{ number_format($row->panen_kg / 1000, 2, ',', '.') }} ton</p>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 text-on-surface-variant">
                    <span class="material-symbols-outlined text-4xl mb-2 block">leaderboard</span>
                    <p class="text-sm">Belum ada data periode ini.</p>
                </div>
                @endif
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card min-w-0 overflow-hidden">
                <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">agriculture</span>
                    Leaderboard Kebun
                </h3>
                @if($kebunBoard->isNotEmpty())
                <div class="space-y-3" style="max-height:400px;overflow-y:auto;margin-left:8px;margin-right:4px;padding-right:12px">
                    @foreach($kebunBoard as $i => $row)
                    @php $pct = $maxKebunKg > 0 ? round($row->kg / $maxKebunKg * 100) : 0; @endphp
                    <div class="rounded-xl bg-surface-container px-3 py-2.5">
                        <div class="flex items-start gap-2">
                            <span class="w-6 h-6 rounded-full text-xs font-bold flex items-center justify-center shrink-0 mt-0.5 {{ $i === 0 ? 'bg-amber-400 text-amber-950' : ($i === 1 ? 'bg-slate-300 text-slate-800' : ($i === 2 ? 'bg-amber-700/70 text-white' : 'bg-surface-container-high text-on-surface-variant')) }}">{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-on-surface leading-snug break-words">{{ $row->nama }}</p>
                                <p class="text-[11px] text-on-surface-variant">{{ $row->distrik }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-1.5 ml-8">
                            <div class="h-2 rounded-full overflow-hidden flex-1" style="background:#e2e8f0">
                                <div class="h-full rounded-full" style="width: {{ $pct }}%;background:#16a34a"></div>
                            </div>
                            <span class="text-xs font-bold text-on-surface whitespace-nowrap">{{ number_format($row->kg / 1000, 2, ',', '.') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 text-on-surface-variant">
                    <span class="material-symbols-outlined text-4xl mb-2 block">agriculture</span>
                    <p class="text-sm">Belum ada data periode ini.</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Pencapaian --}}
        <h3 class="font-headline-md text-headline-md text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">monitoring</span>
            Pencapaian
        </h3>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card min-w-0 overflow-hidden mb-5">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">trending_up</span>
                Total Tonase per Hari
            </h3>
            <div class="min-w-0">
                {!! $tonaseChart->container() !!}
            </div>
        </div>

        {{-- Estimasi vs Realisasi dengan tab --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card min-w-0 overflow-hidden mb-5">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-4 mb-4 border-b border-outline-variant">
                <h3 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">compare_arrows</span>
                    Yield per Ha (Kg/Ha)
                </h3>
                <div class="flex gap-1 rounded-lg bg-surface-container p-1" role="tablist">
                    <button type="button" data-est-tab="mingguan" class="est-tab-btn px-4 py-1.5 text-sm font-semibold rounded-md bg-primary text-on-primary">Mingguan</button>
                    <button type="button" data-est-tab="bulanan" class="est-tab-btn px-4 py-1.5 text-sm font-semibold rounded-md text-on-surface-variant hover:bg-surface-container-high">Bulanan</button>
                    <button type="button" data-est-tab="tahunan" class="est-tab-btn px-4 py-1.5 text-sm font-semibold rounded-md text-on-surface-variant hover:bg-surface-container-high">Tahunan</button>
                </div>
            </div>
            <div id="est-mingguan" class="est-tab-panel min-w-0">
                {!! $estMingguan->container() !!}
            </div>
            <div id="est-bulanan" class="est-tab-panel min-w-0 hidden">
                {!! $estBulanan->container() !!}
            </div>
            <div id="est-tahunan" class="est-tab-panel min-w-0 hidden">
                {!! $estTahunan->container() !!}
            </div>
        </div>
    </div>

    @push('scripts')
        {!! $tonaseChart->script() !!}
        {!! $estMingguan->script() !!}
        {!! $estBulanan->script() !!}
        {!! $estTahunan->script() !!}
    @endpush

    <script>
        (function () {
            // Dependen: pilih distrik → opsi kebun difilter (tanpa reload).
            var distrik = document.getElementById('f-distrik');
            var kebun = document.getElementById('f-kebun');
            function filterKebun() {
                var d = distrik.value;
                kebun.querySelectorAll('option').forEach(function (opt) {
                    if (!opt.value) return; // "Semua Kebun" selalu tampil
                    opt.hidden = !!d && String(opt.getAttribute('data-distrik')) !== String(d);
                });
                var sel = kebun.querySelector('option:checked');
                if (sel && sel.hidden) kebun.value = '';
            }
            distrik.addEventListener('change', filterKebun);
            filterKebun();

            // Tab Estimasi vs Realisasi.
            document.querySelectorAll('.est-tab-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var tab = btn.getAttribute('data-est-tab');
                    document.querySelectorAll('.est-tab-panel').forEach(function (p) { p.classList.add('hidden'); });
                    document.getElementById('est-' + tab).classList.remove('hidden');
                    document.querySelectorAll('.est-tab-btn').forEach(function (b) {
                        b.classList.remove('bg-primary', 'text-on-primary');
                        b.classList.add('text-on-surface-variant', 'hover:bg-surface-container-high');
                    });
                    btn.classList.add('bg-primary', 'text-on-primary');
                    btn.classList.remove('text-on-surface-variant', 'hover:bg-surface-container-high');
                    window.dispatchEvent(new Event('resize'));
                });
            });
        })();
    </script>
</x-layouts::app>
