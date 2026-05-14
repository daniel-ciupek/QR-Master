# QR-Master SaaS — Generator i Platforma Dynamicznych Kodów QR

## 🎯 Wizja

Platforma SaaS „all-in-one" do generowania, zarządzania i śledzenia dynamicznych kodów QR. Trzy grupy docelowe:

1. **B2C** — użytkownicy indywidualni (vCard, social media, prosty branding)
2. **B2B / Marketing** — kampanie z analityką, A/B testing, smart redirect, custom domains
3. **Gastronomia / Retail** — kody na stoliki/półki z aktualizowalnym menu/cennikiem PDF

**Priorytety jakościowe:** nowoczesna architektura (Laravel 13, Action/Service/DTO, Octane), nowoczesny design (shadcn-vue, Bento grid, dark mode), bezpieczeństwo danych (RODO, 2FA, encryption at rest, audit log).

> Stack, konwencje, polecenia, security checklist i workflow Git → **[CLAUDE.md](./CLAUDE.md)**.

---

## 📈 Model Biznesowy

| Plan | Cena | Limity | Kluczowe funkcje |
|---|---|---|---|
| **Free** | 0 PLN | 5 dynamicznych QR, 100 skanów/mc | Statyczne QR bez limitu, podstawowe kolory |
| **Pro** | ~49 PLN/mc | 100 dynamicznych QR, 50k skanów/mc | Logo, gradienty, analityka, vCard/WiFi, A/B test |
| **Business** | ~199 PLN/mc | 1000 dynamicznych QR, 1M skanów/mc | API, bulk, smart redirect, real-time, webhooks |
| **Enterprise** | indywidualnie | unlimited | Custom domains, white-label, DPA, SLA, SSO |

**Ścieżki monetyzacji:** B2C freemium → Pro; B2B Marketing → Business (analityka kampanii); Gastronomia → onboarding „menu QR za 5 minut" + lokalna sprzedaż.

---

## 🗺 Roadmapa Projektu (12 etapów)

Każdy etap kończy się działającą funkcjonalnością i pokryty jest testami (Pest + Playwright). Workflow zamykania etapu → sekcja [✅ Workflow Ukończenia Etapu](#-workflow-ukończenia-etapu) na końcu pliku.

---

### ✅ Etap 0: Fundament Projektu i CI/CD *(ukończony 2026-05-09)*

*Cel: Działający szkielet aplikacji z testami, CI, monitoringiem i security baseline.*

- [x] **0.1** Inicjalizacja projektu Laravel 13 (`composer create-project`), PHP 8.3+
- [x] **0.1a** **Utworzenie pliku `.gitignore`** z bazową zawartością (Laravel + Vue + IDE + cache narzędzi) — szablon w [CLAUDE.md sekcja 5a](./CLAUDE.md#5a-plik-gitignore--zasady)
- [x] **0.1b** **Utworzenie `.env.example`** ze wszystkimi wymaganymi zmiennymi (placeholdery, NIGDY realnych credentiali)
- [x] **0.1c** **Utworzenie `.claude/settings.json`** — permissions (allow/deny) + hook `git-reminder.sh` (PreToolUse)
- [x] **0.1d** `git init` + utworzenie pierwszego commitu z `.gitignore` + push do nowego repo na GitHubie (origin)
- [x] **0.2** Docker Compose dla lokalnego dev (PostgreSQL 17, Redis 7, Reverb, Typesense, Mailpit) + `.dockerignore`
- [x] **0.3** Konfiguracja FrankenPHP + Laravel Octane (driver `frankenphp`)
- [x] **0.4** Inertia.js 3 + Vue 3 + Vite 6 + TypeScript strict mode
- [x] **0.5** Tailwind CSS 4 z design tokens (CSS variables, `@theme inline`)
- [x] **0.6** shadcn-vue setup — kopiowanie bazowych komponentów (Button, Input, Dialog, Sheet, Tabs, DataTable, Toast, Command)
- [x] **0.7** Struktura folderów: `Actions/`, `Services/`, `Data/`, `Enums/` (zgodnie z CLAUDE.md)
- [x] **0.8** Pest 3 + Larastan level 8 + Laravel Pint + Rector — wszystkie skonfigurowane
- [x] **0.9** **lefthook** — pre-commit (Pint + ESLint + sprawdzenie max 5MB) i pre-push (PHPStan + Pest smoke + sprawdzenie `.gitignore`)
- [x] **0.10** GitHub Actions CI: lint, phpstan, pest (z coverage gate ≥ 80%), vitest, playwright smoke
- [x] **0.11** Security headers (spatie/laravel-csp), HSTS, CSP z nonce
- [x] **0.12** Sentry + Laravel Pulse + Telescope (z gate na produkcji)
- [x] **0.13** Seedery + Faker dla danych testowych (DemoSeeder z przykładowymi userami i QR)

**Kryterium ukończenia:** `composer run dev` startuje aplikację, CI zielone na pustym PR.

---

### ✅ Etap 1: Auth, UI Fundament, i18n, Dark Mode *(ukończony 2026-05-10)*

*Cel: System uwierzytelniania klasy enterprise + szkielet UX dashboardu.*

- [x] **1.1** Laravel Fortify (login, register, password reset, email verification)
- [x] **1.2** **2FA TOTP** — flow setup + recovery codes + middleware `EnsureTwoFactor` dla Pro/Business
- [x] **1.3** **Passkeys / WebAuthn** (laragear/webauthn) jako opcja
- [x] **1.4** Cloudflare Turnstile na rejestracji + reset hasła
- [x] **1.5** spatie/laravel-permission — role: `admin`, `user`; permissions per feature
- [x] **1.6** Session management — lista aktywnych sesji, możliwość revocation
- [x] **1.7** Layout aplikacji: Sidebar collapsible + Topbar + Command Palette (Cmd+K)
- [x] **1.8** **Dark mode** (light / dark / system) — persistowany w localStorage
- [x] **1.9** **i18n PL + EN** — vue-i18n + laravel-translatable, wszystkie stringi w plikach `lang/`
- [x] **1.10** Strona profilu: zmiana hasła, 2FA, dane osobowe, eksport RODO, usunięcie konta
- [x] **1.11** Email templates (Markdown) dla wszystkich powiadomień systemowych — branded
- [x] **1.12** Onboarding wizard po pierwszym logowaniu (3 kroki, Lottie animations)

**Kryterium ukończenia:** User może się zarejestrować z 2FA, zalogować, zmienić motyw, język, eksportować swoje dane.

---

### ✅ Etap 2: Statyczny Generator QR + Live Preview *(ukończony 2026-05-10)*

*Cel: Pierwsza wartość biznesowa — generowanie i pobieranie QR z pięknym podglądem.*

- [x] **2.1** `chillerlan/php-qrcode` — wrapper jako `app/Services/QrRendering/QrRenderer.php`
- [x] **2.2** Frontend: `qr-code-styling.js` w komponencie `LivePreview.vue` (Canvas API)
- [x] **2.3** Formularz generatora: tekst / URL / email / telefon / SMS (Tab-based UI)
- [x] **2.4** Live preview z debounced update (150ms)
- [x] **2.5** Walidacja URL (whitelist schemes), długość treści, error correction level
- [x] **2.6** Eksport: **PNG, SVG, PDF, EPS** (PDF przez `dompdf` + SVG embed)
- [x] **2.7** Modal eksportu z preview każdego formatu i wyborem rozdzielczości
- [x] **2.8** Color contrast checker — ostrzeżenie jeśli QR będzie nieczytelny

**Kryterium ukończenia:** User generuje QR z URL, zmienia tekst → preview odświeża się płynnie, pobiera w 4 formatach.

---

### ✅ Etap 3: Dynamiczne Kody QR — Mechanizm Przekierowań *(ukończony 2026-05-11)*

*Cel: Kluczowa wartość B2B — możliwość zmiany linku bez przedrukowywania kodu.*

- [x] **3.1** Migracja `qr_codes`: `id, user_id, type, title, slug, short_hash, destination_url, settings (jsonb), is_active, expires_at, password_hash, created_at, updated_at, deleted_at`
- [x] **3.2** Model `QrCode` z relacjami (`user`, `scanLogs`, `tags`), polymorphic media (logo)
- [x] **3.3** `app/Services/HashGenerator.php` — bezpieczny `short_hash` (8 znaków, base62, kolizja-resistant)
- [x] **3.4** `app/Actions/QrCode/CreateQrCodeAction.php` + `UpdateQrCodeAction.php` + `DeleteQrCodeAction.php`
- [x] **3.5** Routing: `GET /q/{hash}` → `PublicRedirectController` (kontroler najwyższego priorytetu bezpieczeństwa)
- [x] **3.6** Soft delete + queued purge job (30 dni)
- [x] **3.7** Dashboard: DataTable kodów (TanStack Table) z filtrowaniem, sortowaniem, search
- [x] **3.8** Akcje: edytuj, duplikuj, wstrzymaj (`is_active`), usuń, kopiuj link, pobierz QR
- [x] **3.9** Widok edycji kodu — formularz Inertia z walidacją SSR
- [x] **3.10** Folders / Tags — grupowanie kodów dla łatwiejszego zarządzania
- [x] **3.11** Bulk select + bulk actions (delete, pause, export, move to folder)

**Kryterium ukończenia:** User tworzy dynamiczny QR, edytuje URL, skanuje → przekierowanie działa, zarządza listą.

---

### ✅ Etap 4: Personalizacja Wizualna i Branding *(ukończony 2026-05-12)*

*Cel: Wartość premium — branded QR z logo, gradientami, niestandardowymi kropkami.*

- [x] **4.1** Rozszerzenie pola `settings` (jsonb): `dotStyle, dotColor, gradient, bgColor, cornerSquareStyle, cornerDotStyle, logoPath, logoMargin, frame, frameText`
- [x] **4.2** UI: Color picker (HSL + hex + presets brandowych palet)
- [x] **4.3** Wybór stylu kropek: square, dots, rounded, classy, classy-rounded, extra-rounded (qr-code-styling)
- [x] **4.4** Wybór stylu „oczek" (corner squares + corner dots) — niezależnie
- [x] **4.5** **Gradienty** — linear, radial, kolory startowy/końcowy
- [x] **4.6** Upload loga: spatie/laravel-medialibrary, walidacja MIME (real), max 2MB, optimize (PNG/SVG/JPG)
- [x] **4.7** Logo margin / size controls (% pokrycia QR, max 30%)
- [x] **4.8** **Frames** (opakowania) — „Skanuj mnie", „Menu", custom CTA pod kodem
- [x] **4.9** **Templates** — predefiniowane zestawy stylów (Modern, Classic, Vibrant, Minimal, Restaurant)
- [x] **4.10** Bookmark templates — user może zapisać własny styl jako szablon do reuse
- [x] **4.11** **AI suggest colors** (placeholder do Etap 10) — endpoint `/api/ai/suggest-palette` z analizą loga

**Kryterium ukończenia:** User wgrywa logo firmy → AI sugeruje paletę → preview pokazuje branded QR → eksport zachowuje wszystkie efekty.

---

### ✅ Etap 5: Zaawansowana Analityka + Real-time Dashboard *(ukończony 2026-05-14)*

*Cel: Klucz do sprzedaży planów Pro/Business — pełna widoczność efektywności kampanii.*

- [x] **5.1** Migracja `scan_logs`: `id, qr_id, ip_hash, country, region, city, lat, lng, device_type, os, browser, referrer, language, scanned_at`
- [x] **5.2** **Partycjonowanie** tabeli `scan_logs` po miesiącach (PostgreSQL native)
- [x] **5.3** Indeksy na `qr_id`, `scanned_at`, `country` (kompozytowe)
- [x] **5.4** `app/Jobs/RecordScanJob.php` — async logowanie skanu (Redis queue, redirect zwraca natychmiast)
- [x] **5.5** `stevebauman/location` — lookup IP (z cache 24h)
- [x] **5.6** `whichbrowser/parser` lub `jenssegers/agent` — User-Agent parsing
- [x] **5.7** **Anonymizacja IP** — IP nie jest zapisywany w surowej formie, tylko `ip_hash` (Argon2id z salt) — pozwala na unikalne unique-scans bez retencji PII
- [x] **5.8** Cron `php artisan privacy:purge-old-scans` — usuwa precyzyjne dane geolokalizacji po 90 dniach
- [x] **5.9** Widok „Szczegóły kodu": Bento grid layout w stylu Linear/Vercel
- [x] **5.10** Wykresy: ApexCharts (timeline skanów, mapa świata, breakdown urządzenia/OS/browser)
- [x] **5.11** **Heatmapa godzinowa** — kiedy ludzie skanują (ważne dla offline reklamy)
- [x] **5.12** **Real-time counter** (Laravel Reverb) — live update licznika skanów na dashboardzie
- [x] **5.13** Eksport raportu PDF — branded summary dla klienta końcowego
- [x] **5.14** **Comparing** — porównaj 2-5 kodów obok siebie

**Kryterium ukończenia:** Skan publicznego QR → log pojawia się w dashboardzie real-time, mapa pokazuje lokalizację, eksport PDF zawiera pełny raport.

---

### Etap 6: Specjalistyczne Typy Kodów QR

*Cel: Gotowe rozwiązania „z pudełka" dla każdej branży.*

- [x] **6.1** **Moduł vCard** — formularz: imię, nazwisko, firma, stanowisko, telefon, email, www, adres, foto. Generuje vCard 4.0 standard
- [x] **6.2** Encrypted at rest dla pól vCard (telefon, email) — Eloquent cast `'encrypted'`
- [x] **6.3** **Moduł WiFi** — SSID, hasło (encrypted), typ (WPA/WPA2/WPA3/WEP/Open), ukryta sieć
- [x] **6.4** **Moduł SMS / Email / Phone / Geo** — szybkie generatory specjalistyczne
- [x] **6.5** **Moduł PDF Menu** — upload PDF (max 10MB), podgląd inline, możliwość wymiany bez zmiany QR
- [x] **6.6** **Moduł Bio-Link** — landing page builder
  - Kreator: avatar, bio, lista linków (drag-drop sortowanie)
  - Templates: Minimal, Bold, Glassmorphism, Retro
  - Theming: kolory, tło, font, button shape
  - Tracking poszczególnych kliknięć w linki
  - Subdomena: `bio.qr-master.app/{slug}`
  - Analytics dedykowane Bio-Link
- [x] **6.7** **Moduł App Store / Play Store** — auto-detekcja platformy w smart redirect
- [x] **6.8** **Moduł Calendar (.ics)** — eventy z datami, miejscem, opisem
- [x] **6.9** **Moduł Crypto Address** — Bitcoin/ETH wallet z optional kwotą i memo
- [x] **6.10** **Moduł Review (Google/Trustpilot)** — link do napisania recenzji z trackingiem

**Kryterium ukończenia:** Każdy typ kodu działa end-to-end: tworzenie → preview → skan → docelowe działanie (np. zapis kontaktu, dołączenie do WiFi).

---

### Etap 7: Smart Redirect, A/B Testing, Harmonogramowanie

*Cel: Zaawansowane reguły — wartość premium dla marketerów.*

- [ ] **7.1** **Smart Redirect Rules** — silnik reguł:
  - Device type (iOS / Android / Desktop / Tablet)
  - Country / region (GeoIP-based)
  - Language (Accept-Language)
  - Time of day / day of week (godziny otwarcia)
  - Custom (operator user-agent contains, IP range)
- [ ] **7.2** UI builder reguł — drag-drop, preview „co by się stało gdyby"
- [ ] **7.3** **A/B Testing** — wiele URL pod jednym QR z procentowym podziałem
  - Statistical significance calculator
  - Winner auto-selection po N skanach
- [ ] **7.4** **Harmonogramowanie** — `expires_at` + `activates_at` (kampanie sezonowe)
- [ ] **7.5** **Password-protected QR** — przed redirectem prompt o hasło (Argon2id hash)
- [ ] **7.6** **Geofencing** — zezwól tylko z określonych krajów/regionów (anti-leak na inne rynki)
- [ ] **7.7** **Click cap** — maksymalna liczba skanów (np. promocja na pierwsze 100 osób)
- [ ] **7.8** **Fallback URL** — jeśli żadna reguła nie pasuje
- [ ] **7.9** **Anti-bot middleware** — Cloudflare Turnstile dla podejrzanego ruchu (boost > 100/min)

**Kryterium ukończenia:** Marketer tworzy kampanię z 3 wariantami URL → po 1000 skanów system wybiera zwycięzcę → harmonogram aktywuje promocję 1 stycznia.

---

### Etap 8: Subskrypcje i Monetyzacja (Stripe)

*Cel: Przekształcenie projektu w produkt zarobkowy.*

- [ ] **8.1** Laravel Cashier + konto Stripe (test mode + production)
- [ ] **8.2** Migracje Cashier + Customer Portal
- [ ] **8.3** Plany w Stripe: Free, Pro, Business, Enterprise (custom)
- [ ] **8.4** Strona Pricing: shadcn-vue, Bento grid, FAQ accordion, comparison table
- [ ] **8.5** Stripe Checkout integration (płatność karty + Apple/Google Pay + przelew)
- [ ] **8.6** Stripe Tax (auto VAT dla EU)
- [ ] **8.7** Middleware `EnsurePlanFeature` — gate na features wg planu
- [ ] **8.8** Webhooks Stripe — `invoice.payment_succeeded`, `customer.subscription.deleted`, `customer.subscription.updated`, `payment_failed`
- [ ] **8.9** Customer Portal Stripe — faktury, zmiana karty, anulowanie
- [ ] **8.10** Dashboard subskrypcji — widoczne limity (np. „5/100 dynamicznych QR"), upsell hints
- [ ] **8.11** Powiadomienia email: przed wygaśnięciem (7d), po opłacie, problem z płatnością
- [ ] **8.12** **Trial 14 dni** dla Pro przy rejestracji (bez karty), z reminder email
- [ ] **8.13** **Affiliate program** (rewardful.com lub własny) — 20% commission

**Kryterium ukończenia:** User upgrade'uje plan → odblokowane premium features → faktura w Customer Portal → webhook synchronizuje status.

---

### Etap 9: Public API + Bulk Operations + Webhooks Outbound

*Cel: Automatyzacja dla B2B Enterprise — programmatic dostęp.*

- [ ] **9.1** Laravel Sanctum — token-based auth z **ability scopes** (`qrcodes:read`, `qrcodes:write`, `analytics:read`)
- [ ] **9.2** UI w panelu: tworzenie/podgląd/revoke tokenów, expiration date
- [ ] **9.3** API endpoints (RESTful + JSON:API standard z Laravel 13):
  - `GET/POST/PATCH/DELETE /api/v1/qrcodes`
  - `GET /api/v1/qrcodes/{id}/stats`
  - `GET /api/v1/qrcodes/{id}/scans`
  - `GET /api/v1/folders`, `POST /api/v1/folders`
- [ ] **9.4** **Rate limiting per plan tier** (Free: 60/h, Pro: 1000/h, Business: 10000/h)
- [ ] **9.5** **Bulk operations** — `POST /api/v1/qrcodes/bulk` (max 1000 na request, queue processing)
- [ ] **9.6** **CSV import** w UI — wgranie pliku → preview → konfiguracja mapowania → batch generation z queue progress bar (Reverb)
- [ ] **9.7** **Eksport ZIP** — wszystkie QR jako PNG/SVG w archiwum
- [ ] **9.8** **Webhooks outbound** — klient B2B otrzymuje POST na każdy skan
  - Endpoints w panelu, secret signing (HMAC-SHA256)
  - Retry logic (exponential backoff, max 5 prób)
  - Delivery log + status w UI
- [ ] **9.9** **API dokumentacja** — Scribe (auto-generated z PHPDoc) + przykłady curl/PHP/JS/Python
- [ ] **9.10** **OpenAPI 3.1 spec** wystawiony pod `/api/openapi.json` (do importu w Postmanie)
- [ ] **9.11** **API Playground** w docs — interaktywne testowanie endpointów

**Kryterium ukończenia:** Klient generuje 1000 QR przez API w 30s, otrzymuje webhook na każdy skan, dokumentacja jest interaktywna.

---

### Etap 10: AI Features (Laravel 13 AI Primitives)

*Cel: Differentiator vs konkurencja — inteligentne sugestie i automatyzacja.*

- [ ] **10.1** Konfiguracja Laravel 13 AI driver (Anthropic Claude / OpenAI / lokalny Ollama)
- [ ] **10.2** **AI Color Palette Suggestion** — analiza loga (vision model) → 3-5 sugerowanych palet
- [ ] **10.3** **AI Bio-Link Content Generator** — user wpisuje branżę/zawód → AI sugeruje bio + emoji + kolejność linków
- [ ] **10.4** **AI Campaign Hooks** — sugestie tekstu pod ramką QR („Skanuj po promocję!")
- [ ] **10.5** **Anomaly Detection** w skanach — model wykrywa nietypowe wzorce (możliwy bot/fraud)
- [ ] **10.6** **Vector Search** w analityce (Laravel 13 native) — natural language query: „pokaż kody z największym wzrostem skanów w ostatnim tygodniu"
- [ ] **10.7** **Smart QR Naming** — AI sugeruje nazwę kodu na podstawie URL/treści
- [ ] **10.8** **Chatbot Helper** w panelu — Claude jako asystent ze świadomością kontekstu konta usera
- [ ] **10.9** **Performance Insights** — AI generuje natural language podsumowanie: „Twoja kampania w marcu osiągnęła 23% lepszy CTR niż lutowa, głównie dzięki..."
- [ ] **10.10** Rate limiting AI (drogo!) — per plan: Free 0, Pro 50/mc, Business 500/mc, Enterprise unlimited
- [ ] **10.11** Caching odpowiedzi AI — Redis (klucz: hash inputu)
- [ ] **10.12** **Prompt injection protection** — sanityzacja inputu, system prompt z guardrails

**Kryterium ukończenia:** User wgrywa logo → otrzymuje 3 palety w 5s → wpisuje „rzucam nowy produkt" → AI generuje QR z dopasowanym brandingiem i tekstem CTA.

---

### Etap 11: PWA, Custom Domains, Real-time

*Cel: Enterprise-grade gotowość — własna domena, instalowalna apka, live data.*

- [ ] **11.1** **PWA** — manifest.json, service worker (Workbox), ikony, splash screens
- [ ] **11.2** **Offline mode** — drafts kodów zapisywane lokalnie (IndexedDB), sync po online
- [ ] **11.3** Push notifications (web) — alert przy anomalii skanów, koniec planu, wyczerpanie limitu
- [ ] **11.4** **Custom domains** — klient B2B dodaje `qr.firma.pl` jako CNAME
  - Auto-issue SSL przez Cloudflare for SaaS lub Let's Encrypt
  - Walidacja DNS w panelu
  - Multiple domains per account
- [ ] **11.5** **Branded short links** — klient wybiera path: `qr.firma.pl/promo` zamiast `qr.firma.pl/q/Ab3xK9`
- [ ] **11.6** **Real-time collaboration** (Laravel Reverb) — wielu userów edytuje kody w team workspace, presence indicators
- [ ] **11.7** **Live notifications center** — bell icon z dropdownem real-time (Reverb)
- [ ] **11.8** Locale extensions — DE, ES, FR, IT (rozszerzenie i18n)

**Kryterium ukończenia:** Klient instaluje PWA na telefonie → konfiguruje qr.firma.pl → tworzy QR z linkiem `qr.firma.pl/sale` → push notification po anomalii.

---

### Etap 12: Multi-tenancy, White-label, Enterprise Compliance

*Cel: Sprzedaż Enterprise — duże firmy, RODO-ready, audyty.*

- [ ] **12.1** **Teams / Workspaces** — multi-tenancy (single DB, tenant_id na kluczowych tabelach)
- [ ] **12.2** Role w workspace: Owner, Admin, Editor, Viewer
- [ ] **12.3** Invitations + email + akceptacja
- [ ] **12.4** Per-team billing (jeden Stripe customer per workspace)
- [ ] **12.5** **White-label** — workspace ma własne logo, kolory, custom domain → user widzi „Powered by [BrandX]" zamiast QR-Master
- [ ] **12.6** **DPA Generator** (Data Processing Agreement) — wygenerowany PDF z danymi klienta dla compliance
- [ ] **12.7** **Compliance Dashboard** (Enterprise) — status RODO, lista przetwarzanych danych, last audit, cookie consent stats
- [ ] **12.8** **Audit raporty** — eksport pełnego activity log dla auditora (kto co zrobił kiedy)
- [ ] **12.9** **SSO** — SAML 2.0 / OAuth 2.0 (Google Workspace, Microsoft 365, Okta)
- [ ] **12.10** **SCIM provisioning** — auto-provisioning userów z IdP
- [ ] **12.11** **IP allowlisting** — workspace może ograniczyć dostęp do konkretnych IP/CIDR
- [ ] **12.12** **Data residency** — wybór regionu (EU / US) dla zgodności z lokalnymi regulacjami
- [ ] **12.13** **SLA dashboard** publiczny (status.qr-master.app)

**Kryterium ukończenia:** Enterprise klient konfiguruje SSO Microsoft 365, white-labeluje panel pod swoją markę, eksportuje audit raport za ostatni kwartał, ogląda DPA wygenerowane na ich dane.

---

## 🛡 Security & Compliance

Pełny checklist obowiązuje we wszystkich etapach → **[CLAUDE.md sekcja 4](./CLAUDE.md#4-bezpieczeństwo--checklist-obowiązkowy)**.

**Najważniejsze:** 2FA TOTP wymuszony dla Pro/Business+, Passkeys/WebAuthn jako opcja, Cloudflare Turnstile na rejestracji/reset hasła; encryption at rest dla PII (vCard, WiFi password), `ip_hash` zamiast surowego IP, Argon2id dla haseł, TLS 1.3 + HSTS preload; RODO — consent log, eksport `/account/export`, queued purge, anonymizacja geo po 90 dniach; rate limiting wielowarstwowy (`/q/{hash}` 60/min/IP, API per plan, login/register throttle); audit przez `spatie/laravel-activitylog`, Sentry z filtrem PII, Pulse, Horizon failed jobs (Telescope tylko dev/staging); anti-fraud — anomaly detection, AbuseIPDB, honeypot, Google Safe Browsing dla destination URL.

---

## 🎨 Design System

**Tokens (CSS variables):** kolory primary/secondary/accent/success/warning/danger × light/dark; spacing 4px grid; typografia Geist Sans (UI) + Geist Mono (kod) + Inter fallback; radius sm/md/lg/xl/2xl (4/8/12/16/24px); shadows glassmorphism + standard elevation.

**Komponenty (shadcn-vue + custom):** Button, Input, Select, Textarea, Checkbox, Radio, Switch, Slider, Card, Sheet, Dialog, Tabs, Accordion, Sidebar, Breadcrumb, Command Palette, Popover, Menu, Tooltip, DataTable (TanStack), Pagination, Empty State, Skeleton, Toast (Sonner-vue), Alert, Badge, Progress; custom: ColorPicker, QrLivePreview, FileUpload, ImageCropper.

**Layout patterns:** Bento grid w dashboardach (Apple/Linear), Sidebar collapsible, Command palette (Cmd+K), Onboarding multi-step z Lottie.

**Animacje (Motion-v + View Transitions API):** page transitions, hover (scale/glow), reveal (intersection observer), skeleton zamiast spinner, debounced live preview QR (150ms).

**Accessibility WCAG 2.2 AA:** Reka UI / Radix-vue (built-in), focus visible, keyboard nav, NVDA + VoiceOver tested, color contrast ≥ 4.5:1 (autocheck w designerze QR).

---

## 📊 KPI / Metryki Sukcesu

**Produktowe:** Time-to-first-QR < 60s; Activation rate (24h) > 40%; W4 retention > 25%; Trial → Paid > 15%.

**Techniczne:** redirect `/q/{hash}` < 50ms (p95); dashboard < 1s przy 10k kodów; Lighthouse > 95 publiczne strony; coverage Actions/Services ≥ 80%; PHPStan level 8 zero violations; zero CVE (Snyk); Octane > 1000 req/s na 1 vCPU.

**Bezpieczeństwo:** zero PII leaks w logach (audit kwartalny); MTTR critical < 24h; 2FA adoption > 60% wśród Pro+; failed logins < 0.5%.

**Biznesowe:** MRR growth tracking; churn < 5%/mc (Pro+); LTV/CAC > 3:1; NPS > 40.

---

## 🚀 Rozpoczęcie Pracy

```bash
git clone <repo> && cd QR-Master
composer install && npm install
cp .env.example .env && php artisan key:generate
docker compose up -d                   # PostgreSQL, Redis, Reverb, Typesense, Mailpit
php artisan migrate --seed
composer run dev                       # Octane + Vite + Horizon + Reverb
```

Pełny stack, konwencje, polecenia, security i workflow Git → **[CLAUDE.md](./CLAUDE.md)**.

---

## ✅ Workflow Ukończenia Etapu

Po skończeniu każdego z 12 etapów wykonaj **w tej kolejności**:

1. Zaznacz wszystkie sub-checkboxy w etapie: `- [ ]` → `- [x]`.
2. Zmień nagłówek etapu: `### Etap N: Tytuł` → `### ✅ Etap N: Tytuł *(ukończony YYYY-MM-DD)*`.
3. Uruchom test suite: `php artisan test --parallel && ./vendor/bin/phpstan analyse && ./vendor/bin/pint --test && npm run test && npm run lint`.
4. Zweryfikuj `.gitignore` (`git status -u`). Jeśli są śmieci/sekrety → dodaj wzorce, `git rm --cached` jeśli zaśledzone.
5. `git add -A` (po weryfikacji z punktu 4).
6. Pokaż preview userowi (`git status` + `git diff --cached --stat` + proponowany message), zapytaj o zgodę. `git commit` **dopiero po jawnym "tak"**.
7. Pokaż userowi co idzie na origin, zapytaj o zgodę. `git push origin develop` **dopiero po jawnym "tak"**.
8. Opcjonalnie tag (też po pytaniu): `git tag -a stage-N -m "..."` + `git push origin stage-N`.

### ❗ Bezwzględne reguły dla commitów

- **Każdy `git commit` wymaga zgody usera** (preview + pytanie). To samo dla `git push` (osobne pytanie).
- **Brak AI attribution** w commit messages i opisach PR — żadnego `Co-Authored-By: Claude` ani `🤖 Generated with Claude Code`. Repo wygląda jak praca jednego dewelopera.
- **Sprawdzenie `.gitignore`** przed każdym commitem — żadnych logów, cache, sekretów, artefaktów buildu.

Szczegóły → [CLAUDE.md sekcja 5](./CLAUDE.md#5-workflow-git) i [sekcja 5a](./CLAUDE.md#5a-plik-gitignore--zasady).
