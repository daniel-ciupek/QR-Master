<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\QrCode\CreateQrCodeAction;
use App\Data\QrCodeData;
use App\Enums\QrCodeType;
use App\Enums\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = $this->seedUsers();
        $this->seedQrCodes($users['admin'], $users['pro'], $users['free']);

        $this->command->info('DemoSeeder: dane demo załadowane.');
        $this->command->comment('Loginy: admin@qr-master.test / pro@qr-master.test / free@qr-master.test — hasło: password');
    }

    /** @return array<string, User> */
    private function seedUsers(): array
    {
        [$admin, $adminCreated] = $this->firstOrCreateUser('admin@qr-master.test', 'Admin QR-Master');
        if ($adminCreated) {
            $admin->assignRole(Role::Admin);
        }

        [$pro, $proCreated] = $this->firstOrCreateUser('pro@qr-master.test', 'Demo Pro User');
        if ($proCreated) {
            $pro->assignRole(Role::User);
        }

        [$free, $freeCreated] = $this->firstOrCreateUser('free@qr-master.test', 'Demo Free User');
        if ($freeCreated) {
            $free->assignRole(Role::User);
        }

        if (User::count() < 10) {
            User::factory(7)->create()->each->assignRole(Role::User);
        }

        return ['admin' => $admin, 'pro' => $pro, 'free' => $free];
    }

    /** @return array{User, bool} */
    private function firstOrCreateUser(string $email, string $name): array
    {
        $existing = User::where('email', $email)->first();
        if ($existing !== null) {
            return [$existing, false];
        }

        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
        ]);

        return [$user, true];
    }

    private function seedQrCodes(User $admin, User $pro, User $free): void
    {
        // Skip QR seeding if data already exists for these users
        if ($admin->qrCodes()->exists() || $pro->qrCodes()->exists() || $free->qrCodes()->exists()) {
            return;
        }

        $action = app(CreateQrCodeAction::class);

        // ── Admin tags + codes ─────────────────────────────────────────
        $tagDocs = Tag::firstOrCreate(
            ['user_id' => $admin->id, 'name' => 'Dokumenty'],
            ['color' => '#6366f1']
        );

        $action->handle($admin, new QrCodeData(
            title: 'Strona główna QR-Master',
            type: QrCodeType::Url,
            destination_url: 'https://qr-master.app',
        ));

        $qr2 = $action->handle($admin, new QrCodeData(
            title: 'Dokumentacja API',
            type: QrCodeType::Url,
            destination_url: 'https://docs.qr-master.app',
            is_active: true,
            expires_at: now()->addYear(),
        ));
        $qr2->tags()->attach($tagDocs->id);

        // ── Pro user tags + codes ──────────────────────────────────────
        $tagMarketing = Tag::firstOrCreate(['user_id' => $pro->id, 'name' => 'Marketing'], ['color' => '#22c55e']);
        $tagKontakt = Tag::firstOrCreate(['user_id' => $pro->id, 'name' => 'Kontakt'], ['color' => '#06b6d4']);
        $tagPromo = Tag::firstOrCreate(['user_id' => $pro->id, 'name' => 'Promo'], ['color' => '#f97316']);

        $qr3 = $action->handle($pro, new QrCodeData(
            title: 'Kampania letnia 2026',
            type: QrCodeType::Url,
            destination_url: 'https://example.com/summer-sale',
        ));
        $qr3->tags()->attach([$tagMarketing->id, $tagPromo->id]);

        $qr4 = $action->handle($pro, new QrCodeData(
            title: 'Kontakt e-mail',
            type: QrCodeType::Email,
            destination_url: 'mailto:hello@example.com?subject=Zapytanie',
        ));
        $qr4->tags()->attach($tagKontakt->id);

        $qr5 = $action->handle($pro, new QrCodeData(
            title: 'Telefon biuro',
            type: QrCodeType::Phone,
            destination_url: 'tel:+48123456789',
        ));
        $qr5->tags()->attach($tagKontakt->id);

        $action->handle($pro, new QrCodeData(
            title: 'Wygasła promocja',
            type: QrCodeType::Url,
            destination_url: 'https://example.com/promo',
            is_active: true,
            expires_at: now()->subDay(),
        ));

        $action->handle($pro, new QrCodeData(
            title: 'Wyłączony kod',
            type: QrCodeType::Url,
            destination_url: 'https://example.com/hidden',
            is_active: false,
        ));

        // ── Free user codes ────────────────────────────────────────────
        $action->handle($free, new QrCodeData(
            title: 'Mój profil LinkedIn',
            type: QrCodeType::Url,
            destination_url: 'https://linkedin.com/in/example',
        ));

        $action->handle($free, new QrCodeData(
            title: 'SMS do mnie',
            type: QrCodeType::Sms,
            destination_url: 'smsto:+48987654321:Hej, masz mój QR!',
        ));
    }
}
