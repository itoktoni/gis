<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FonnteService
{
    private const SEND_URL = 'https://api.fonnte.com/send';
    private const FETCH_GROUP_URL = 'https://api.fonnte.com/fetch-group';
    private const GET_GROUP_URL = 'https://api.fonnte.com/get-whatsapp-group';

    public function configured(): bool
    {
        return filled(config('fonnte.token'));
    }

    /**
     * @throws RuntimeException
     */
    public function send(string $target, string $message): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('FONNTE_TOKEN belum diisi di .env.');
        }

        $response = Http::timeout(60)
            ->withHeaders(['Authorization' => config('fonnte.token')])
            ->asForm()
            ->post(self::SEND_URL, [
                'target' => $target,
                'message' => $message,
                'countryCode' => (string) config('fonnte.country_code', '62'),
            ]);

        $data = $response->json() ?? [];

        if (! $response->successful() || ($data['status'] ?? false) !== true) {
            throw new RuntimeException('Fonnte gagal: '.($data['reason'] ?? $response->body() ?: 'HTTP '.$response->status()));
        }

        return $data;
    }

    public function sendToLhpGroup(string $message): array
    {
        $groupId = (string) config('fonnte.group_id');

        if ($groupId === '') {
            throw new RuntimeException('FONNTE_LHP_GROUP_ID belum diisi di .env.');
        }

        return $this->send($groupId, $message);
    }

    /** Grup WA untuk rekap absen (default ikut grup LHP bila ID khusus belum diisi). */
    public function sendToAbsenGroup(string $message): array
    {
        $groupId = (string) (config('fonnte.absen_group_id') ?: config('fonnte.group_id'));

        if ($groupId === '') {
            throw new RuntimeException('FONNTE_ABSEN_GROUP_ID (atau FONNTE_LHP_GROUP_ID) belum diisi di .env.');
        }

        return $this->send($groupId, $message);
    }

    /**
     * Refresh daftar grup di sisi Fonnte. Panggil sekali saat join grup baru.
     *
     * @throws RuntimeException
     */
    public function fetchGroups(): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('FONNTE_TOKEN belum diisi di .env.');
        }

        $response = Http::timeout(60)
            ->withHeaders(['Authorization' => config('fonnte.token')])
            ->asForm()
            ->post(self::FETCH_GROUP_URL);

        $data = $response->json() ?? [];

        if (! $response->successful()) {
            throw new RuntimeException('Fetch group gagal: '.($response->body() ?: 'HTTP '.$response->status()));
        }

        return $data;
    }

    /**
     * Daftar grup WhatsApp (id + nama) untuk dipakai sebagai target.
     *
     * @throws RuntimeException
     */
    public function getGroups(): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('FONNTE_TOKEN belum diisi di .env.');
        }

        $response = Http::timeout(60)
            ->withHeaders(['Authorization' => config('fonnte.token')])
            ->asForm()
            ->post(self::GET_GROUP_URL);

        $data = $response->json() ?? [];

        if (! $response->successful()) {
            throw new RuntimeException('Get group gagal: '.($response->body() ?: 'HTTP '.$response->status()));
        }

        return $data;
    }
}
