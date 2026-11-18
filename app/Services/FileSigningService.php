<?php

namespace App\Services;

use App\Models\Signature;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class FileSigningService
{
    /**
     * Marker used to delimit the embedded payload from original file bytes.
     */
    public const MARKER = "\x00DIGITALSIGV1:";

    public function __construct(private CryptoService $crypto) {}

    /**
     * Sign a file: hash original bytes, build metadata, sign, embed.
     * Returns the signed bytes plus the record fields to persist.
     */
    public function signFile(UploadedFile $file, User $user, ?string $label, ?string $expiredAt): array
    {
        $originalBytes = file_get_contents($file->getRealPath());
        $contentHash = hash('sha256', $originalBytes);

        $metadata = [
            'hash' => $contentHash,
            'userId' => $user->id,
            'timestamp' => now()->toIso8601String(),
            'expiredAt' => $expiredAt ? date('c', strtotime($expiredAt)) : null,
            'label' => $label,
            'keyFingerprint' => $user->activeCryptoKey->fingerprint ?? null,
        ];

        $canonicalPayload = $this->crypto->buildPayload($metadata);
        $sign = $this->crypto->sign($user, $canonicalPayload);

        $embedded = json_encode([
            'metadata' => $metadata,
            'signature' => $sign['signature'],
        ], JSON_UNESCAPED_SLASHES);

        $signedBytes = $originalBytes.self::MARKER.base64_encode($embedded);

        return [
            'bytes' => $signedBytes,
            'content_hash' => $contentHash,
            'embedded_signature' => $embedded,
            'expired_at' => $expiredAt,
        ];
    }

    /**
     * Sign a string payload and return the embed data.
     */
    public function signString(User $user, string $string, ?string $label, ?string $expiredAt): array
    {
        $contentHash = hash('sha256', $string);

        $metadata = [
            'hash' => $contentHash,
            'userId' => $user->id,
            'timestamp' => now()->toIso8601String(),
            'expiredAt' => $expiredAt ? date('c', strtotime($expiredAt)) : null,
            'label' => $label,
            'keyFingerprint' => $user->activeCryptoKey->fingerprint ?? null,
        ];

        $canonicalPayload = $this->crypto->buildPayload($metadata);
        $sign = $this->crypto->sign($user, $canonicalPayload);

        return [
            'metadata' => $metadata,
            'embedded_signature' => json_encode([
                'metadata' => $metadata,
                'signature' => $sign['signature'],
            ], JSON_UNESCAPED_SLASHES),
            'content_hash' => $contentHash,
            'expired_at' => $expiredAt,
        ];
    }

    /**
     * Extract the embedded payload + original bytes from a signed file.
     * Returns [originalBytes, embeddedArray] or null if no signature found.
     * Throws RuntimeException if the marker is present but payload is corrupt.
     */
    public function extractFromFile(string $bytes): ?array
    {
        $markerPos = strrpos($bytes, self::MARKER);
        if ($markerPos === false) {
            return null;
        }

        $originalBytes = substr($bytes, 0, $markerPos);
        $embeddedJson = base64_decode(substr($bytes, $markerPos + strlen(self::MARKER)), true);
        $embedded = json_decode($embeddedJson ?: '', true);

        if (! is_array($embedded) || ! isset($embedded['metadata'], $embedded['signature'])) {
            throw new RuntimeException('Corrupt embedded signature');
        }

        return [$originalBytes, $embedded];
    }
}
