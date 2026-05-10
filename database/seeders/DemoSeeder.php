<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();

        $this->command->info('DemoSeeder: użytkownicy gotowi.');
        $this->command->comment('QR codes zostaną dodane po implementacji modelu w Etapie 3.');
    }

    private function seedUsers(): void
    {
        User::factory()->create([
            'name' => 'Admin QR-Master',
            'email' => 'admin@qr-master.test',
            'email_verified_at' => now(),
        ])->assignRole(Role::Admin);

        User::factory()->create([
            'name' => 'Demo Pro User',
            'email' => 'pro@qr-master.test',
            'email_verified_at' => now(),
        ])->assignRole(Role::User);

        User::factory()->create([
            'name' => 'Demo Free User',
            'email' => 'free@qr-master.test',
            'email_verified_at' => null,
        ])->assignRole(Role::User);

        User::factory(7)->create()->each->assignRole(Role::User);
    }
}
