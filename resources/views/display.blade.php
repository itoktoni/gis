<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="refresh" content="60"/>
    <title>Display Monitoring LHP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css'])
    <script src="{{ asset('js/apexcharts.min.js') }}"></script>
    <style>
        html, body { background: #eef2f7; color: #0f172a; font-family: 'Inter', system-ui, sans-serif; }
        .tv-card { background: #ffffff; border: 1px solid #dbe3ef; border-radius: 1rem; box-shadow: 0 1px 3px rgba(15, 23, 42, .06); }
        .tv-num { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body class="min-h-screen p-4 md:p-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-600">
                <span class="h-3 w-3 rounded-full bg-white animate-pulse"></span>
            </span>
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-slate-900">MONITORING PRODUKSI LHP</h1>
                <p class="text-sm text-slate-500">{{ $today->locale('id')->isoFormat('dddd, D MMMM Y') }} • auto-refresh 60 detik</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div id="tv-clock" class="tv-num text-4xl md:text-5xl font-extrabold text-slate-900">--:--:--</div>
            <form method="GET" action="{{ route('display') }}" id="tv-filter" class="flex items-center gap-2">
                <select name="distrik_id" id="tv-distrik" onchange="document.getElementById('tv-filter').submit()" class="h-10 px-2 bg-white border border-slate-300 rounded-lg text-sm outline-none max-w-40">
                    <option value="">Semua Distrik</option>
                    @foreach($distrikOptions as $id => $nama)
                    <option value="{{ $id }}" {{ (string) $distrikId === (string) $id ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
                <select name="kebun_id" id="tv-kebun" onchange="document.getElementById('tv-filter').submit()" class="h-10 px-2 bg-white border border-slate-300 rounded-lg text-sm outline-none max-w-40">
                    <option value="">Semua Kebun</option>
                    @foreach($kebunOptions as $k)
                    <option value="{{ $k['id'] }}" data-distrik="{{ $k['distrik'] }}" {{ (string) $kebunId === (string) $k['id'] ? 'selected' : '' }}>{{ $k['nama'] }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" onclick="toggleFull()" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">⛶ Fullscreen</button>
        </div>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
        <div class="tv-card p-5">
            <p class="text-sm font-semibold uppercase tracking-widest text-slate-500 mb-1">Tonase Kirim Hari Ini</p>
            <p class="tv-num text-5xl font-extrabold text-emerald-600">{{ number_format($kirimHariIni / 1000, 2, ',', '.') }} <span class="text-xl font-bold text-slate-400">ton</span></p>
            <p class="text-sm text-slate-500 mt-1">Panen: {{ number_format($panenHariIni / 1000, 2, ',', '.') }} ton</p>
        </div>
        <div class="tv-card p-5">
            <p class="text-sm font-semibold uppercase tracking-widest text-slate-500 mb-1">Capaian vs RKAP</p>
            <p class="tv-num text-5xl font-extrabold {{ $capaian >= 100 ? 'text-emerald-600' : ($capaian >= 70 ? 'text-amber-600' : 'text-red-600') }}">{{ number_format($capaian, 1, ',', '.') }}%</p>
            <p class="text-sm text-slate-500 mt-1">realisasi ÷ estimasi harian</p>
        </div>
        <div class="tv-card p-5">
            <p class="text-sm font-semibold uppercase tracking-widest text-slate-500 mb-1">Kebun Lapor</p>
            <p class="tv-num text-5xl font-extrabold text-sky-600">{{ $kebunLapor }}<span class="text-2xl text-slate-400"> / {{ $totalKebun }}</span></p>
            <p class="text-sm text-slate-500 mt-1">kebun sudah input hari ini</p>
        </div>
        <div class="tv-card p-5">
            <p class="text-sm font-semibold uppercase tracking-widest text-slate-500 mb-1">Periode Leaderboard</p>
            <p class="text-3xl font-extrabold text-slate-900">{{ $monthStart->locale('id')->isoFormat('MMMM Y') }}</p>
            <p class="text-sm text-slate-500 mt-1">akumulasi 1 – {{ $today->format('d') }} {{ $today->locale('id')->isoFormat('MMMM') }}</p>
        </div>
    </div>

    {{-- Tren + leaderboard --}}
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-4 mb-4">
        <div class="tv-card p-5 xl:col-span-3 min-w-0 overflow-hidden">
            <h2 class="text-lg font-bold mb-3 text-slate-900">📈 Tonase Kirim — 7 Hari Terakhir</h2>
            <div class="min-w-0">{!! $trendChart->container() !!}</div>
        </div>
        <div class="tv-card p-5 min-w-0 overflow-hidden">
            <div class="flex items-center justify-between gap-2 mb-4">
                <h2 class="text-lg font-bold text-slate-900">🏆 Leaderboard</h2>
                <div class="flex gap-1 rounded-lg bg-slate-100 p-1" role="tablist">
                    <button type="button" data-tvboard="distrik" class="tvboard-btn px-3 py-1 text-sm font-semibold rounded-md bg-slate-900 text-white">Distrik</button>
                    <button type="button" data-tvboard="kebun" class="tvboard-btn px-3 py-1 text-sm font-semibold rounded-md text-slate-500 hover:bg-slate-200">Kebun</button>
                </div>
            </div>
            <div id="tvboard-distrik">
            @if($board->isNotEmpty())
            <div class="space-y-4">
                @foreach($board as $i => $row)
                @php $pct = $maxBoardKg > 0 ? round($row->kg / $maxBoardKg * 100) : 0; @endphp
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-8 h-8 rounded-lg text-lg font-extrabold flex items-center justify-center shrink-0 {{ $i === 0 ? 'bg-amber-400 text-amber-950' : ($i === 1 ? 'bg-slate-300 text-slate-800' : ($i === 2 ? 'bg-amber-700 text-white' : 'bg-slate-200 text-slate-500')) }}">{{ $i + 1 }}</span>
                        <span class="text-base font-semibold truncate text-slate-900">{{ $row->nama }}</span>
                        <span class="ml-auto tv-num text-xl font-extrabold text-emerald-600 whitespace-nowrap">{{ number_format($row->kg / 1000, 1, ',', '.') }} t</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-slate-200 overflow-hidden ml-10">
                        <div class="h-full rounded-full bg-gradient-to-r from-sky-500 to-emerald-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-slate-500">Belum ada data bulan ini.</p>
            @endif
            </div>
            <div id="tvboard-kebun" class="hidden">
            @if($kebunBoard->isNotEmpty())
            <div class="space-y-4">
                @foreach($kebunBoard as $i => $row)
                @php $pct = $maxKebunKg > 0 ? round($row->kg / $maxKebunKg * 100) : 0; @endphp
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-8 h-8 rounded-lg text-lg font-extrabold flex items-center justify-center shrink-0 {{ $i === 0 ? 'bg-amber-400 text-amber-950' : ($i === 1 ? 'bg-slate-300 text-slate-800' : ($i === 2 ? 'bg-amber-700 text-white' : 'bg-slate-200 text-slate-500')) }}">{{ $i + 1 }}</span>
                        <span class="text-base font-semibold truncate text-slate-900">{{ $row->nama }}</span>
                        <span class="ml-auto tv-num text-xl font-extrabold text-emerald-600 whitespace-nowrap">{{ number_format($row->kg / 1000, 1, ',', '.') }} t</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-slate-200 overflow-hidden ml-10">
                        <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-emerald-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-slate-500">Belum ada data bulan ini.</p>
            @endif
            </div>
            <p class="text-xs text-slate-400 mt-3">bulan {{ $monthStart->locale('id')->isoFormat('MMMM Y') }} • otomatis bergantian tiap 15 detik</p>
        </div>
    </div>

    {{-- Top kebun hari ini --}}
    <div class="tv-card p-5">
        <h2 class="text-lg font-bold mb-3 text-slate-900">🚜 Top Kebun Hari Ini (kirim)</h2>
        @if($topKebun->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
            @foreach($topKebun as $i => $r)
            <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 flex items-center gap-3">
                <span class="text-2xl font-extrabold text-slate-400">{{ $i + 1 }}</span>
                <div class="min-w-0">
                    <p class="font-semibold truncate text-slate-900">{{ $r->hasKebun?->kebun_nama ?? '-' }}</p>
                    <p class="tv-num text-xl font-extrabold text-emerald-600">{{ number_format(($r->lhp_produksi_kirim_kg ?? 0) / 1000, 2, ',', '.') }} ton</p>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-slate-500">Belum ada LHP hari ini.</p>
        @endif
    </div>

    {!! $trendChart->script() !!}
    <script>
        // Dependen: pilih distrik → opsi kebun difilter (lalu auto-submit).
        (function () {
            var distrik = document.getElementById('tv-distrik');
            var kebun = document.getElementById('tv-kebun');
            function filterKebun() {
                var d = distrik.value;
                kebun.querySelectorAll('option').forEach(function (opt) {
                    if (!opt.value) return;
                    opt.hidden = !!d && String(opt.getAttribute('data-distrik')) !== String(d);
                });
                var sel = kebun.querySelector('option:checked');
                if (sel && sel.hidden) kebun.value = '';
            }
            distrik.addEventListener('change', function () { filterKebun(); });
            filterKebun();
        })();
        // Toggle manual + rotasi otomatis Distrik | Kebun tiap 15 detik.
        var tvBoardCurrent = 'distrik';
        function tvBoardShow(which) {
            tvBoardCurrent = which;
            document.getElementById('tvboard-distrik').classList.toggle('hidden', which !== 'distrik');
            document.getElementById('tvboard-kebun').classList.toggle('hidden', which !== 'kebun');
            document.querySelectorAll('.tvboard-btn').forEach(function (b) {
                var on = b.getAttribute('data-tvboard') === which;
                b.classList.toggle('bg-slate-900', on);
                b.classList.toggle('text-white', on);
                b.classList.toggle('text-slate-500', !on);
            });
        }
        document.querySelectorAll('.tvboard-btn').forEach(function (b) {
            b.addEventListener('click', function () { tvBoardShow(b.getAttribute('data-tvboard')); });
        });
        setInterval(function () {
            tvBoardShow(tvBoardCurrent === 'distrik' ? 'kebun' : 'distrik');
        }, 15000);
        function tick() {
            var d = new Date();
            var p = function (n) { return String(n).padStart(2, '0'); };
            document.getElementById('tv-clock').textContent = p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
        }
        tick();
        setInterval(tick, 1000);
        function toggleFull() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(function () {});
            } else {
                document.exitFullscreen();
            }
        }
    </script>
</body>
</html>
