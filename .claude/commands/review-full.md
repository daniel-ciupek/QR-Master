# /review-full — Pełny Code Review (wszystkie domeny)

Uruchom kompleksowy code review projektu QR-Master. Uruchamiam **wszystkie 5 agentów review równolegle** (jeden call z wieloma narzędziami), a następnie tworzę skonsolidowany raport priorytetyzowany.

## Uruchom równolegle

1. **Security** — `/review-security` (auth, CSRF, rate limiting, PII, HMAC, injection)
2. **Architecture** — `/review-architecture` (Action/Service/DTO, kontrolery, Jobs)
3. **API Design** — `/review-api` (REST conventions, status codes, ability checks)
4. **Frontend** — `/review-frontend` (TypeScript, i18n, Vue 3, dark mode)
5. **Performance** — `/review-performance` (N+1, indeksy, cache, queue)

## Skonsolidowany raport końcowy

Po zebraniu wyników z wszystkich 5 agentów:

### Format output

```
# 🔍 Full Code Review — QR-Master
Data: {data}
Etapy objęte: {zakres}

## 🚨 BLOKERY (napraw przed kontynuacją)
Problemy krytyczne wymagające natychmiastowej naprawy.

## 🔴 WYSOKIE PRIORYTETY (napraw w tym tygodniu)
Poważne problemy wpływające na security/correctness.

## 🟡 ŚREDNIE PRIORYTETY (napraw przed deploymentem)
Architektoniczne lub jakościowe problemy.

## 🟢 NISKIE PRIORYTETY (nice to have)
Sugestie i optymalizacje.

## 📊 PODSUMOWANIE
- Security: X problemów (Y krytycznych)
- Architecture: X problemów
- API Design: X problemów
- Frontend: X problemów
- Performance: X problemów

## ✅ CO JEST DOBRZE
Lista rzeczy zrobionych poprawnie.
```

## Kolejność napraw

Po raporcie zaproponuj:
1. Kolejność napraw (BLOKERY → WYSOKIE → ŚREDNIE)
2. Grupowanie w logiczne commity (per domenę)
3. Szacowany czas napraw
