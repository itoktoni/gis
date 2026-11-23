<?php

namespace App\Services;

use App\Models\CryptoKey;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use RuntimeException;

class CryptoService
{
    private const CIPHER = 'aes-256-gcm';

    private function opensslConfigPath(): ?string
    {
        // @ pada is_file(): di server produksi open_basedir membatasi akses ke
        // /etc/ssl/certs; tanpa @, is_file() melempar ErrorException (500) saat
        // memeriksa kandidat config yang tidak ada di host (lihat bug login APK).
        $env = getenv('OPENSSL_CONF');

        if ($env && @is_file($env)) {
            return $env;
        }

        $candidates = [
            ini_get('openssl.cafile') ? dirname(ini_get('openssl.cafile')).DIRECTORY_SEPARATOR.'openssl.cnf' : null,
            'C:\ProgramData\envkit\services\php\\'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION.'\extras\ssl\openssl.cnf',
            glob('C:\ProgramData\envkit\services\php\*\extras\ssl\openssl.cnf')[0] ?? null,
        ];

        foreach ($candidates as $path) {
            if ($path && @is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Generate an RSA-2048 key pair for a user and store it encrypted.
     */
    public function generateKeyPairForUser(User $user): CryptoKey
    {
        $config = $this->opensslConfigPath();
        $options = [
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ];

        if ($config) {
            $options['config'] = $config;
        }

        $res = openssl_pkey_new($options);

        if ($res === false) {
            throw new RuntimeException('Failed to generate RSA key pair');
        }

        openssl_pkey_export($res, $privateKeyPem, null, $options);
        $details = openssl_pkey_get_details($res);
        $publicKeyPem = $details['key'];

        $fingerprint = hash('sha256', $publicKeyPem);

        return CryptoKey::create([
            'user_id' => $user->id,
            'public_key' => $publicKeyPem,
            'encrypted_private_key' => $this->encryptPrivateKey($privateKeyPem),
            'fingerprint' => $fingerprint,
            'is_active' => true,
        ]);
    }

    public function encryptPrivateKey(string $privateKeyPem): string
    {
        $masterKey = $this->masterKey();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($privateKeyPem, self::CIPHER, $masterKey, OPENSSL_RAW_DATA, $iv, $tag);

        if ($ciphertext === false) {
            throw new RuntimeException('Failed to encrypt private key');
        }

        return base64_encode($iv.$tag.$ciphertext);
    }

    public function decryptPrivateKey(CryptoKey $key): string
    {
        $masterKey = $this->masterKey();
        $raw = base64_decode($key->encrypted_private_key, true);
        if ($raw === false || strlen($raw) < 28) {
            throw new RuntimeException('Corrupt encrypted private key');
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $masterKey, OPENSSL_RAW_DATA, $iv, $tag);

        if ($plaintext === false) {
            throw new RuntimeException('Failed to decrypt private key');
        }

        return $plaintext;
    }

    public function masterKey(): string
    {
        $key = Config::get('app.master_key');
        if (! $key) {
            $key = env('MASTER_KEY');
        }
        if (! $key) {
            throw new RuntimeException('MASTER_KEY is not configured');
        }

        $decoded = base64_decode($key, true);
        if ($decoded !== false && strlen($decoded) >= 32) {
            return $decoded;
        }

        if (strlen($key) < 32) {
            throw new RuntimeException('MASTER_KEY must be at least 32 bytes');
        }

        return substr($key, 0, 32);
    }

    /**
     * Create a signature over a payload using the user's active private key.
     */
    public function sign(User $user, string $payload): array
    {
        $key = $user->activeCryptoKey;
        if (! $key) {
            throw new RuntimeException('No active crypto key found');
        }

        $privateKeyPem = $this->decryptPrivateKey($key);
        $privateKey = openssl_pkey_get_private($privateKeyPem);

        openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return [
            'signature' => base64_encode($signature),
            'fingerprint' => $key->fingerprint,
            'crypto_key_id' => $key->id,
        ];
    }

    /**
     * Verify a payload against a signature using a public key PEM.
     */
    public function verify(string $payload, string $signatureB64, string $publicKeyPem): bool
    {
        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if ($publicKey === false) {
            return false;
        }

        $signature = base64_decode($signatureB64, true);
        if ($signature === false) {
            return false;
        }

        return openssl_verify($payload, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * Build the canonical signed payload.
     */
    public function buildPayload(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }
}
