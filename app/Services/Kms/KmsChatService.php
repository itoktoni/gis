<?php

namespace App\Services\Kms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Tanya-jawab KMS: ambil chunks relevan → susun konteks berlabel sumber
 * (judul + nomor halaman) → minta jawaban ke LLM OpenAI-compatible
 * dengan sitasi [S1], [S2], ... yang dipetakan ke daftar sumber.
 */
class KmsChatService
{
    /**
     * @param  array<int, int|string>  $divisiIds  kosong = cari di semua dokumen
     * @param  array<int, int|string>  $tagIds  kosong = cari di semua dokumen
     *
     * @return array{jawaban: string, sumber: array<int, array{label: string, dokumen: string, lokasi: string, cuplikan: string, dokumen_id: int, chunk_id: int}>}
     */
    public static function ask(string $question, ?int $topK = null, array $divisiIds = [], array $tagIds = []): array
    {
        $question = trim($question);
        if ($question === '') {
            throw new RuntimeException('Pertanyaan tidak boleh kosong.');
        }

        $chunks = KmsRetrievalService::search($question, $topK, $divisiIds, $tagIds);

        if ($chunks->isEmpty()) {
            return [
                'jawaban' => 'Maaf, saya tidak menemukan dokumen yang relevan dengan pertanyaan tersebut. Coba gunakan kata kunci lain atau upload dokumen pendukung ke menu Dokumen KMS terlebih dahulu.',
                'sumber' => [],
            ];
        }

        $maxChars = (int) config('kms.max_context_chars', 12000);
        $contextParts = [];
        $sources = [];
        $used = 0;

        foreach ($chunks->values() as $i => $chunk) {
            $label = 'S'.($i + 1);
            $doc = $chunk->hasDocument;
            $judul = $doc?->kms_document_judul ?? 'Dokumen';
            $lokasi = $chunk->lokasi;
            $teks = trim(mb_substr($chunk->kms_chunk_teks, 0, 2000));
            $part = "[{$label}] {$judul} — {$lokasi}\n{$teks}";

            if ($used + mb_strlen($part) > $maxChars && $contextParts !== []) {
                break;
            }
            $used += mb_strlen($part);
            $contextParts[] = $part;
            $sources[] = [
                'label' => $label,
                'dokumen' => $judul,
                'lokasi' => $lokasi,
                'cuplikan' => mb_substr($teks, 0, 220).(mb_strlen($teks) > 220 ? '…' : ''),
                'dokumen_id' => (int) $chunk->kms_chunk_id_document,
                'chunk_id' => (int) $chunk->kms_chunk_id,
            ];
        }

        $answer = self::complete($question, implode("\n\n", $contextParts));

        return ['jawaban' => $answer, 'sumber' => $sources];
    }

    private static function complete(string $question, string $context): string
    {
        $apiKey = (string) config('services.openai.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY belum diisi. Tambahkan di file .env lalu coba lagi.');
        }

        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com'), '/');
        if (! str_ends_with($baseUrl, '/chat/completions')) {
            $baseUrl .= (str_ends_with($baseUrl, '/v1') ? '' : '/v1').'/chat/completions';
        }
        $model = (string) config('services.openai.model', 'gpt-4o-mini');

        $system = 'Kamu adalah asisten Knowledge Management System berbahasa Indonesia. '.
            'Jawablah HANYA berdasarkan KONTEKS DOKUMEN berikut. Setiap fakta yang kamu tulis WAJIB disertai sitasi label sumbernya seperti [S1], [S2]. '.
            'Jika konteks tidak memuat jawabannya, katakan jujur bahwa dokumen tidak memuat informasi tersebut dan jangan mengarang. '.
            'Gunakan bahasa yang ringkas dan jelas.';

        try {
            $response = Http::timeout(120)
                ->withToken($apiKey)
                ->acceptJson()
                ->post($baseUrl, [
                    'model' => $model,
                    'temperature' => 0.2,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => "KONTEKS DOKUMEN:\n{$context}\n\nPERTANYAAN: {$question}"],
                    ],
                ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('LLM tidak bisa dihubungi: '.$e->getMessage());
        }

        if (! $response->successful()) {
            $detail = $response->json('error.message') ?? $response->body();
            throw new RuntimeException('LLM gagal menjawab (HTTP '.$response->status().'): '.mb_substr((string) $detail, 0, 300));
        }

        $content = trim((string) $response->json('choices.0.message.content', ''));
        if ($content === '') {
            throw new RuntimeException('LLM mengembalikan jawaban kosong.');
        }

        return $content;
    }
}
