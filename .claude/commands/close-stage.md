---
description: Zamyka etap N z PROJECT.md — testy, oznaczenie ukończenia, commit i push (każdy krok ze zgodą usera)
argument-hint: <numer-etapu>
allowed-tools: Bash, Read, Edit, AskUserQuestion
---

Wykonaj pełny **Workflow Ukończenia Etapu** dla etapu **$ARGUMENTS** zgodnie z PROJECT.md i CLAUDE.md sekcja 5.

## Kroki (wykonaj sekwencyjnie, NIE pomijaj żadnego)

### 1. Walidacja wstępna

- Przeczytaj `PROJECT.md` i znajdź sekcję `### Etap $ARGUMENTS:`.
- Sprawdź, czy WSZYSTKIE sub-checkboxy w tym etapie są `- [x]`. Jeśli nie — zatrzymaj się i pokaż userowi listę nieukończonych zadań do potwierdzenia (może chce wymusić zamknięcie albo dokończyć resztę).
- Sprawdź sekcję „Kryterium ukończenia" tego etapu — przypomnij userowi i zapytaj, czy zostało spełnione.

### 2. Test suite

Uruchom sekwencyjnie. Jeśli którykolwiek krok zwróci błąd — zatrzymaj się i pokaż output userowi.

```bash
php artisan test --parallel
./vendor/bin/phpstan analyse
./vendor/bin/pint --test
npm run test
npm run lint
```

### 3. Aktualizacja nagłówka etapu w PROJECT.md

Edytuj `PROJECT.md`:
- Z: `### Etap $ARGUMENTS: <Tytuł>`
- Na: `### ✅ Etap $ARGUMENTS: <Tytuł> *(ukończony YYYY-MM-DD)*`

YYYY-MM-DD pobierz z `date +%Y-%m-%d`.

### 4. Weryfikacja .gitignore

```bash
git status -u
```

Przeanalizuj untracked pliki wg sygnałów z CLAUDE.md sekcja 5a:
- pliki w `storage/logs/`, `storage/framework/cache/`, `bootstrap/cache/`
- `*.log`, `*.cache`, `*.tmp`, `coverage/`, `dist/`, `public/build/`
- `*.pem`, `*.key`, `*.crt`, `*.sql`, `*.dump`
- pliki `.env*` (poza `.env.example`)
- nowe katalogi IDE
- cache narzędzi (`.phpstan-cache`, `.rector-cache`, `.pest.cache`)

Jeśli znajdziesz coś takiego — najpierw zaktualizuj `.gitignore`, potem `git rm --cached <plik>` jeśli już zaśledzony.

Sprawdź też staged pod kątem sekretów:
```bash
git diff --cached | grep -iE "(api[_-]?key|secret|password|token|bearer|sk_live|pk_live)" || echo "OK — brak sekretów"
```

### 5. Stage zmian

```bash
git add -A
git status
git diff --cached --stat
```

### 6. ❗ ZGODA USERA NA COMMIT

Pokaż userowi:
- Listę plików w stage
- Proponowany commit message w formacie Conventional Commits:
  ```
  feat(stage-$ARGUMENTS): zakończenie etapu $ARGUMENTS — <krótki opis z tytułu etapu>
  ```
- Krótkie uzasadnienie (co etap dostarczył)

**Zapytaj wprost przez AskUserQuestion: "Czy mogę wykonać ten commit?"** Czekaj na jawne "tak". Jeśli user prosi o zmianę message — popraw i zapytaj ponownie.

Po zatwierdzeniu wykonaj commit (heredoc, **BEZ** `Co-Authored-By: Claude`, **BEZ** `🤖 Generated with Claude Code`):

```bash
git commit -m "$(cat <<'EOF'
feat(stage-$ARGUMENTS): zakończenie etapu $ARGUMENTS — <opis>

<bullet points z najważniejszymi dostawami etapu>
EOF
)"
```

### 7. ❗ ZGODA USERA NA PUSH (osobne pytanie)

Pokaż:
- Branch docelowy (zwykle `develop`)
- Liczba commitów do wypchnięcia (`git log origin/<branch>..HEAD --oneline`)

**Zapytaj wprost przez AskUserQuestion: "Czy mogę wypchnąć na origin/develop?"** Czekaj na jawne "tak".

```bash
git push origin develop
```

### 8. Opcjonalny tag release (po pytaniu o zgodę)

Zapytaj usera, czy chce utworzyć tag `stage-$ARGUMENTS`. Jeśli tak:

```bash
git tag -a stage-$ARGUMENTS -m "Etap $ARGUMENTS: <tytuł>"
git push origin stage-$ARGUMENTS
```

## Reguły bezwzględne (przypomnienie)

- **NIGDY nie commituj bez jawnej zgody usera.** Jednorazowe „tak" w sesji NIE zwalnia z pytania przy następnych commitach.
- **NIGDY nie dodawaj `Co-Authored-By: Claude` ani innych AI attribution.** To repozytorium wygląda jak praca jednego dewelopera.
- **NIGDY nie pushuj bez osobnej zgody.** Commit ≠ push.
- Jeśli `git status` pokazuje śmieci — najpierw napraw `.gitignore`, dopiero potem proponuj commit.

Po zakończeniu — krótkie podsumowanie: co zostało zrobione, jaki tag (jeśli był), link do brancha.
