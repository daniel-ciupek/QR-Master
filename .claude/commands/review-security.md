# /review-security — Security Code Review

Uruchom dogłębny przegląd bezpieczeństwa projektu QR-Master. Użyj **3 równoległych agentów Explore** (jeden call, wiele narzędzi) dla poniższych obszarów, a następnie skonsoliduj wyniki w raport z priorytetyzacją: 🔴 KRYTYCZNE → 🟡 WAŻNE → 🟢 SUGESTIE.

## Agent 1 — Auth, CSRF, Rate Limiting
Sprawdź:
- Każdy route w `routes/web.php` i `routes/api.php` ma `auth` middleware lub jest świadomie publiczny
- Wszystkie publiczne endpointy mają `throttle:*` middleware
- Brak pominięcia CSRF (`withoutMiddleware([PreventRequestForgery::class])` tylko dla Stripe webhook)
- `auth:sanctum` + `plan.feature:api` na wszystkich `/api/v1/*` routach
- `tokenCan()` wywołane przed każdą operacją w API controllerach
- Login/register mają odpowiednie rate limity (`auth-login`, `register`)

## Agent 2 — Input Validation, Injection, PII
Sprawdź:
- Każdy kontroler używa `FormRequest` (nie `$request->all()` / `$request->input()` bez walidacji)
- Brak SQL injection: `whereRaw()` / `selectRaw()` tylko z bound parameters
- URL destination_url ma regex whitelist `^https?://` we wszystkich Requestach
- PII (email, phone, wifi_password, vcard_*) przechowywane z `'encrypted'` castem
- `ip_hash` (nie raw IP) w scan_logs i user_sessions
- Webhook URL validation — `url` rule + regex `^https?://`
- Brak hardcoded secrets w kodzie PHP

## Agent 3 — Webhooks, HMAC, Stripe, API Security
Sprawdź:
- `DeliverWebhookJob` poprawnie liczy HMAC-SHA256 z całego body
- `StripeWebhookController` weryfikuje Stripe signature (nie tylko akceptuje payload)
- `PublicRedirectController` ma `CheckBotSuspicion` middleware
- `QrCodeZipExportController` — brak path traversal w `tempnam()`
- `BulkQrCodeController` — owner isolation (batch name `bulk:{userId}`)
- CSV import — brak możliwości path traversal przy session_key (UUID validation)
- `EnsurePlanFeature` middleware działa poprawnie dla wszystkich feature gates

## Format raportu

```
## 🔴 KRYTYCZNE (napraw natychmiast)
- [plik:linia] Opis problemu

## 🟡 WAŻNE (napraw przed Stage X)
- [plik:linia] Opis problemu

## 🟢 SUGESTIE (nice to have)
- [plik:linia] Opis problemu

## ✅ WERYFIKACJE PRZESZŁY
- Lista rzeczy które są OK
```
