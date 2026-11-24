<?php

namespace App\Services;

use App\Models\Blok;
use App\Models\Lhp;
use App\Models\LhpBlok;

/**
 * Sinkron rincian panen per blok + hitung ulang total header LHP.
 * Dipakai form web (LhpController) dan import per-blok (LhpImportAction).
 *
 * $rows: [blok_id => ['luas_panen'=>?, 'janjang_panen'=>?, 'janjang_kirim'=>?,
 *   'brondolan_panen'=>?, 'brondolan_kirim'=>?, 'restan'=>bool]]
 */
class LhpBlokService
{
    public function sync(Lhp $lhp, array $rows): void
    {
        $blokIds = Blok::where('blok_id_kebun', $lhp->lhp_id_kebun)->pluck('blok_id')->all();
        $keep = [];
        $sumLuas = 0;
        $sumJp = 0;
        $sumJk = 0;
        $sumBp = 0;
        $sumBk = 0;
        $sumRj = 0;
        $sumRb = 0;
        $restanCodes = [];

        foreach ($rows as $blokId => $r) {
            if (! in_array((int) $blokId, array_map('intval', $blokIds), true) || ! is_array($r)) {
                continue;
            }
            $filled = array_filter([
                $r['luas_panen'] ?? null, $r['janjang_panen'] ?? null, $r['janjang_kirim'] ?? null,
                $r['brondolan_panen'] ?? null, $r['brondolan_kirim'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');
            $isRestan = ! empty($r['restan']);
            if ($filled === [] && ! $isRestan) {
                continue;
            }

            // Restan = panen − kirim (tidak boleh negatif).
            $jp = (int) ($r['janjang_panen'] ?? 0);
            $jk = (int) ($r['janjang_kirim'] ?? 0);
            $bp = (float) ($r['brondolan_panen'] ?? 0);
            $bk = (float) ($r['brondolan_kirim'] ?? 0);
            $restanJjg = max(0, $jp - $jk);
            $restanBrd = max(0, $bp - $bk);
            // Checkbox restan di form dihapus: status restan otomatis dari ada/tidaknya sisa.
            $isRestan = ! empty($r['restan']) || $restanJjg > 0 || $restanBrd > 0;

            $keep[] = (int) $blokId;
            LhpBlok::updateOrCreate(
                ['lhp_blok_id_lhp' => $lhp->lhp_id, 'lhp_blok_id_blok' => (int) $blokId],
                [
                    'lhp_blok_luas_panen' => $this->num($r['luas_panen'] ?? null),
                    'lhp_blok_janjang_panen' => $this->int($r['janjang_panen'] ?? null),
                    'lhp_blok_janjang_kirim' => $this->int($r['janjang_kirim'] ?? null),
                    'lhp_blok_brondolan_panen' => $this->num($r['brondolan_panen'] ?? null),
                    'lhp_blok_brondolan_kirim' => $this->num($r['brondolan_kirim'] ?? null),
                    'lhp_blok_restan_janjang' => $restanJjg,
                    'lhp_blok_restan_brondolan' => $restanBrd,
                    'lhp_blok_restan' => $isRestan,
                ]
            );

            $sumLuas += (float) ($r['luas_panen'] ?? 0);
            $sumJp += $jp;
            $sumJk += $jk;
            $sumBp += $bp;
            $sumBk += $bk;
            $sumRj += $restanJjg;
            $sumRb += $restanBrd;
            if ($isRestan) {
                $restanCodes[] = Blok::where('blok_id', (int) $blokId)->value('blok_kode');
            }
        }

        $del = LhpBlok::where('lhp_blok_id_lhp', $lhp->lhp_id);
        if ($keep !== []) {
            $del->whereNotIn('lhp_blok_id_blok', $keep);
        }
        $del->delete();

        $lhp->update([
            'lhp_luas_panen' => $sumLuas ?: null,
            'lhp_janjang_panen' => $sumJp ?: null,
            'lhp_janjang_kirim' => $sumJk ?: null,
            'lhp_brondolan_kg' => ($sumBp + $sumBk) ?: null,
            'lhp_restan_janjang' => $sumRj ?: null,
            'lhp_restan_brondolan_kg' => $sumRb ?: null,
            'lhp_blok_restan' => $restanCodes !== [] ? implode(', ', array_filter($restanCodes)) : null,
        ]);
    }

    /**
     * Pusingan per baris blok = selisih hari antara tanggal panen baris ini
     * dengan tanggal panen sebelumnya dari blok yang sama (seluruh histori,
     * tidak dibatasi periode filter). Baris pertama suatu blok → null.
     *
     * Hasil ditempel sebagai atribut non-persisted:
     * - `pusingan_hari` (?int)
     * - `pusingan_prev` (?string Y-m-d, tanggal panen sebelumnya)
     *
     * $cache dipakai antar-chunk pada export agar histori cukup diquery sekali.
     */
    public function attachPusingan(iterable $rows, array &$cache = []): void
    {
        $rows = $rows instanceof \Traversable ? iterator_to_array($rows) : (is_array($rows) ? $rows : $rows->all());
        if ($rows === []) {
            return;
        }

        $blokIds = collect($rows)->map(fn ($r) => (int) $r->lhp_blok_id_blok)->unique()->values()->all();
        $missing = array_values(array_diff($blokIds, array_keys($cache)));
        if ($missing !== []) {
            $histori = LhpBlok::query()
                ->select('lhp_blok_id_blok', 'lhp_tanggal')
                ->join('lhp', 'lhp.lhp_id', '=', 'lhp_blok.lhp_blok_id_lhp')
                ->whereIn('lhp_blok_id_blok', $missing)
                ->orderBy('lhp_tanggal')
                ->get()
                ->groupBy('lhp_blok_id_blok');
            foreach ($missing as $bid) {
                $cache[$bid] = isset($histori[$bid])
                    ? $histori[$bid]->map(fn ($h) => (string) $h->lhp_tanggal)->unique()->values()->all()
                    : [];
            }
        }

        foreach ($rows as $r) {
            $bid = (int) $r->lhp_blok_id_blok;
            $tgl = $r->hasLhp?->lhp_tanggal;
            $prev = null;
            if ($tgl) {
                $cur = $tgl instanceof \Carbon\CarbonInterface ? $tgl->toDateString() : substr((string) $tgl, 0, 10);
                foreach ($cache[$bid] ?? [] as $d) {
                    if ($d < $cur && ($prev === null || $d > $prev)) {
                        $prev = $d;
                    }
                }
            }
            $r->pusingan_prev = $prev;
            $r->pusingan_hari = ($prev !== null && $tgl)
                ? (int) \Carbon\Carbon::parse($prev)->diffInDays(\Carbon\Carbon::parse($tgl))
                : null;
        }
    }

    /**
     * Peta blok_id => tanggal panen terakhir (Y-m-d) untuk daftar blok.
     * Dipakai tabel master blok: kolom Panen Terakhir + Pusingan (s/d hari ini).
     *
     * @return array<int, string>
     */
    public function lastPanenMap(array $blokIds): array
    {
        if ($blokIds === []) {
            return [];
        }

        return LhpBlok::query()
            ->selectRaw('lhp_blok_id_blok, MAX(lhp_tanggal) AS tgl')
            ->join('lhp', 'lhp.lhp_id', '=', 'lhp_blok.lhp_blok_id_lhp')
            ->whereIn('lhp_blok_id_blok', $blokIds)
            ->groupBy('lhp_blok_id_blok')
            ->pluck('tgl', 'lhp_blok_id_blok')
            ->map(fn ($v) => substr((string) $v, 0, 10))
            ->all();
    }

    private function num(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        // Terima format Indonesia: "1.234,56" maupun "1234.56".
        $s = trim((string) $v);
        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : null;
    }

    private function int(mixed $v): ?int
    {
        $n = $this->num($v);

        return $n === null ? null : (int) $n;
    }
}
