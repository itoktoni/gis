<?php

namespace App\Services\Kms;

use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

/**
 * Ekstrak teks dokumen per unit (halaman untuk PDF).
 *
 * @return array<int, array{halaman: int|null, teks: string, label: string|null}>
 */
class DocumentTextExtractor
{
    public static function extract(string $absolutePath, string $extension): array
    {
        $extension = strtolower(ltrim($extension, '.'));

        return match ($extension) {
            'pdf' => self::fromPdf($absolutePath),
            'docx' => self::fromDocx($absolutePath),
            'xlsx' => self::fromXlsx($absolutePath),
            'txt', 'md', 'csv', 'text' => self::fromPlainText($absolutePath),
            default => throw new RuntimeException("Format .{$extension} belum didukung (pakai PDF, DOCX, XLSX, TXT, MD, CSV)."),
        };
    }

    /**
     * @return array<int, array{halaman: int|null, teks: string, label: string|null}>
     */
    private static function fromPdf(string $path): array
    {
        try {
            $parser = new PdfParser;
            $pdf = $parser->parseFile($path);
            $pages = $pdf->getPages();
        } catch (\Throwable $e) {
            throw new RuntimeException('PDF tidak bisa dibaca: '.$e->getMessage());
        }

        $units = [];
        foreach (array_values($pages) as $i => $page) {
            try {
                $text = trim($page->getText());
            } catch (\Throwable) {
                $text = '';
            }
            $text = self::normalize($text);
            if ($text === '') {
                continue;
            }
            $units[] = ['halaman' => $i + 1, 'teks' => $text, 'label' => null];
        }

        if ($units === []) {
            throw new RuntimeException('PDF tidak mengandung teks (kemungkinan hasil scan gambar — butuh OCR).');
        }

        return $units;
    }

    /**
     * DOCX = ZIP berisi word/document.xml. Baca per paragraf tanpa library tambahan.
     *
     * @return array<int, array{halaman: int|null, teks: string, label: string|null}>
     */
    private static function fromDocx(string $path): array
    {
        $xml = self::readZipEntry($path, 'word/document.xml', 'DOCX rusak atau tidak valid.');

        preg_match_all('/<w:p[^>]*>(.*?)<\/w:p>/s', $xml, $matches);
        $paragraphs = [];
        foreach ($matches[1] as $p) {
            $p = preg_replace('/<w:tab[^>]*\/>/', ' ', $p);
            $p = preg_replace('/<w:br[^>]*\/>/', "\n", $p);
            $text = trim(html_entity_decode(strip_tags($p), ENT_QUOTES | ENT_XML1, 'UTF-8'));
            if ($text !== '') {
                $paragraphs[] = $text;
            }
        }

        $text = self::normalize(implode("\n\n", $paragraphs));
        if ($text === '') {
            throw new RuntimeException('DOCX tidak mengandung teks.');
        }

        // DOCX tidak menyimpan nomor halaman → halaman null, sitasi memakai "Bagian".
        return [['halaman' => null, 'teks' => $text, 'label' => null]];
    }

    /**
     * XLSX = ZIP; baca string bersama + tiap sheet. Satu sheet = satu unit.
     *
     * @return array<int, array{halaman: int|null, teks: string, label: string|null}>
     */
    private static function fromXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('XLSX rusak atau tidak valid.');
        }

        $shared = [];
        if (($raw = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $raw, $m);
            $shared = array_map(
                fn ($s) => html_entity_decode($s, ENT_QUOTES | ENT_XML1, 'UTF-8'),
                $m[1]
            );
        }

        $sheetNames = [];
        if (($wb = $zip->getFromName('xl/workbook.xml')) !== false) {
            preg_match_all('/<sheet[^>]*name="([^"]+)"/', $wb, $m);
            $sheetNames = array_map(
                fn ($s) => html_entity_decode($s, ENT_QUOTES | ENT_XML1, 'UTF-8'),
                $m[1]
            );
        }

        $units = [];
        for ($i = 1; $i <= 100; $i++) {
            $sheetXml = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
            if ($sheetXml === false) {
                break;
            }
            $cells = [];
            preg_match_all('/<row[^>]*>(.*?)<\/row>/s', $sheetXml, $rows);
            foreach ($rows[1] as $row) {
                $values = [];
                // Sel string bersama: <c t="s"><v>indeks</v></c>; inline: <c t="inlineStr"><is><t>..</t></is></c>; angka/tanggal: <v>.
                preg_match_all('/<c[^>]*>(.*?)<\/c>/s', $row, $cellMatches);
                foreach ($cellMatches[1] as $cell) {
                    if (str_contains($cell, '<is>')) {
                        preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $cell, $tm);
                        $values[] = implode('', array_map(
                            fn ($s) => html_entity_decode($s, ENT_QUOTES | ENT_XML1, 'UTF-8'),
                            $tm[1]
                        ));
                    } elseif (preg_match('/<v>(.*?)<\/v>/s', $cell, $vm)) {
                        $idx = trim($vm[1]);
                        $values[] = ctype_digit($idx) && isset($shared[(int) $idx]) ? $shared[(int) $idx] : $idx;
                    }
                }
                $line = trim(implode(' | ', array_filter($values, fn ($v) => trim((string) $v) !== '')));
                if ($line !== '') {
                    $cells[] = $line;
                }
            }
            $text = self::normalize(implode("\n", $cells));
            if ($text === '') {
                continue;
            }
            $units[] = [
                'halaman' => null,
                'teks' => $text,
                'label' => $sheetNames[$i - 1] ?? "Sheet{$i}",
            ];
        }
        $zip->close();

        if ($units === []) {
            throw new RuntimeException('XLSX tidak mengandung teks.');
        }

        return $units;
    }

    /**
     * @return array<int, array{halaman: int|null, teks: string, label: string|null}>
     */
    private static function fromPlainText(string $path): array
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('File teks tidak bisa dibaca.');
        }
        $raw = str_replace(chr(0), '', $raw);
        if (! mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'auto');
        }
        $text = self::normalize($raw);
        if ($text === '') {
            throw new RuntimeException('File teks kosong.');
        }

        return [['halaman' => null, 'teks' => $text, 'label' => null]];
    }

    private static function readZipEntry(string $path, string $entry, string $errorMessage): string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException($errorMessage);
        }
        $content = $zip->getFromName($entry);
        $zip->close();
        if ($content === false) {
            throw new RuntimeException($errorMessage);
        }

        return $content;
    }

    private static function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}
