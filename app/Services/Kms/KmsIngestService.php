<?php

namespace App\Services\Kms;

use App\Enums\KmsDocumentStatusEnum;
use App\Models\KmsChunk;
use App\Models\KmsDocument;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Ekstrak file dokumen → potongan teks → simpan ke kms_chunks.
 * Dipicu otomatis oleh event saved() KmsDocument saat file berubah.
 */
class KmsIngestService
{
    public static function ingest(KmsDocument $document): bool
    {
        $document->updateQuietly([
            'kms_document_status' => KmsDocumentStatusEnum::PROCESSING,
            'kms_document_error' => null,
        ]);

        try {
            $relative = $document->kms_document_file;
            if (empty($relative)) {
                throw new \RuntimeException('Dokumen belum punya file.');
            }

            $absolute = storage_path('app/public/'.$relative);
            if (! is_file($absolute)) {
                throw new \RuntimeException('File tidak ditemukan di penyimpanan.');
            }

            $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
            $units = DocumentTextExtractor::extract($absolute, $extension);

            $rows = [];
            $order = 0;
            $maxPage = 0;
            foreach ($units as $unit) {
                foreach (TextChunker::chunk($unit['teks']) as $teks) {
                    $label = $unit['label'] ?? null;
                    $rows[] = [
                        'kms_chunk_id_document' => $document->kms_document_id,
                        'kms_chunk_halaman' => $unit['halaman'],
                        'kms_chunk_urutan' => $order++,
                        'kms_chunk_teks' => $label ? "[{$label}]\n".$teks : $teks,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if (! empty($unit['halaman'])) {
                    $maxPage = max($maxPage, (int) $unit['halaman']);
                }
            }

            if ($rows === []) {
                throw new \RuntimeException('Tidak ada teks yang bisa diekstrak dari dokumen.');
            }

            DB::transaction(function () use ($document, $rows) {
                KmsChunk::where('kms_chunk_id_document', $document->kms_document_id)->delete();
                foreach (array_chunk($rows, 200) as $batch) {
                    KmsChunk::insert($batch);
                }
            });

            $document->updateQuietly([
                'kms_document_status' => KmsDocumentStatusEnum::READY,
                'kms_document_error' => null,
                'kms_document_jumlah_halaman' => $maxPage,
                'kms_document_jumlah_chunk' => count($rows),
            ]);

            return true;
        } catch (Throwable $e) {
            $document->updateQuietly([
                'kms_document_status' => KmsDocumentStatusEnum::FAILED,
                'kms_document_error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            return false;
        }
    }
}
