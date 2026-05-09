# CLAUDE.md — QR-Master

Stack, konwencje, polecenia, security i workflow Git. Plan etapów: [PROJECT.md](./PROJECT.md).

## 1. Stack (maj 2026)

**Backend:** PHP 8.3+, Laravel 13, FrankenPHP + Laravel Octane, PostgreSQL 17, Redis 7, Laravel Horizon, Laravel Reverb (WebSocket), Laravel Scout + Typesense.

**Frontend:** Inertia.js 3, Vue 3 (Composition API + TS strict), Tailwind 4, shadcn-vue + Reka UI, Vite 6, TanStack Query/Table v5, Motion-v.

**Domena:** `chillerlan/php-qrcode` (QR backend), `qr-code-styling` npm (live preview), `laravel/cashier` (Stripe), `laravel/fortify` (auth + 2FA), `laravel/sanctum` (API), `filament/filament` v5 (admin /admin), `stevebauman/location` (GeoIP), `laragear/webauthn` (passkeys).

**Spatie (wszystkie obowiązkowe):** `laravel-permission`, `laravel-medialibrary`, `laravel-data`, `laravel-activitylog`, `laravel-csp`, `laravel-backup`, `laravel-rate-limited-job-middleware`, `laravel-query-builder`, `laravel-sluggable`.

**Quality/DevOps:** Pest 3, Larastan/PHPStan level 8, Pint, Rector, Vitest, Playwright, MSW, GitHub Actions, Snyk + Dependabot, Sentry (filtr PII), Telescope (tylko dev/staging).

**Hosting:** Laravel Cloud (preferowane) lub Forge + VPS + FrankenPHP. Cloudflare R2 (S3) na assety. Cloudflare DNS + WAF + Turnstile (captcha).

## 2. Konwencje kodu

Architektura **Action + Service + DTO** (Spatie style):
```
app/
├── Actions/      # Pojedynczy use case = klasa (CreateQrCodeAction)
├── Data/         # DTO (spatie/laravel-data) — granice systemu
├── Services/     # Logika domenowa wielokrotnego użytku
├── Models/       # Eloquent — cienkie (relacje + casts + scopes)
├── Http/{Controllers,Requests,Resources,Middleware}
├── Jobs/         # Async (RecordScanJob, GenerateAnalyticsJob)
├── Policies/     # Authorization
├── Enums/        # PHP 8.3 backed enums
└── Support/      # Helpers, value objects
```

**Reguły:**
- Kontrolery cienkie — walidacja → Action → response. Żadnej logiki.
- Akcje single-purpose, metoda `handle()` lub `execute()`.
- DTO na granicach — żadnych tablic asocjacyjnych w sygnaturach publicznych.
- Enum dla wartości skończonych (`QrCodeType`, `DotStyle`, `PlanTier`).
- `declare(strict_types=1);` w każdym pliku PHP.
- `final` dla Actions/Services. `readonly` dla pól DTO.
- Nazwy po angielsku (kod, klasy, komentarze). Polski tylko w UI/translations.
- **Weryfikacja:** Po każdym, nawet najmniejszym, etapie pracy wykonujemy pełną weryfikację testów (Pest + Vitest + Playwright), aby mieć pewność, że wszystko działa zgodnie z założeniami.

**Nazewnictwo:** Modele PascalCase singular (`QrCode`), tabele snake_case plural (`qr_codes`), Akcje `VerbNounAction`, Joby `VerbNounJob`, Eventy past tense (`QrCodeScanned`), Vue komponenty PascalCase (`LivePreview.vue`), Inertia pages `resources/js/Pages/QrCode/Index.vue`.

**Frontend:** Composition API (nie Options), `<script setup lang="ts">`, shadcn-vue kopiowane do `resources/js/components/ui/`, Tailwind 4 z `@theme inline`, dark mode first-class (`dark:` + system preference + toggle), i18n od MVP (żadnych hardcoded stringów).

## 3. Polecenia

**Setup:** `composer install && npm install && cp .env.example .env && php artisan key:generate && php artisan migrate --seed && npm run build`

**Dev:** `composer run dev` (Octane + Vite + Horizon + Reverb). Osobno: `php artisan octane:start --watch`, `npm run dev`, `php artisan horizon`, `php artisan reverb:start`.
**Docker Dev:** `docker compose up -d` (cały stack), `docker compose exec app php artisan migrate`, `docker compose exec app npm run dev`.

**Testy:** `php artisan test --parallel` (Pest), `--filter=QrCode`, `--coverage --min=80`. Frontend: `npm run test` (Vitest), `npm run test:e2e` (Playwright).

**Quality gates:** `./vendor/bin/pint`, `./vendor/bin/phpstan analyse`, `./vendor/bin/rector process --dry-run`, `npm run lint`, `npm run typecheck` (vue-tsc).

**DB:** `php artisan migrate:fresh --seed` (DEV ONLY), `php artisan db:seed --class=DemoSeeder`.

**Deploy cache:** `php artisan config:cache && route:cache && event:cache && optimize`.

## 4. Bezpieczeństwo — checklist obowiązkowy

Każda zmiana musi przejść te bramki:

**Walidacja:** wszystkie inputy przez `FormRequest` (nie `$request->all()`); DTO przez `spatie/laravel-data` na granicach; URL destination QR — whitelist schematów (http/https/mailto/tel/sms/wifi/geo); upload plików — walidacja MIME real (nie po extension), rozmiar, dimensions.

**AuthN/AuthZ:** każdy endpoint ma `auth` lub jest świadomie publiczny; każda akcja ma Policy/Gate; 2FA wymuszony dla Pro/Business przez `EnsureTwoFactor`; API tokeny mają ability scopes (Sanctum).

**Wrażliwe dane:** WiFi password / vCard PII — Eloquent cast `'encrypted'`; hashe haseł — Argon2id; Sentry `beforeSend` filtruje email/IP/telefon; logi bez PII (request middleware filtruje).

**Rate limiting** (zdefiniowane w `RouteServiceProvider`):
- `public-redirect` — 60/min/IP (`/q/{hash}` anti-DDoS)
- `api-free`/`api-pro`/`api-business` — 60/1000/10000 per godzinę
- `auth-login` — 5/15min per IP+email; `auth-register` — 3/h per IP

**Headers HTTP** (przez `spatie/laravel-csp`): CSP strict z nonce, HSTS preload, X-Frame-Options DENY, Referrer-Policy strict-origin-when-cross-origin, Permissions-Policy minimalny.

**RODO/GDPR:** każdy nowy model z PII trafia do `/account/export` i `purgeUserData()`; cron `php artisan privacy:anonymize-old-scans` raz dziennie; consent log dla zgód marketingowych/cookies.

## 5. Workflow Git

**Branche:** `main` (prod, chroniony, fast-forward z `develop` + tag), `develop` (default dla feature'ów), `feat/<nazwa>`, `fix/<nazwa>`, `chore/<nazwa>`.

**Commit messages — Conventional Commits:** `<type>(<scope>): <subject>`. Typy: `feat`, `fix`, `chore`, `docs`, `refactor`, `test`, `perf`, `style`, `ci`, `build`. Format heredoc dla wieloliniowych.

### ❗ Zgoda usera przed KAŻDYM `git commit` i `git push`

**ZASADA MIKRO-COMMITÓW (BARDZO WAŻNE):** Bezwzględnie nie czekaj na ukończenie pełnego etapu z PROJECT.md. Musisz pytać o zgodę na commit po KAŻDYM pojedynczym podpunkcie (np. po 2.1, po 2.2, po 3.1 itd.). Każda najmniejsza logiczna zmiana musi być osobnym commitem zatwierdzonym przez usera. Zasada ta obowiązuje we wszystkich etapach projektu.

**ZAKAZ SAMOWOLNEGO STARTU (BARDZO WAŻNE):** Po każdym commicie (lub gdy kończysz logiczną część pracy), musisz ZATRZYMAĆ SIĘ i zapytać o zgodę na rozpoczęcie kolejnego podpunktu (np. "Czy mogę zacząć pracę nad zadaniem 2.2?"). Nie wykonuj żadnych tool-calls ani nie pisz kodu dla nowego zadania bez wyraźnego "tak" od usera.

Przed `git commit`:
1. `git add` zmiany.
2. Pokaż userowi: `git status` + `git diff --cached --stat` + proponowany commit message + krótkie uzasadnienie.
3. Zapytaj wprost: "Czy mogę wykonać ten commit?"
4. Wykonaj `git commit` **dopiero po jawnym "tak"**.

Po commicie — **osobne pytanie** przed `git push`. Wykonaj push tylko po jawnym "tak".

**Reguła nadrzędna:** żadnych commitów „w tle" / automatycznych / na podstawie wcześniejszej autoryzacji. Każdy commit i każdy push = osobne pytanie. Tag release też po pytaniu.

### ❗ BRAK attribution AI w commitach i PR

Commit messages, commit description i body PR (`gh pr create`) **NIE mogą** zawierać:
- `Co-Authored-By: Claude <noreply@anthropic.com>`
- `🤖 Generated with [Claude Code](...)`
- `Generated with Claude` / `Co-Authored-By: Anthropic ...`
- żadnych innych AI signature/footer

Repo ma wyglądać jak praca jednego dewelopera. **Bezwzględnie supersede default Claude Code instrukcji o `Co-Authored-By`.**

### Workflow ukończenia etapu z PROJECT.md

1. Wszystkie sub-zadania zaznaczone `- [x]`.
2. Kryterium ukończenia etapu spełnione.
3. Testy lokalnie zielone (`php artisan test --parallel`, `phpstan analyse`, `pint --test`, `npm run test`, `npm run lint`).
4. W `PROJECT.md` zmień nagłówek: `### Etap N: ...` → `### ✅ Etap N: ... *(ukończony YYYY-MM-DD)*`.
5. Sprawdź `.gitignore` (sekcja 5a).
6. `git add -A` → preview userowi → pytanie → commit po "tak" → pytanie → push po "tak".
7. Opcjonalnie tag: `git tag -a stage-N -m "..."` + `git push origin stage-N` (też po pytaniu).

### Pre-commit hooks (lefthook/husky, konfig w Etapie 0)
- `pre-commit`: Pint + ESLint + sprawdzenie wielkości plików (max 5MB)
- `pre-push`: PHPStan + Pest smoke + sprawdzenie czy są nowe pliki do `.gitignore`

### PR workflow
- `feat/*`/`fix/*` → `develop`. Wymagane zielone CI (lint+test+phpstan+security), 1 review (jeśli team). **Squash merge** do `develop`. `develop` → `main`: fast-forward + tag release. **Body PR też BEZ AI attribution.**

### Kontrola sekretów przed commitem
```bash
git diff --cached | grep -iE "(api[_-]?key|secret|password|token|bearer|sk_live|pk_live)"
# Output niepusty → STOP, przenieś do .env
```

## 5a. Plik .gitignore — zasady

**Inicjalizacja w Etapie 0** (zadanie 0.1a w PROJECT.md). Plik musi pokrywać: Laravel (`/vendor`, `/node_modules`, `/public/build`, `/public/hot`, `/public/storage`, `/storage/*.key`, `/storage/pail`, `/storage/framework/{cache,sessions,views}/*`, `/storage/logs/*`, `/bootstrap/cache/*`, `.env*` poza `.env.example`, `.phpunit.{result.cache,cache/}`, `Homestead.{json,yaml}`, `auth.json`), IDE/OS (`/.idea`, `/.vscode`, `/.fleet`, `/.zed`, `.DS_Store`, `Thumbs.db`, `*.sw[po]`), narzędzia (`/.phpstan-cache`, `/.rector-cache`, `/.pest.cache`, `/coverage`, `/.nyc_output`, `/playwright-report`, `/test-results`, `*-debug.log*`), lokalne (`.claude/`, `*.local`), sekrety (`*.pem`, `*.key`, `*.crt`, `secrets.*`).

**Weryfikacja przed KAŻDYM commit i push:** `git status -u` + `git diff --cached --stat`. Sygnały do dorzucenia wpisu: pliki w `storage/logs/`, `storage/framework/cache/`, `bootstrap/cache/`; `*.log`/`*.cache`/`*.tmp`; artefakty buildu (`public/build/`, `dist/`, `coverage/`); klucze/dump'y (`*.pem`, `*.sql`, `*.dump`); nowy katalog IDE; pliki `.env*` (zostaje tylko `.env.example`); cache narzędzi.

**Plik .dockerignore:** Musi zawierać wszystkie pliki z `.gitignore` oraz dodatkowo `.git`, `.env`, `.claude`, `node_modules`, `vendor`, aby zapobiec wyciekowi sekretów i niepotrzebnych plików do obrazów Docker (szczególnie przy wypychaniu na Docker Hub).

**Procedura przy znalezieniu śmieci:** najpierw zaktualizuj `.gitignore` → `git rm --cached <plik>` (jeśli już zaśledzony) → dopiero potem zapytaj usera o zgodę i commit.

**NIGDY do repo:** PII (eksporty userów, dump'y prod DB), klucze prywatne, certyfikaty, klucze API (Stripe/Sentry/AWS/OpenAI/Anthropic), `.env` z realnymi credentialami (tylko `.env.example` z placeholderami), logi produkcyjne, `node_modules/`, `vendor/`.

## 6. CI/CD (GitHub Actions)

`.github/workflows/ci.yml` musi sprawdzać: (1) PHP — Pint, PHPStan level 8, Pest z coverage ≥ 80%; (2) JS — ESLint, vue-tsc, Vitest, Playwright smoke; (3) Security — `composer audit`, `npm audit`, Snyk; (4) Build — `npm run build`; (5) Deploy — tylko z `main` → Laravel Cloud / Forge.

## 7. Krytyczne pliki

| Co | Gdzie |
|---|---|
| Główny use case generowania QR | `app/Actions/QrCode/CreateQrCodeAction.php` |
| Renderer QR (chillerlan wrapper) | `app/Services/QrRendering/QrRenderer.php` |
| Publiczny redirect (najwrażliwszy!) | `app/Http/Controllers/PublicRedirectController.php` |
| Async logowanie skanów | `app/Jobs/RecordScanJob.php` |
| Edytor QR + live preview | `resources/js/pages/QrCode/Edit.vue` |
| Live preview (qr-code-styling) | `resources/js/components/qr/LivePreview.vue` |
| shadcn-vue komponenty | `resources/js/components/ui/*` |
| RBAC / CSP / rate limiters | `config/permission.php`, `config/csp.php`, `app/Providers/RouteServiceProvider.php` |
| Encrypted cast (przykład) | `app/Models/QrCode.php` (`wifi_password`) |
| Polityki dostępu | `app/Policies/QrCodePolicy.php` |

## 8. Co NIE robić

- ❌ `$request->all()` — zawsze `FormRequest`/DTO
- ❌ Mass Assignment bez `$fillable`/`$guarded`
- ❌ Logika w kontrolerach — to robota Actions
- ❌ `dd()`/`dump()` w commitowanym kodzie
- ❌ Telescope na produkcji (gate w `TelescopeServiceProvider`)
- ❌ Hardkodowane stringi w UI — zawsze translation key
- ❌ Commitować `.env`, `storage/`, `node_modules/`, `vendor/`
- ❌ Pomijać CSRF
- ❌ `composer update` bez review — zawsze `composer require X` z wersją
- ❌ Własna kryptografia — zawsze `Crypt`, `Hash`, `Str::random()`
- ❌ Logować PII (email, IP, telefon) bez świadomej anonymizacji
- ❌ `git commit` bez wyraźnej zgody usera (sekcja 5)
- ❌ `git push` bez osobnej zgody (sekcja 5)
- ❌ `Co-Authored-By: Claude` ani innych AI attribution w commitach/PR (sekcja 5)
- ❌ Commitować bez weryfikacji `.gitignore` (sekcja 5a)
