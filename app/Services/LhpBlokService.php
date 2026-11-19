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

            $keep[] = (int) $blokId;
            LhpBlok::updateOrCreate(
                ['lhp_blok_id_lhp' => $lhp->lhp_id, 'lhp_blok_id_blok' => (int) $blokId],
                [
                    'lhp_blok_luas_panen' => $this->num($r['luas_panen'] ?? null),
                    'lhp_blok_janjang_panen' => $this->int($r['janjang_panen'] ?? null),
                    'lhp_blok_janjang_kirim' => $this->int($r['janjang_kirim'] ?? null),
                    'lhp_blok_brondolan_panen' => $this->num($r['brondolan_panen'] ?? null),
                    'lhp_blok_brondolan_kirim' => $this->num($r['brondolan_kirim'] ?? null),
                    'lhp_blok_restan' => $isRestan,
                ]
            );

            $sumLuas += (float) ($r['luas_panen'] ?? 0);
            $sumJp += (int) ($r['janjang_panen'] ?? 0);
            $sumJk += (int) ($r['janjang_kirim'] ?? 0);
            $sumBp += (float) ($r['brondolan_panen'] ?? 0);
            $sumBk += (float) ($r['brondolan_kirim'] ?? 0);
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
            'lhp_blok_restan' => $restanCodes !== [] ? implode(', ', array_filter($restanCodes)) : null,
        ]);
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
