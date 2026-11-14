<?php

namespace App\Console\Commands;

use App\Services\FonnteService;
use Illuminate\Console\Command;

class FonnteGroups extends Command
{
    protected $signature = 'fonnte:groups {--fetch : Refresh daftar grup dulu (fetch-group)}';
    protected $description = 'Tampilkan daftar WhatsApp group Fonnte (id + nama) untuk FONNTE_LHP_GROUP_ID';

    public function handle(FonnteService $fonnte): int
    {
        try {
            if ($this->option('fetch')) {
                $fonnte->fetchGroups();
                $this->info('Fetch group OK, ambil daftar terbaru...');
            }
            $data = $fonnte->getGroups();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $groups = $data['data'] ?? $data['groups'] ?? [];
        if (empty($groups)) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(['ID', 'Nama'], collect($groups)->map(fn ($g) => [
            $g['id'] ?? $g['group_id'] ?? '-',
            $g['name'] ?? $g['subject'] ?? '-',
        ])->all());

        return self::SUCCESS;
    }
}
