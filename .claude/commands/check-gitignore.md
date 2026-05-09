---
description: Audit untracked plików vs sygnały z CLAUDE.md sekcja 5a — sugeruje wpisy do .gitignore i wykrywa sekrety w staged
allowed-tools: Bash, Read, Edit
---

Wykonaj pełny audit gotowości do commit zgodnie z CLAUDE.md sekcja 5a.

## 1. Untracked files

```bash
git status -u
```

Przeanalizuj wynik wg sygnałów (CLAUDE.md sekcja 5a). Dla każdego znalezionego pliku/katalogu zaklasyfikuj:

| Wzorzec | Sugerowany wpis do `.gitignore` |
|---|---|
| `storage/logs/*.log` | `/storage/logs/*` |
| `storage/framework/cache/data/*` | `/storage/framework/cache/data/*` |
| `bootstrap/cache/*.php` (poza `.gitkeep`) | `/bootstrap/cache/*` |
| `*.log` w root | `*.log` |
| `public/build/`, `dist/` | `/public/build`, `/dist` |
| `coverage/`, `.nyc_output/`, `playwright-report/` | `/coverage`, `/.nyc_output`, `/playwright-report` |
| `*.pem`, `*.key`, `*.crt` | `*.pem`, `*.key`, `*.crt` |
| `*.sql`, `*.dump` | `*.sql`, `*.dump` |
| `.env`, `.env.local`, `.env.production` | `.env*` (zostaje `!.env.example`) |
| `.idea/`, `.vscode/`, `.fleet/`, `.zed/` | `/.idea`, `/.vscode`, `/.fleet`, `/.zed` |
| `.phpstan-cache/`, `.rector-cache/`, `.pest.cache/` | `/.phpstan-cache`, `/.rector-cache`, `/.pest.cache` |
| `node_modules/`, `vendor/` | `/node_modules`, `/vendor` |

## 2. Staged files — kontrola sekretów

```bash
git diff --cached | grep -iE "(api[_-]?key|secret|password|token|bearer|sk_live|sk_test|pk_live|pk_test|ghp_|gho_|aws_access|aws_secret|stripe_)" || echo "OK — brak wykrytych sekretów"
```

Jeśli output niepusty (oprócz „OK") — **STOP, zgłoś userowi**. Wskaż linie i zaproponuj refactor (przeniesienie do `.env`).

## 3. Pliki które NIGDY nie powinny być w repo

Sprawdź czy w `git ls-files` nie ma:
- `.env` (poza `.env.example`)
- `auth.json`, `composer.lock` z PII (rzadko, ale check)
- `*.pem`, `*.key`, `*.crt`, `id_rsa`
- backup/dump bazy

```bash
git ls-files | grep -E "(^\.env$|^\.env\.local|^\.env\.production|^auth\.json$|\.pem$|\.key$|\.crt$|id_rsa)" || echo "OK — repo czyste z sekretów historycznych"
```

Jeśli znajdziesz — STOP i zgłoś (wymaga `git rm --cached` + commit + rotacja sekretów + ewentualnie `git filter-repo` dla historii).

## 4. Output

Wygeneruj zwięzły raport w formacie:

```
🔍 Audit .gitignore — podsumowanie

Untracked → do .gitignore (N pozycji):
  + <wzorzec>   (znaleziono: <ścieżka>)
  ...

Staged sekrety: <OK / X znalezionych — szczegóły>

Repo historyczne sekrety: <OK / X znalezionych — szczegóły>

Akcje:
  1. Dodaj te wpisy do .gitignore: ...
  2. Wykonaj: git rm --cached <plik> dla...
  3. (jeśli sekrety w staged) Refactor ...
```

## 5. Akcje opcjonalne (po zgodzie usera)

Jeśli znaleziono propozycje wpisów — **zapytaj usera** czy zaktualizować `.gitignore` automatycznie.

Jeśli `git rm --cached` jest potrzebne — **zapytaj usera** zanim wykonasz.

**Nie commituj** tych zmian samodzielnie — to robota `/close-stage` lub osobnego commit z user approval (CLAUDE.md sekcja 5).
