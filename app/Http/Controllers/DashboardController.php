<?php

namespace App\Http\Controllers;

use App\Charts\DashboardChart;
use App\Models\Kebun;
use App\Models\Lhp;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke(DashboardChart $chart)
    {
        $stats = [
            'total_users' => User::count(),
            'total_notifications' => Notification::count(),
            'unread_notifications' => Notification::where('read', false)->count(),
        ];

        $recentUsers = User::latest()->limit(5)->get();

        // --- LHP Harian (report per hari) ---
        $tanggal = request()->input('tanggal', Carbon::today()->toDateString());
        try {
            $tanggalCarbon = Carbon::parse($tanggal);
        } catch (\Throwable) {
            $tanggalCarbon = Carbon::today();
            $tanggal = $tanggalCarbon->toDateString();
        }

        // Kalau tanggal yang dipilih belum ada data, pakai tanggal terakhir ada data
        if (! Lhp::whereDate('lhp_tanggal', $tanggal)->exists()) {
            $last = Lhp::orderByDesc('lhp_tanggal')->value('lhp_tanggal');
            if ($last) {
                $tanggalCarbon = Carbon::parse($last);
                $tanggal = $tanggalCarbon->toDateString();
            }
        }

        $lhpQuery = Lhp::with(['hasKebun.hasPt'])->whereDate('lhp_tanggal', $tanggal);

        $lhpStats = [
            'tanggal' => $tanggalCarbon->copy()->locale('id')->isoFormat('dddd, D MMMM Y'),
            'tanggal_raw' => $tanggal,
            'produksi_panen' => (float) (clone $lhpQuery)->sum('lhp_produksi_panen_kg'),
            'produksi_kirim' => (float) (clone $lhpQuery)->sum('lhp_produksi_kirim_kg'),
            'janjang_panen' => (int) (clone $lhpQuery)->sum('lhp_janjang_panen'),
            'janjang_kirim' => (int) (clone $lhpQuery)->sum('lhp_janjang_kirim'),
            'kebun_lapor' => (clone $lhpQuery)->count(),
            'total_kebun' => Kebun::count(),
            'luas_panen' => (float) (clone $lhpQuery)->sum('lhp_luas_panen'),
            'tk_hk' => (float) (clone $lhpQuery)->sum('lhp_tk_hk'),
        ];

        $lhpPerKebun = (clone $lhpQuery)->orderByDesc('lhp_produksi_kirim_kg')->get();
        $lhpRecent = Lhp::with(['hasKebun.hasPt'])->orderByDesc('lhp_tanggal')->orderByDesc('lhp_id')->limit(10)->get();

        return view('dashboard', compact('stats', 'recentUsers', 'lhpStats', 'lhpPerKebun', 'lhpRecent'))
            ->with('userChart', $chart->userRegistrations())
            ->with('notifChart', $chart->notificationStats());
    }
}
