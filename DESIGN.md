# QR-Master Design System

## Paleta kolorów

Trzy kolory wiodące: **Violet** (primary), **Cyan** (accent), **Gold** (premium).
Tryb: **dark-first** — aplikacja jest zawsze w dark mode.

### Violet / Fiolet — Primary
Używany do: przycisków CTA, linków aktywnych, focus rings, active states w menu.

| Token / klasa | oklch | Hex (przybliżony) |
|---|---|---|
| `text-primary` / `bg-primary` | `oklch(0.66 0.25 285)` | `#8b5cf6` |
| `text-violet-400` | `oklch(65% 0.22 292)` | `#a78bfa` |
| `text-violet-500` | `oklch(56% 0.26 292)` | `#8b5cf6` |
| `text-violet-600` | `oklch(49% 0.24 292)` | `#7c3aed` |

### Cyan / Turkus — Accent
Używany do: drugorzędnych akcji, danych analitycznych, ikon info, wykresów (druga seria).

| Token / klasa | oklch | Hex (przybliżony) |
|---|---|---|
| `text-cyan` / `bg-cyan` | `oklch(0.72 0.15 200)` | `#22d3ee` |
| `text-cyan-400` | `oklch(0.72 0.15 200)` | `#22d3ee` |
| `text-cyan-500` | `oklch(0.65 0.15 200)` | `#06b6d4` |
| `text-cyan-600` | `oklch(0.57 0.14 200)` | `#0891b2` |

### Gold / Złoty — Premium
Używany do: odznak Pro/Business, CTA upgrade, gwiazdek, elementów wyróżniających.

| Token / klasa | oklch | Hex (przybliżony) |
|---|---|---|
| `text-gold` / `bg-gold` | `oklch(0.78 0.15 85)` | `#fbbf24` |
| `text-gold-400` | `oklch(0.80 0.15 85)` | `#fbbf24` |
| `text-gold-500` | `oklch(0.74 0.16 85)` | `#f59e0b` |
| `text-gold-600` | `oklch(0.65 0.15 85)` | `#d97706` |

---

## Powierzchnie (dark mode)

| Rola | Token | oklch | Zastosowanie |
|---|---|---|---|
| Tło strony | `bg-background` | `oklch(0.12 0.025 272)` | body, page background |
| Karta | `bg-card` | `oklch(0.17 0.025 272)` | Card, Sheet, Dialog |
| Secondary | `bg-secondary` | `oklch(0.22 0.025 272)` | hover states, subtle bg |
| Sidebar | `bg-sidebar` | `oklch(0.10 0.030 272)` | najciemniejszy panel |
| Obramowanie | `border-border` | `oklch(0.28 0.028 272)` | karty, dividers |

---

## Typografia

- **Headings/UI:** Geist Sans (`font-sans`) — 400, 500, 600, 700
- **Kod/Mono:** Geist Mono (`font-mono`) — 400, 500
- **Kolor tekstu:** `text-foreground` (near-white z lekkim cool tint)
- **Subdued:** `text-muted-foreground` — opisy, metadane

### Hierarchia

```
h1: text-2xl/3xl font-bold
h2: text-xl font-semibold
h3: text-base font-semibold
body: text-sm
caption: text-xs text-muted-foreground
```

---

## Ikony

Używaj wyłącznie **lucide-vue-next**. Standardowe rozmiary:

| Kontekst | Klasa | px |
|---|---|---|
| W przycisku / menu item | `size-4` | 16px |
| Standalone / duże | `size-5` | 20px |
| Hero / empty state | `size-10` lub `size-12` | 40/48px |
| Badge / inline | `size-3` lub `size-3.5` | 12/14px |

Kolor ikony: dziedziczy z `currentColor`. W active state: `text-primary`. W muted: `text-muted-foreground`.

---

## Interaktywność

### Hover states
```css
hover:bg-secondary    /* subtelne tło na karcie/menu */
hover:text-primary    /* link → violet */
hover:opacity-80      /* ikona / obraz */
```

### Focus ring
```css
focus-visible:ring-2 focus-visible:ring-ring   /* ring = violet */
```

### Transitions
```css
transition-colors duration-150   /* kolor/bg */
transition-all duration-200      /* pozycja/skala */
```

### Animacje (Motion-v / tw-animate-css)
- **Wejście:** `animate-fade-in`, `animate-slide-in-from-bottom`
- **Hover glow:** `hover:shadow-[0_0_20px_oklch(0.66_0.25_285/0.4)]` (violet glow)
- **Skeleton:** `animate-pulse bg-muted`

---

## Zasady użycia kolorów

| Sytuacja | Kolor |
|---|---|
| Główny przycisk akcji | `bg-primary` (violet) |
| Drugi przycisk / info | `text-cyan-400` / `border-cyan-500` |
| Upgrade / Pro badge | `text-gold-500` / `bg-gold-500/10` |
| Error / destructive | `text-destructive` |
| Success | `text-success-500` |
| Wykresy — seria 1 | `#8b5cf6` (violet) |
| Wykresy — seria 2 | `#22d3ee` (cyan) |
| Wykresy — seria 3 | `#f59e0b` (gold) |

---

## Przykłady klas

```html
<!-- Primary button -->
<Button class="bg-primary text-primary-foreground hover:bg-primary/90">

<!-- Pro badge -->
<Badge class="bg-gold-500/10 text-gold-500 border-gold-500/30">Pro</Badge>

<!-- Info chip -->
<span class="text-cyan-400 bg-cyan-400/10 rounded-full px-2 py-0.5 text-xs">

<!-- Card z glow hover -->
<Card class="hover:border-primary/40 hover:shadow-[0_0_24px_oklch(0.66_0.25_285/0.15)] transition-all duration-200">

<!-- Gradient text -->
<span class="bg-gradient-to-r from-violet-400 to-cyan-400 bg-clip-text text-transparent">
```

---

## Design Review

Uruchom `/review-design` aby sprawdzić zgodność komponentów z tym przewodnikiem.
