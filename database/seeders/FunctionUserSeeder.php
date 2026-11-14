<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FunctionUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['Tim Tanaman', 'tanaman@kalteng.test', 'tanaman'],
            ['Tim Finance', 'finance@kalteng.test', 'finance'],
            ['Sekretaris', 'sekretaris@kalteng.test', 'sekretaris'],
            ['Distrik', 'distrik@kalteng.test', 'distrik'],
        ];

        foreach ($users as [$name, $email, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'Kalteng2026#', 'role' => $role]
            );
        }
    }
}
