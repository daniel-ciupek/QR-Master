<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\QrCode\CreateQrCodeAction;
use App\Data\QrCodeData;
use App\Enums\BioLinkTemplate;
use App\Enums\PlanTier;
use App\Enums\QrCodeType;
use App\Enums\Role;
use App\Enums\TeamRole;
use App\Models\BioLink;
use App\Models\BioLinkItem;
use App\Models\QrCode;
use App\Models\StatusCheck;
use App\Models\StatusIncident;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = $this->seedUsers();
        $this->seedQrCodes($users['admin'], $users['pro'], $users['free']);
        $this->seedExtraQrTypes($users['admin'], $users['pro']);
        $this->seedScanLogs($users['pro']);
        $this->seedTeam($users['admin'], $users['pro'], $users['free']);
        $this->seedBioLink($users['pro']);
        $this->seedStatusData();

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
        $admin->update(['plan_tier' => PlanTier::Business, 'onboarding_completed_at' => now()]);

        [$pro, $proCreated] = $this->firstOrCreateUser('pro@qr-master.test', 'Demo Pro User');
        if ($proCreated) {
            $pro->assignRole(Role::User);
        }
        $pro->update([
            'plan_tier' => PlanTier::Pro,
            'trial_ends_at' => now()->addDays(7),
            'onboarding_completed_at' => now(),
        ]);

        [$free, $freeCreated] = $this->firstOrCreateUser('free@qr-master.test', 'Demo Free User');
        if ($freeCreated) {
            $free->assignRole(Role::User);
        }
        $free->update(['plan_tier' => PlanTier::Free, 'onboarding_completed_at' => now()]);

        if (User::count() < 10) {
            $tiers = [PlanTier::Pro, PlanTier::Pro, PlanTier::Pro, PlanTier::Free, PlanTier::Free, PlanTier::Free, PlanTier::Free];
            User::factory(7)->create()->each(function (User $u, int $i) use ($tiers): void {
                $u->assignRole(Role::User);
                $u->update(['plan_tier' => $tiers[$i] ?? PlanTier::Free]);
            });
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

    private function seedExtraQrTypes(User $admin, User $pro): void
    {
        if (QrCode::where('user_id', $pro->id)->where('type', QrCodeType::Wifi)->exists()) {
            return;
        }

        $action = app(CreateQrCodeAction::class);

        // WiFi QR
        $action->handle($pro, new QrCodeData(
            title: 'WiFi Biuro',
            type: QrCodeType::Wifi,
            destination_url: 'WIFI:T:WPA;S:QR-Master-Demo;P:demo1234;H:false;;',
            settings: [
                'wifi_ssid' => 'QR-Master-Demo',
                'wifi_security' => 'WPA',
                'wifi_hidden' => false,
            ],
        ));

        // vCard QR
        $vcard = implode("\n", [
            'BEGIN:VCARD',
            'VERSION:4.0',
            'FN:Jan Kowalski',
            'N:Kowalski;Jan;;;',
            'ORG:QR-Master Sp. z o.o.',
            'TITLE:Product Manager',
            'TEL;TYPE=WORK:+48 500 123 456',
            'EMAIL:jan.kowalski@qr-master.app',
            'URL:https://qr-master.app',
            'END:VCARD',
        ]);
        $action->handle($pro, new QrCodeData(
            title: 'Wizytówka — Jan Kowalski',
            type: QrCodeType::VCard,
            destination_url: $vcard,
            settings: [
                'vcard_first_name' => 'Jan',
                'vcard_last_name' => 'Kowalski',
                'vcard_company' => 'QR-Master Sp. z o.o.',
                'vcard_job_title' => 'Product Manager',
                'vcard_website' => 'https://qr-master.app',
            ],
        ));

        // Geo QR (Warsaw)
        $action->handle($pro, new QrCodeData(
            title: 'Biuro Warszawa',
            type: QrCodeType::Geo,
            destination_url: 'geo:52.2297,21.0122',
            settings: ['geo_lat' => '52.2297', 'geo_lng' => '21.0122'],
        ));

        // Calendar QR
        $eventStart = now()->addDays(7)->format('Ymd\THis');
        $eventEnd = now()->addDays(7)->addHours(2)->format('Ymd\THis');
        $uid = md5('QR-Master Demo Day'.$eventStart).'@qr-master.app';
        $ical = implode("\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//QR-Master//EN',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTART:{$eventStart}",
            "DTEND:{$eventEnd}",
            'SUMMARY:QR-Master Demo Day',
            'DESCRIPTION:Prezentacja możliwości platformy QR-Master.',
            'LOCATION:Warszawa\, ul. Marszałkowska 1',
            'END:VEVENT',
            'END:VCALENDAR',
        ]);
        $action->handle($admin, new QrCodeData(
            title: 'QR-Master Demo Day',
            type: QrCodeType::Calendar,
            destination_url: $ical,
            settings: [
                'calendar_title' => 'QR-Master Demo Day',
                'calendar_start' => now()->addDays(7)->toDateTimeString(),
                'calendar_end' => now()->addDays(7)->addHours(2)->toDateTimeString(),
                'calendar_location' => 'Warszawa, ul. Marszałkowska 1',
                'calendar_description' => 'Prezentacja możliwości platformy QR-Master.',
            ],
        ));
    }

    private function seedScanLogs(User $pro): void
    {
        if (DB::table('scan_logs')
            ->whereIn('qr_code_id', QrCode::where('user_id', $pro->id)->pluck('id'))
            ->exists()
        ) {
            return;
        }

        $qrIds = QrCode::where('user_id', $pro->id)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (empty($qrIds)) {
            return;
        }

        $countries = $this->weightedList([
            'PL' => 40, 'DE' => 20, 'US' => 15, 'GB' => 10, 'FR' => 10,
            'IT' => 2, 'ES' => 1, 'NL' => 1, 'CZ' => 1,
        ]);
        $devices = $this->weightedList(['desktop' => 50, 'mobile' => 40, 'tablet' => 10]);
        $browsers = $this->weightedList(['Chrome' => 60, 'Safari' => 25, 'Firefox' => 10, 'Edge' => 5]);
        $languages = ['pl', 'en', 'de', 'fr', 'en'];

        $rows = [];
        $totalDays = 90;
        $totalScans = 600;

        // Weight per day: day 0 (oldest) = 1, day 89 (today) = 3 — linear ramp
        $weights = [];
        for ($d = 0; $d < $totalDays; $d++) {
            $weights[$d] = 1.0 + ($d / ($totalDays - 1)) * 2.0;
        }
        $weightSum = array_sum($weights);

        // Pre-assign scans per day
        $scansPerDay = [];
        $assigned = 0;
        for ($d = 0; $d < $totalDays; $d++) {
            $n = (int) round($weights[$d] / $weightSum * $totalScans);
            $scansPerDay[$d] = $n;
            $assigned += $n;
        }
        // Adjust for rounding drift
        $scansPerDay[$totalDays - 1] += $totalScans - $assigned;

        foreach ($scansPerDay as $daysAgo => $count) {
            $baseDate = now()->subDays($totalDays - 1 - $daysAgo);
            for ($i = 0; $i < $count; $i++) {
                $hour = $this->weightedHour();
                $minute = random_int(0, 59);
                $second = random_int(0, 59);
                $scannedAt = $baseDate->copy()->setTime($hour, $minute, $second);

                $rows[] = [
                    'qr_code_id' => $qrIds[array_rand($qrIds)],
                    'ip_hash' => hash('sha256', Str::random(16)),
                    'country' => $countries[array_rand($countries)],
                    'device_type' => $devices[array_rand($devices)],
                    'browser' => $browsers[array_rand($browsers)],
                    'language' => $languages[array_rand($languages)],
                    'scanned_at' => $scannedAt->format('Y-m-d H:i:s'),
                ];
            }
        }

        // Bulk insert in chunks of 200
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('scan_logs')->insert($chunk);
        }
    }

    private function seedTeam(User $admin, User $pro, User $free): void
    {
        if (Team::where('name', 'Acme Corp')->exists()) {
            return;
        }

        $team = Team::create([
            'owner_id' => $admin->id,
            'name' => 'Acme Corp',
            'plan_tier' => PlanTier::Business,
            'settings' => [
                'brand_name' => 'Acme Corp',
                'primary_color' => '#0f172a',
                'powered_by_hidden' => false,
            ],
        ]);

        // Owner is also a member in pivot
        $team->members()->attach($admin->id, [
            'role' => TeamRole::Owner->value,
            'joined_at' => now()->subDays(30),
        ]);
        $team->members()->attach($pro->id, [
            'role' => TeamRole::Editor->value,
            'joined_at' => now()->subDays(14),
        ]);
        $team->members()->attach($free->id, [
            'role' => TeamRole::Viewer->value,
            'joined_at' => now()->subDays(7),
        ]);
    }

    private function seedBioLink(User $pro): void
    {
        if (BioLink::where('user_id', $pro->id)->exists()) {
            return;
        }

        $action = app(CreateQrCodeAction::class);
        $qr = $action->handle($pro, new QrCodeData(
            title: 'Demo Bio Link',
            type: QrCodeType::BioLink,
            destination_url: null,
        ));

        $bioLink = BioLink::create([
            'user_id' => $pro->id,
            'qr_code_id' => $qr->id,
            'slug' => 'demo-user',
            'title' => 'Jan Kowalski',
            'bio' => 'Product Manager @ QR-Master. Tworzę narzędzia, które łączą świat fizyczny z cyfrowym.',
            'template' => BioLinkTemplate::Minimal,
            'theme' => [
                'primary_color' => '#6366f1',
                'bg_color' => '#0f172a',
                'text_color' => '#f1f5f9',
                'button_shape' => 'pill',
                'font' => 'inter',
            ],
        ]);

        // Update QR destination to public bio-link URL
        $qr->update(['destination_url' => url("/bio/{$bioLink->slug}")]);

        $items = [
            ['title' => 'Moja strona', 'url' => 'https://qr-master.app', 'icon' => 'globe'],
            ['title' => 'Twitter / X', 'url' => 'https://twitter.com/qrmaster', 'icon' => 'twitter'],
            ['title' => 'LinkedIn', 'url' => 'https://linkedin.com/in/jan-kowalski', 'icon' => 'linkedin'],
            ['title' => 'Napisz do mnie', 'url' => 'mailto:jan.kowalski@qr-master.app', 'icon' => 'mail'],
        ];

        foreach ($items as $order => $item) {
            BioLinkItem::create([
                'bio_link_id' => $bioLink->id,
                'title' => $item['title'],
                'url' => $item['url'],
                'icon' => $item['icon'],
                'sort_order' => $order,
                'is_active' => true,
            ]);
        }
    }

    private function seedStatusData(): void
    {
        if (StatusCheck::count() > 0) {
            return;
        }

        $services = ['api', 'dashboard', 'qr-redirect', 'analytics', 'storage'];
        $rows = [];

        foreach ($services as $service) {
            for ($d = 89; $d >= 0; $d--) {
                $checksPerDay = random_int(2, 4);
                for ($c = 0; $c < $checksPerDay; $c++) {
                    $hour = random_int(0, 23);
                    $minute = random_int(0, 59);
                    $checkedAt = now()->subDays($d)->setTime($hour, $minute, 0);

                    // 95% operational, 3% degraded, 2% outage
                    $rand = random_int(1, 100);
                    $status = match (true) {
                        $rand <= 95 => 'operational',
                        $rand <= 98 => 'degraded',
                        default => 'outage',
                    };

                    $rows[] = [
                        'service' => $service,
                        'status' => $status,
                        'response_time_ms' => match ($status) {
                            'operational' => random_int(40, 180),
                            'degraded' => random_int(500, 2000),
                            default => null,
                        },
                        'message' => $status !== 'operational' ? 'Elevated response times detected.' : null,
                        'checked_at' => $checkedAt->format('Y-m-d H:i:s'),
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('status_checks')->insert($chunk);
        }

        // One resolved maintenance incident from last week
        if (StatusIncident::count() === 0) {
            StatusIncident::create([
                'title' => 'Scheduled Database Maintenance',
                'description' => 'Planned maintenance window for PostgreSQL upgrades and index rebuilds. All services restored to normal operation.',
                'severity' => 'minor',
                'status' => 'resolved',
                'is_maintenance' => true,
                'starts_at' => now()->subDays(8)->setTime(2, 0, 0),
                'ends_at' => now()->subDays(8)->setTime(3, 30, 0),
            ]);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────

    /**
     * Build a flat array for weighted random selection.
     *
     * @param  array<string, int>  $weights  key => relative weight
     * @return list<string>
     */
    private function weightedList(array $weights): array
    {
        $list = [];
        foreach ($weights as $value => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $list[] = $value;
            }
        }

        return $list;
    }

    /** Returns an hour 0-23 with peaks around 10-12 and 18-20. */
    private function weightedHour(): int
    {
        $hourWeights = [
            0 => 1, 1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 2,
            6 => 3, 7 => 5, 8 => 7, 9 => 9, 10 => 12, 11 => 12,
            12 => 10, 13 => 8, 14 => 7, 15 => 7, 16 => 8, 17 => 9,
            18 => 12, 19 => 12, 20 => 10, 21 => 7, 22 => 4, 23 => 2,
        ];

        $pool = [];
        foreach ($hourWeights as $hour => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $hour;
            }
        }

        return $pool[array_rand($pool)];
    }
}
