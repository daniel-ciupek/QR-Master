# /review-performance — Performance Code Review

Uruchom przegląd wydajnościowy projektu QR-Master. Użyj **3 równoległych agentów Explore**, a następnie skonsoliduj wyniki.

## Agent 1 — N+1 queries i eager loading
Sprawdź w `app/Http/Controllers/` i `app/Actions/`:
- Każde `->get()` na kolekcji z relacją ma `->with(...)` eager load
- Brak `$model->relation` w pętlach (klasyczny N+1)
- `QrCode::with('tags')` w index controllerach
- `Webhook::with('deliveries')` sprawdź czy nie ma N+1 w delivery count
- `withCount()` zamiast `$model->relation->count()` w listach
- `QrCode::forUser()` scope — sprawdź czy nie ładuje niepotrzebnych kolumn (brak `SELECT *`)
- `ScanLog` queries filtrują po `qr_code_id` (z indeksem)
- `BioLink`, `AbTest`, `RedirectRule` — eager loading w edycji

## Agent 2 — Indeksy bazy danych
Sprawdź migracje w `database/migrations/`:
- `scan_logs`: indeks na `(qr_code_id, scanned_at)` — używany w analityce
- `qr_codes`: indeks na `(user_id, is_active)`, `short_hash` (unique)
- `webhooks`: indeks na `user_id`
- `webhook_deliveries`: indeks na `(webhook_id, created_at)`, `status`
- `affiliate_commissions`: indeks na `referrer_user_id`
- `user_sessions`: indeks na `user_id`
- Brak brakujących indeksów na foreign keys (PostgreSQL nie dodaje automatycznie)
- `personal_access_tokens`: indeks na `tokenable_id` (Sanctum używa)

## Agent 3 — Cache, Queue, optymalizacja
Sprawdź:
- `GeoLookupService` cachuje wyniki (24h) — zweryfikuj implementację
- `RecordScanJob` — czy nie wykonuje zbędnych queries
- `DeliverWebhookJob` — `with('webhook')` zamiast lazy load
- Kolejka `scans` dla RecordScanJob, `webhooks` dla DeliverWebhookJob, `bulk` dla batch
- `QrCode::forUser()` — select konkretnych kolumn w liście (nie `SELECT *`)
- Analityka: aggregate queries zamiast kolekcji PHP
- `Bus::findBatch()` nie robi N+1 (jednorazowe query)
- CSV import: linia po linii (nie cały plik do RAM) — sprawdź limit

## Format raportu

```
## 🔴 KRYTYCZNE (N+1 w krytycznych ścieżkach, brak indeksów)
- [plik:linia] Opis problemu + szacowany impact

## 🟡 WAŻNE (suboptymalne queries, brakujące indeksy)
- [plik:linia] Opis problemu

## 🟢 SUGESTIE (nice-to-have optimizations)
- [plik:linia] Opis sugestii

## ✅ WERYFIKACJE PRZESZŁY
```
