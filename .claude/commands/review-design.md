# /review-design — Design System Review

Uruchom przegląd komponentów Vue pod kątem zgodności z design systemem QR-Master (violet/cyan/gold, dark-first). Użyj **2 równoległych agentów Explore**, a następnie skonsoliduj wyniki.

## Agent 1 — Paleta i dark mode

Sprawdź w plikach `resources/js/pages/**/*.vue` i `resources/js/components/**/*.vue`:

- **Hardcoded kolory zamiast tokenów:** szukaj `#[0-9a-fA-F]{3,6}`, `rgb(`, `hsl(` poza plikami CSS — każdy taki przypadek to błąd
- **Brak CSS vars dla tła/tekstu:** `bg-white`, `bg-black`, `text-black`, `text-white` zamiast `bg-background`/`text-foreground`
- **Brak `dark:` wariantów** przy hardcoded `bg-gray-*`, `bg-slate-*` — lista podejrzanych miejsc
- **Zgodność z paletą:** czy używane `text-violet-*`, `text-cyan-*`, `text-gold-*` czy inne kolory spoza palety?

Raport: lista plików + linie z problemem i sugestia poprawki.

## Agent 2 — Ikony, interaktywność, kontrast

Sprawdź:

- **Spójność ikon lucide-vue-next:** czy używane są tylko `size-3`, `size-3.5`, `size-4`, `size-5`, `size-10`, `size-12`? Podaj anomalie (np. `w-6 h-6` zamiast `size-6`)
- **Hover/focus states:** czy interaktywne elementy mają `hover:` i `focus-visible:ring-*`? Szukaj `<button`, `<a`, `<Button`, `<Link` bez hover/focus
- **Brak transitions:** elementy interaktywne bez `transition-*` — subtelne ale ważne
- **Kontrast WCAG:** sprawdź czy `text-muted-foreground` jest używany na `bg-card` (OK) vs `bg-secondary` (może być za mały kontrast) — wskaż konkretne miejsca
- **Animacje wejścia:** czy nowe komponenty (modalne, sheet, dropdown) używają animacji z `tw-animate-css` lub `motion-v`?

Raport: lista problemów z priorytetem 🔴 / 🟡 / 🟢.

---

## Format raportu końcowego

```
# 🎨 Design Review — QR-Master
Data: {data}

## 🔴 BLOKERY (łamią design system)
Hardcoded kolory, brak dark mode, złe kontrasty.

## 🟡 WAŻNE (niespójność, brak interaktywności)
Brakujące hover states, złe rozmiary ikon, brak transitions.

## 🟢 SUGESTIE (polish i finezja)
Brakujące animacje, możliwości użycia gradient text, glow effects.

## ✅ CO JEST DOBRZE
Lista poprawnie zastosowanych wzorców.
```

## Po raporcie

Zaproponuj konkretne poprawki (diff-style: stare → nowe klasy Tailwind) dla 3 najważniejszych problemów.
