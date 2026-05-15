# /review-frontend — Frontend Quality Code Review

Uruchom przegląd jakości frontendu projektu QR-Master (Vue 3 + TypeScript + i18n). Użyj **3 równoległych agentów Explore**, a następnie skonsoliduj wyniki.

## Agent 1 — TypeScript i Vue 3 patterns
Sprawdź w `resources/js/`:
- Brak `any` typów (implicit lub explicit) — tylko konkretne interfejsy
- Brak `// @ts-ignore` lub `// @ts-nocheck` bez uzasadnienia
- Wszystkie `defineProps<...>()` mają pełne typy
- Composition API wszędzie (`<script setup lang="ts">`) — zero Options API
- `computed()`, `ref()`, `watch()` poprawnie importowane
- Brak `reactive()` na prymitywach (użyj `ref()`)
- `onUnmounted()` czyści subsrypcje Echo/intervals/timers
- Brak `console.log()`, `console.error()` w commitowanym kodzie

## Agent 2 — i18n kompletność
Sprawdź:
- Zero hardcoded stringów w `<template>` — wszystkie przez `t('...')`
- Wszystkie klucze w `en.ts` mają odpowiedniki w `pl.ts` i odwrotnie
- Brak polskich stringów w angielskiej wersji i vice versa
- Klucze i18n używane w komponentach faktycznie istnieją w plikach lokalizacji
- Brak zduplikowanych kluczy w tym samym namespace
- Strony z nowych modułów (API tokens, webhooks, CSV import, affiliate) mają kompletne i18n

## Agent 3 — Komponenty i struktura
Sprawdź:
- Shadcn-vue komponenty kopiowane do `resources/js/components/ui/` (nie importowane z npm)
- Brak nieużywanych importów w `<script setup>`
- `defineOptions({ layout: AppLayout })` na wszystkich stronach Inertia
- Strony Inertia w `resources/js/pages/` — PascalCase, zgodne z routingiem
- `AppLayout` używany spójnie
- Dark mode klasy (`dark:`) w komponentach
- Brak inline styles gdzie jest odpowiednik Tailwind
- `Badge`, `Button` używają variant prop zamiast custom klas

## Format raportu

```
## 🔴 KRYTYCZNE (TypeScript błędy, broken i18n)
- [plik:linia] Opis problemu

## 🟡 WAŻNE (niekompletne i18n, złe patterns)
- [plik:linia] Opis problemu

## 🟢 SUGESTIE
- [plik:linia] Opis problemu

## ✅ WERYFIKACJE PRZESZŁY
```
