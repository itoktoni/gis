<?php

namespace App\Services;

use App\Models\CryptoKey;
use App\Models\Signature;
use App\Models\ValidationLog;
use Illuminate\Http\Request;

class ValidationService
{
    public function __construct(
        private CryptoService $crypto,
        private FileSigningService $fileSigning,
    ) {}

    /**
     * Validate a signed file. Returns result + details.
     */
    public function validateFile(string $bytes, Request $request): array
    {
        try {
            $extracted = $this->fileSigning->extractFromFile($bytes);
        } catch (\RuntimeException $e) {
            return $this->result('TIDAK VALID', 'Tanda tangan digital rusak atau dimodifikasi', null);
        }

        if (! $extracted) {
            return $this->result('TIDAK DITEMUKAN', 'Dokumen tidak mengandung tanda tangan digital', null);
        }

        [$originalBytes, $embedded] = $extracted;

        return $this->verifyEmbedded($embedded, $originalBytes, $request);
    }

    /**
     * Validate a signature by its ID (from QR / URL).
     */
    public function validateById(string $signatureId, Request $request): array
    {
        $signature = Signature::with('user', 'stringData')->find($signatureId);
        if (! $signature) {
            return $this->result('TIDAK DITEMUKAN', 'Tanda tangan tidak ditemukan', null);
        }

        $embedded = json_decode($signature->embedded_signature, true);

        if ($signature->status === 'revoked') {
            return $this->result('DICABUT', 'Tanda tangan telah dicabut oleh creator', $signature);
        }

        if ($signature->isExpired()) {
            return $this->result('KEDALUWARSA', 'Tanda tangan telah melewati tanggal kedaluwarsa', $signature);
        }

        // Verify the stored signature is cryptographically valid
        $metadata = $embedded['metadata'] ?? null;
        $signatureB64 = $embedded['signature'] ?? null;
        $key = CryptoKey::where('fingerprint', $metadata['keyFingerprint'] ?? null)->first();

        if (! $metadata || ! $signatureB64 || ! $key) {
            return $this->result('TIDAK VALID', 'Tanda tangan tidak dapat diverifikasi', $signature);
        }

        $canonical = $this->crypto->buildPayload($metadata);
        if (! $this->crypto->verify($canonical, $signatureB64, $key->public_key)) {
            return $this->result('TIDAK VALID', 'Tanda tangan tidak cocok', $signature);
        }

        return $this->result('VALID', 'Dokumen asli dan tanda tangan valid', $signature);
    }

    private function verifyEmbedded(array $embedded, string $originalBytes, Request $request): array
    {
        $metadata = $embedded['metadata'] ?? null;
        $signatureB64 = $embedded['signature'] ?? null;

        if (! $metadata || ! $signatureB64) {
            return $this->result('TIDAK VALID', 'Data tanda tangan tidak lengkap', null);
        }

        // Find the key by fingerprint
        $key = CryptoKey::with('user')->where('fingerprint', $metadata['keyFingerprint'] ?? null)->first();
        if (! $key) {
            return $this->result('TIDAK VALID', 'Kunci publik creator tidak ditemukan', null);
        }

        // Verify the content hash matches the original bytes
        $currentHash = hash('sha256', $originalBytes);
        if (! hash_equals($metadata['hash'] ?? '', $currentHash)) {
            return $this->result('TIDAK VALID', 'Dokumen telah dimodifikasi setelah ditandatangani', null);
        }

        // Verify the cryptographic signature over the metadata
        $canonical = $this->crypto->buildPayload($metadata);
        if (! $this->crypto->verify($canonical, $signatureB64, $key->public_key)) {
            return $this->result('TIDAK VALID', 'Tanda tangan digital tidak cocok', null);
        }

        // Find the signature record to check status & expiry
        $signature = Signature::with('user')
            ->where('crypto_key_id', $key->id)
            ->where('content_hash', $metadata['hash'])
            ->first();

        if ($signature && $signature->status === 'revoked') {
            return $this->result('DICABUT', 'Tanda tangan telah dicabut oleh creator', $signature);
        }

        if ($signature && $signature->isExpired()) {
            return $this->result('KEDALUWARSA', 'Tanda tangan telah melewati tanggal kedaluwarsa', $signature);
        }

        if ($metadata['expiredAt'] && strtotime($metadata['expiredAt']) < time()) {
            return $this->result('KEDALUWARSA', 'Tanda tangan telah melewati tanggal kedaluwarsa', $signature);
        }

        return $this->result('VALID', 'Dokumen asli dan tanda tangan valid', $signature);
    }

    private function result(string $status, string $message, ?Signature $signature): array
    {
        $data = [
            'status' => $status,
            'message' => $message,
        ];

        if ($signature) {
            $data['signature'] = [
                'id' => $signature->id,
                'type' => $signature->type,
                'label' => $signature->label,
                'signed_at' => $signature->created_at?->toIso8601String(),
                'expired_at' => $signature->expired_at?->toIso8601String(),
                'creator' => [
                    'id' => $signature->user_id,
                    'name' => $signature->user?->name,
                ],
                'content_hash' => $signature->content_hash,
                'string' => $signature->stringData?->original_string,
            ];
        }

        return $data;
    }

    public function log(Signature $signature, string $result, Request $request): void
    {
        ValidationLog::create([
            'signature_id' => $signature->id,
            'result' => $result,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
