<?php

declare(strict_types=1);

namespace Database\Seeders;

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
        // Admin (do panelu Filament i Telescope/Pulse)
        User::factory()->create([
            'name' => 'Admin QR-Master',
            'email' => 'admin@qr-master.test',
            'email_verified_at' => now(),
        ]);

        // Pro user — demo konta Pro
        User::factory()->create([
            'name' => 'Demo Pro User',
            'email' => 'pro@qr-master.test',
            'email_verified_at' => now(),
        ]);

        // Free user — demo konta Free
        User::factory()->create([
            'name' => 'Demo Free User',
            'email' => 'free@qr-master.test',
            'email_verified_at' => null,
        ]);

        // 7 losowych userów
        User::factory(7)->create();
    }
}
