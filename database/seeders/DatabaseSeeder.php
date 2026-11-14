<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(KebunSeeder::class);
        $this->call(PtSeeder::class);
        $this->call(DistrikSeeder::class);
        $this->call(DivisiSeeder::class);
        $this->call(MasterRegIxSeeder::class);
        $this->call(VoucherStatusSeeder::class);
        $this->call(MasalahSeeder::class);
        $this->call(VoucherSeeder::class);
        $this->call(ObjekSeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(LhpSeeder::class);
    }
}
