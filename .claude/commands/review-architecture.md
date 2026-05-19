# /review-architecture — Architecture Code Review

Uruchom przegląd architektoniczny projektu QR-Master (wzorzec Action/Service/DTO). Użyj **3 równoległych agentów Explore**, a następnie skonsoliduj wyniki.

## Agent 1 — Controllers i Actions
Sprawdź:
- Kontrolery zawierają TYLKO: walidację → Action → response. Zero logiki biznesowej
- Każda klasa Action jest `final` i ma metodę `handle()` (nie `execute()`, nie inne nazwy)
- Actions single-purpose — jedna klasa = jeden use case
- Brak `dd()`, `dump()`, `var_dump()`, `print_r()` w commitowanym kodzie
- Brak `$request->all()` — tylko `$request->validated()` lub dedykowane metody
- Brak logiki w `__construct()` kontrolerów (tylko DI)
- `app/Http/Controllers/` — czy są tam kontrolery z logiką? (N+1 problem, DB queries)

## Agent 2 — Services, DTOs, Models
Sprawdź:
- DTO (spatie/laravel-data) używane na granicach systemu — nie surowe tablice asocjacyjne
- `readonly` na polach DTO
- Modele są "cienkie" — tylko relacje, casts, scopes, helpers
- Brak logiki biznesowej w modelach (poza scope'ami i pomocniczymi metodami)
- Services wielokrotnego użytku, nie jednorazowe
- Enums (`QrCodeType`, `PlanTier`, `ApiAbility`) dla wszystkich skończonych wartości
- `declare(strict_types=1)` w każdym pliku PHP

## Agent 3 — Jobs, Events, Middleware
Sprawdź:
- Jobs implementują `ShouldQueue`, mają `$tries` i `$backoff`
- `DeliverWebhookJob` ma `tries=5` i exponential backoff
- `RecordScanJob` i `ProcessCsvImportRowJob` mają `tries=1` (brak duplikatów)
- Events dziedziczą po `ShouldBroadcast` lub `ShouldBroadcastNow` gdzie potrzeba
- Middleware `final` klasy, thin (tylko logika middleware)
- `EnsurePlanFeature` poprawnie deleguje do `PlanTier` enum
- Brak circular dependencies między klasami

## Format raportu

```
## 🔴 KRYTYCZNE (naruszenia wzorca)
- [plik:linia] Opis naruszenia

## 🟡 WAŻNE (do refaktoru)
- [plik:linia] Opis problemu

## 🟢 SUGESTIE
- [plik:linia] Opis sugestii

## ✅ WERYFIKACJE PRZESZŁY
```
