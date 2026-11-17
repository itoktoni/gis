<?php

namespace App\Http\Controllers;

use App\Charts\KebunDashboardChart;
use App\Models\Distrik;
use App\Models\Kebun;
use App\Models\Lhp;
use Carbon\Carbon;

class DisplayController extends Controller
{
    public function __invoke(KebunDashboardChart $chart)
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        // Filter (tetap di URL sehingga auto-refresh 60 dtk tidak menghilangkannya).
        $distrikId = request()->input('distrik_id') ?: null;
        $kebunId = request()->input('kebun_id') ?: null;

        $kebunBase = Kebun::when(allowedKebunIds(), fn ($q, $ids) => $q->whereIn('kebun_id', $ids));
        if ($distrikId) {
            $kebunBase->where('kebun_id_distrik', $distrikId);
        }
        if ($kebunId) {
            $kebunBase->where('kebun_id', $kebunId);
        }
        $kebunIds = (clone $kebunBase)->pluck('kebun_id');

        $distrikOptions = Distrik::whereHas('hasKebun', fn ($q) => scopeKebun($q, 'kebun_id'))
            ->orderBy('distrik_kode')->orderBy('distrik_nama')->get()
            ->mapWithKeys(fn ($d) => [$d->distrik_id => Distrik::labelKodeNama($d)]);
        $kebunOptions = Kebun::when(allowedKebunIds(), fn ($q, $ids) => $q->whereIn('kebun_id', $ids))
            ->orderBy('kebun_nama')->get()
            ->map(fn ($k) => ['id' => $k->kebun_id, 'nama' => $k->kebun_nama, 'distrik' => $k->kebun_id_distrik]);

        // KPI hari ini (dalam filter)
        $todayQ = Lhp::query()->whereIn('lhp_id_kebun', $kebunIds)->whereDate('lhp_tanggal', $today->toDateString());
        $kirimHariIni = (float) (clone $todayQ)->sum('lhp_produksi_kirim_kg');
        $panenHariIni = (float) (clone $todayQ)->sum('lhp_produksi_panen_kg');
        $kebunLapor = (clone $todayQ)->distinct('lhp_id_kebun')->count('lhp_id_kebun');
        $dailyEstimasiKg = (float) (clone $kebunBase)->sum('kebun_rkap_kg');
        $capaian = $dailyEstimasiKg > 0 ? round($kirimHariIni / $dailyEstimasiKg * 100, 1) : 0;

        // Tren 7 hari kebelakang (ton kirim per hari).
        $from7 = $today->copy()->subDays(6);
        $byDate = Lhp::query()->whereIn('lhp_id_kebun', $kebunIds)
            ->whereDate('lhp_tanggal', '>=', $from7->toDateString())
            ->whereDate('lhp_tanggal', '<=', $today->toDateString())
            ->selectRaw('lhp_tanggal, SUM(lhp_produksi_kirim_kg) as kg')
            ->groupBy('lhp_tanggal')
            ->pluck('kg', 'lhp_tanggal')
            ->mapWithKeys(fn ($v, $k) => [(string) Carbon::parse($k)->toDateString() => (float) $v]);

        $trendLabels = [];
        $trendTons = [];
        foreach (Carbon::parse($from7->toDateString())->daysUntil($today->toDateString()) as $d) {
            $trendLabels[] = $d->locale('id')->isoFormat('D MMM');
            $trendTons[] = round(($byDate[$d->toDateString()] ?? 0) / 1000, 2);
        }

        // Leaderboard distrik bulan berjalan (dalam filter)
        $board = Lhp::query()->whereIn('lhp.lhp_id_kebun', $kebunIds)
            ->join('kebun', 'kebun.kebun_id', '=', 'lhp.lhp_id_kebun')
            ->leftJoin('distrik', 'distrik.distrik_id', '=', 'kebun.kebun_id_distrik')
            ->whereDate('lhp_tanggal', '>=', $monthStart->toDateString())
            ->whereDate('lhp_tanggal', '<=', $today->toDateString())
            ->groupBy('distrik.distrik_id', 'distrik.distrik_nama')
            ->selectRaw("COALESCE(distrik.distrik_nama, 'Tanpa Distrik') as nama, SUM(lhp.lhp_produksi_kirim_kg) as kg")
            ->orderByDesc('kg')
            ->limit(8)
            ->get();
        $maxBoardKg = (float) ($board->max('kg') ?: 0);

        // Top kebun bulan berjalan (dalam filter) — pasangan toggle leaderboard.
        $kebunBoard = Lhp::query()->whereIn('lhp.lhp_id_kebun', $kebunIds)
            ->join('kebun', 'kebun.kebun_id', '=', 'lhp.lhp_id_kebun')
            ->whereDate('lhp_tanggal', '>=', $monthStart->toDateString())
            ->whereDate('lhp_tanggal', '<=', $today->toDateString())
            ->groupBy('kebun.kebun_id', 'kebun.kebun_nama')
            ->selectRaw('kebun.kebun_nama as nama, SUM(lhp.lhp_produksi_kirim_kg) as kg')
            ->orderByDesc('kg')
            ->limit(8)
            ->get();
        $maxKebunKg = (float) ($kebunBoard->max('kg') ?: 0);

        // Top kebun hari ini (dalam filter)
        $topKebun = Lhp::with('hasKebun')->whereIn('lhp_id_kebun', $kebunIds)
            ->whereDate('lhp_tanggal', $today->toDateString())
            ->orderByDesc('lhp_produksi_kirim_kg')
            ->limit(8)
            ->get();

        return view('display', compact(
            'today', 'monthStart', 'kirimHariIni', 'panenHariIni',
            'kebunLapor', 'board', 'maxBoardKg', 'kebunBoard', 'maxKebunKg', 'topKebun',
            'distrikOptions', 'kebunOptions', 'distrikId', 'kebunId'
        ))->with('totalKebun', $kebunIds->count())
            ->with('trendChart', $chart->tonase($trendLabels, $trendTons, 'Tonase Kirim — 7 Hari Terakhir', '#334155'));
    }
}
