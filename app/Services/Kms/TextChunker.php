<?php

namespace App\Services\Kms;

/**
 * Potong teks panjang menjadi chunks ~chunk_size karakter dengan overlap,
 * diutamakan pada batas paragraf/kalimat agar konteks tidak terputus.
 */
class TextChunker
{
    public static function chunk(string $text, ?int $maxChars = null, ?int $overlap = null): array
    {
        $maxChars = $maxChars ?? (int) config('kms.chunk_size', 1200);
        $overlap = $overlap ?? (int) config('kms.chunk_overlap', 150);

        $text = trim($text);
        if ($text === '') {
            return [];
        }
        if (mb_strlen($text) <= $maxChars) {
            return [$text];
        }

        // Kumpulkan paragraf; paragraf raksasa dipecah per kalimat dulu.
        $pieces = [];
        foreach (preg_split('/\n{2,}/', $text) as $para) {
            $para = trim($para);
            if ($para === '') {
                continue;
            }
            if (mb_strlen($para) <= $maxChars) {
                $pieces[] = $para;

                continue;
            }
            foreach (preg_split('/(?<=[.!?])\s+/u', $para) as $sentence) {
                $sentence = trim($sentence);
                if ($sentence === '') {
                    continue;
                }
                if (mb_strlen($sentence) <= $maxChars) {
                    $pieces[] = $sentence;

                    continue;
                }
                // Kalimat super panjang: potong keras per maxChars.
                $len = mb_strlen($sentence);
                for ($i = 0; $i < $len; $i += $maxChars) {
                    $pieces[] = trim(mb_substr($sentence, $i, $maxChars));
                }
            }
        }

        $chunks = [];
        $current = '';
        foreach ($pieces as $piece) {
            $candidate = $current === '' ? $piece : $current."\n\n".$piece;
            if (mb_strlen($candidate) <= $maxChars) {
                $current = $candidate;

                continue;
            }
            if ($current !== '') {
                $chunks[] = $current;
                // Overlap: ekor chunk sebelumnya jadi awalan chunk berikut.
                $current = $overlap > 0 ? mb_substr($current, -$overlap)."\n\n".$piece : $piece;
                if (mb_strlen($current) > $maxChars) {
                    $chunks[] = mb_substr($current, 0, $maxChars);
                    $current = $overlap > 0 ? mb_substr($chunks[count($chunks) - 1], -$overlap) : '';
                }
            } else {
                $chunks[] = $piece;
            }
        }
        if (trim($current) !== '') {
            $chunks[] = $current;
        }

        return array_values(array_filter(array_map('trim', $chunks), fn ($c) => $c !== ''));
    }
}
