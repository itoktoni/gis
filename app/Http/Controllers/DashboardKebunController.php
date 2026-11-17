<?php

namespace App\Http\Controllers;

use App\Charts\KebunDashboardChart;
use App\Models\Distrik;
use App\Models\Kebun;
use App\Models\Lhp;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardKebunController extends Controller
{
    public function __invoke(KebunDashboardChart $chart)
    {
        // Preset rentang: mingguan = 7 hari, bulanan = 30 hari, tahunan = 365 hari (termasuk hari ini).
        $range = request()->input('range', 'bulanan');
        if (! in_array($range, ['mingguan', 'bulanan', 'tahunan'], true)) {
            $range = 'bulanan';
        }
        $days = $range === 'mingguan' ? 7 : ($range === 'tahunan' ? 365 : 30);

        $dari = $this->parseDate(request()->input('dari'));
        $sampai = $this->parseDate(request()->input('sampai'));
        if ($dari === null || $sampai === null) {
            $sampai = Carbon::today();
            $dari = Carbon::today()->subDays($days - 1)->startOfDay();
        }
        if ($sampai->lt($dari)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        // Filter dependen: distrik → kebun (diterapkan di atas scope hak akses user).
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

        // Opsi dropdown (menghormati scope + pilihan distrik).
        $distrikOptions = Distrik::whereHas('hasKebun', fn ($q) => scopeKebun($q, 'kebun_id'))
            ->orderBy('distrik_kode')->orderBy('distrik_nama')->get()
            ->mapWithKeys(fn ($d) => [$d->distrik_id => Distrik::labelKodeNama($d)]);
        $kebunOptions = Kebun::when(allowedKebunIds(), fn ($q, $ids) => $q->whereIn('kebun_id', $ids))
            ->orderBy('kebun_nama')->get()
            ->map(fn ($k) => ['id' => $k->kebun_id, 'nama' => $k->kebun_nama, 'distrik' => $k->kebun_id_distrik]);

        // Estimasi harian = total RKAP master kebun dalam filter (konstan per hari,
        // mengikuti pola file excel yang RKAP hariannya konstan).
        $dailyEstimasiKg = (float) (clone $kebunBase)->sum('kebun_rkap_kg');
        // Luas kebun dalam filter — pembagi Yield per Ha.
        $luasHa = (float) (clone $kebunBase)->sum('kebun_luas_ha');
        $yield = fn (float $kg) => $luasHa > 0 ? round($kg / $luasHa, 2) : 0;

        $rows = Lhp::with(['hasKebun.hasDistrik'])->whereIn('lhp_id_kebun', $kebunIds)
            ->whereDate('lhp_tanggal', '>=', $dari->toDateString())
            ->whereDate('lhp_tanggal', '<=', $sampai->toDateString())
            ->orderBy('lhp_tanggal')
            ->get();

        $byDate = $rows->groupBy(fn ($r) => $r->lhp_tanggal->toDateString());

        $period = CarbonPeriod::create($dari, $sampai);
        $dayLabels = [];
        $dayTonase = [];
        $dayYield = [];
        $totalPanenKg = 0;
        $totalKirimKg = 0;

        foreach ($period as $date) {
            $key = $date->toDateString();
            $dayRows = $byDate->get($key, collect());
            $panen = (float) $dayRows->sum('lhp_produksi_panen_kg');
            $kirim = (float) $dayRows->sum('lhp_produksi_kirim_kg');
            $totalPanenKg += $panen;
            $totalKirimKg += $kirim;
            $dayLabels[] = $date->locale('id')->isoFormat('D MMM');
            $dayTonase[] = round($kirim / 1000, 2);
            $dayYield[] = $yield($kirim);
        }

        // Bucket mingguan & bulanan dari tanggal di periode (bukan hanya hari ber-data).
        [$weekLabels, $weekRealisasi] = $this->bucket($period, $byDate, 'week');
        [$monthLabels, $monthRealisasi] = $this->bucket($period, $byDate, 'month');
        $weekYield = array_map($yield, array_map(fn ($t) => $t * 1000, $weekRealisasi));
        $monthYield = array_map($yield, array_map(fn ($t) => $t * 1000, $monthRealisasi));

        // Leaderboard distrik — perolehan (kirim) tertinggi dalam periode + filter.
        $board = Lhp::query()->whereIn('lhp.lhp_id_kebun', $kebunIds)
            ->join('kebun', 'kebun.kebun_id', '=', 'lhp.lhp_id_kebun')
            ->leftJoin('distrik', 'distrik.distrik_id', '=', 'kebun.kebun_id_distrik')
            ->whereDate('lhp_tanggal', '>=', $dari->toDateString())
            ->whereDate('lhp_tanggal', '<=', $sampai->toDateString())
            ->groupBy('distrik.distrik_id', 'distrik.distrik_nama')
            ->selectRaw("COALESCE(distrik.distrik_nama, 'Tanpa Distrik') as nama, SUM(lhp.lhp_produksi_kirim_kg) as kg, SUM(lhp.lhp_produksi_panen_kg) as panen_kg, COUNT(*) as laporan")
            ->orderByDesc('kg')
            ->get();
        $maxBoardKg = (float) ($board->max('kg') ?: 0);

        // Top kebun — perolehan (kirim) tertinggi dalam periode + filter.
        $kebunBoard = Lhp::query()->whereIn('lhp.lhp_id_kebun', $kebunIds)
            ->join('kebun', 'kebun.kebun_id', '=', 'lhp.lhp_id_kebun')
            ->leftJoin('distrik', 'distrik.distrik_id', '=', 'kebun.kebun_id_distrik')
            ->whereDate('lhp_tanggal', '>=', $dari->toDateString())
            ->whereDate('lhp_tanggal', '<=', $sampai->toDateString())
            ->groupBy('kebun.kebun_id', 'kebun.kebun_nama', 'distrik.distrik_nama')
            ->selectRaw("kebun.kebun_nama as nama, COALESCE(distrik.distrik_nama, '-') as distrik, SUM(lhp.lhp_produksi_kirim_kg) as kg, SUM(lhp.lhp_produksi_panen_kg) as panen_kg")
            ->orderByDesc('kg')
            ->limit(8)
            ->get();
        $maxKebunKg = (float) ($kebunBoard->max('kg') ?: 0);

        $totalEstimasiKg = $dailyEstimasiKg * iterator_count(CarbonPeriod::create($dari, $sampai));
        $capaian = $totalEstimasiKg > 0 ? round($totalKirimKg / $totalEstimasiKg * 100, 1) : 0;

        $stats = [
            'dari' => $dari->toDateString(),
            'sampai' => $sampai->toDateString(),
            'range' => $range,
            'distrik_id' => $distrikId,
            'kebun_id' => $kebunId,
            'tonase_kirim' => round($totalKirimKg / 1000, 2),
            'tonase_panen' => round($totalPanenKg / 1000, 2),
            'kebun_lapor' => $rows->pluck('lhp_id_kebun')->unique()->count(),
            'total_kebun' => $kebunIds->count(),
            'capaian' => $capaian,
        ];

        return view('dashboard-kebun', compact(
            'stats', 'board', 'maxBoardKg', 'kebunBoard', 'maxKebunKg', 'distrikOptions', 'kebunOptions',
            'dayLabels', 'dayTonase', 'dayYield',
            'weekLabels', 'weekYield',
            'monthLabels', 'monthYield'
        ))
            ->with('tonaseChart', $chart->tonase($dayLabels, $dayTonase))
            ->with('estMingguan', $chart->yieldPerHa($weekLabels, $weekYield, 'Per minggu — kg/ha'))
            ->with('estBulanan', $chart->yieldPerHa($monthLabels, $monthYield, 'Per bulan — kg/ha'))
            ->with('estTahunan', $chart->yieldPerHa($monthLabels, $monthYield, 'Per bulan dalam setahun — kg/ha'));
    }

    /**
     * @return array{0: array, 1: array} [labels, realisasiTon]
     */
    private function bucket(CarbonPeriod $period, $byDate, string $mode): array
    {
        $groups = [];
        foreach ($period as $date) {
            $key = $mode === 'week' ? $date->format('o-\WW') : $date->format('Y-m');
            $groups[$key][] = $date->toDateString();
        }

        $labels = $realisasi = [];
        foreach ($groups as $keys) {
            $days = collect($keys);
            $kg = $days->sum(fn ($k) => (float) $byDate->get($k, collect())->sum('lhp_produksi_kirim_kg'));
            $first = Carbon::parse($days->first())->locale('id');
            $last = Carbon::parse($days->last())->locale('id');
            $labels[] = $mode === 'week'
                ? $first->isoFormat('D MMM').' – '.$last->isoFormat('D MMM')
                : $first->isoFormat('MMMM Y');
            $realisasi[] = round($kg / 1000, 2);
        }

        return [$labels, $realisasi];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }
        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
