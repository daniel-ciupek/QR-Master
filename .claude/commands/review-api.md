# /review-api — API Design Code Review

Uruchom przegląd jakości API v1 projektu QR-Master. Użyj **2 równoległych agentów Explore**, a następnie skonsoliduj wyniki.

## Agent 1 — REST conventions, status codes, responses
Sprawdź w `app/Http/Controllers/Api/`:
- HTTP status codes są semantycznie poprawne:
  - `200` dla GET/PATCH odpowiedzi
  - `201` dla store (POST tworzący zasób)
  - `202` dla operacji asynchronicznych (bulk)
  - `204` dla DELETE (bez body)
  - `404` gdy zasób nie istnieje
  - `403` gdy brak uprawnień
  - `422` dla błędów walidacji
- Wszystkie listy zwracają paginację (nie `->get()` na dużych zbiorach)
- Wszystkie zasoby używają API Resource classes (`QrCodeResource`, `ScanLogResource`)
- Formaty błędów spójne (`{"message": "..."}` lub `{"message": "...", "errors": {...}}`)
- Brak wyciekania stacktrace/exception messages w odpowiedziach
- `withCount()`, `with()` używane zamiast lazy loading

## Agent 2 — Auth, abilities, rate limiting, bulk
Sprawdź:
- Każdy endpoint API ma `abort_unless($request->user()?->tokenCan('...'), 403, ...)` dla właściwej ability
- `qrcodes:read` sprawdzane przy GET, `qrcodes:write` przy POST/PATCH/DELETE
- `analytics:read` sprawdzane przy stats/scans endpoints
- `throttle:api` middleware na całej grupie v1
- `plan.feature:api` middleware na całej grupie v1 (tylko Business+)
- Bulk endpoint ma limit 1000 items i go egzekwuje
- Batch status sprawdza owner isolation (`bulk:{userId}` / `csv-import:{userId}`)
- `BulkCreateQrCodeApiRequest::authorize()` sprawdza `tokenCan('qrcodes:write')`
- Scribe DocBlocks (`@group`, `@bodyParam`, `@response`) na wszystkich metodach

## Format raportu

```
## 🔴 KRYTYCZNE
- [plik:linia] Opis problemu

## 🟡 WAŻNE
- [plik:linia] Opis problemu

## 🟢 SUGESTIE
- [plik:linia] Opis problemu

## ✅ WERYFIKACJE PRZESZŁY
```
