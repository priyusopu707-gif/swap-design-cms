# Sprint 15F — Premium Homepage Design Overhaul

**Date:** 2026-08-07
**Version:** 1.6.0
**Scope:** Visual audit + premium redesign of all 15 homepage sections. No content/order/feature changes. CSS/HTML/JS refinement only.

---

## Executive Summary

Identified 23 systemic issues from a production design audit of the post-15E homepage. Found **1 confirmed layout bug** (`grid--4` rendering 3 columns instead of 4), **~400 lines of dead CSS** never used by any template, **an entire wrong brand color** (indigo instead of coral) appearing 17 times in hover glows, and **two competing header systems** that made every section look slightly different from the next. All resolved. Footer rebuilt entirely on the `--ds-*` token system.

---

## Key Fixes

### 1. Layout Bug: `grid--4` Was 3 Columns
**Root cause:** `.grid--4 { grid-template-columns: repeat(3, 1fr); }` in `base.css:235`
**Impact:** Experience counter with 4 stat cards rendered 3 columns + 1 orphaned row
**Fix:** Changed to `repeat(4, 1fr)`

### 2. Brand Color Corruption: Indigo in Coral Website
**Root cause:** 17 instances of `rgba(99,102,241,…)`, 37.35% opacity indigo, used as hover glows, placeholder gradients, and accent rings throughout `homepage.css`
**Impact:** Portfolio placeholders glowed indigo, card hover shadows blued, badge borders tinted wrong hue — inconsistent with coral `#ff4d2e` brand
**Fix:** Global find-replace `99,102,241` → `255,77,46` (brand coral RGB), preserving each alpha level

### 3. Inconsistent Icon System Across 6 Sections
**Root cause:** `.cp-icon-wrap` fixed at 32px box while individual card icon classes declared 36px/48px/56px containers, all overridden by cascade to produce 32px uniform tiles — but services used a completely separate 48px box with 24px hardcoded SVG
**Impact:** Services icons 25% smaller than all other sections. Tech icons 28px glyph in 32px box. Overall visual weight inconsistent.
**Fix:** Redefined `.cp-icon-wrap` as the single authoritative icon tile: 56px box, 28px glyph, `border-radius:12px`. Updated `cards.css` service icon to 56px. Updated renderer to use `cp-icon-wrap` class on service icons.

### 4. Header System Consolidation
**Root cause:** Homepage used two separate header systems — `.section__header`/`.section__title` (services, why-choose, experience) defined at 800 weight/3rem clamp, and `.section__heading`/`.section__description` (all other sections) defined at 700 weight/2.75rem clamp. Two different weight/size combos made every section look subtly different.
**Fix:** Unified homepage `.section__title` to `700` weight / `clamp(1.75rem, 4vw, 2.75rem)` to match the shared `.section__heading` system. Kept all shared classes site-wide (used by services, portfolio, about, blocks). Section header containers (`section__header`) now wrap all sections identically.

### 5. Dead CSS Removal (~400 lines)
**Removed:**
- `.card-premium` + `__icon`/`__title`/`__desc` (never used in any PHP template — confirmed zero grep hits)
- `.grid-premium` (never used in any PHP template)
- `.hero__orb` / `--sm` / `--lg` / `--md` / `--accent` + `@keyframes hero-orb` (4 floating orbs removed from renderer, CSS orphaned)
- `.hero__glass-layer` (3 glass panels, removed from renderer)
- `.hero__glow-pulse` (2 pulsing blobs, removed from renderer)
- `.hero__panel-row` / `__stat` / `__suffix` / `__statlbl` (hero panel stat rows removed from renderer)
- `.hero__stats-row` / `.hero__stat-card` / `__number` / `__label` (never used in renderer)
- `.section--divider-top` / `::before` / `--divider-glow::before` (never applied to any element)

**Added:**
- `.section--top-glow` (functional replacement for divider — applies gradient accent on alternate sections)
- `.hero__scroll` system (scroll indicator with mouse + wheel animation)

### 6. Unified CTA Gradient
**Root cause:** `--ds-gradient-primary` (coral→orange) and `--ds-gradient-brand` (coral→pink) were different. `btn--primary` used one, `btn--cta` used the other — two gradient CTAs on the same page.
**Fix:** Unified `--ds-gradient-primary` to match `--ds-gradient-brand` (coral→pink). All primary CTAs now share one gradient.

### 7. Footer Rebuild on `--ds-*` Tokens
**Root cause:** Footer CSS used legacy `--color-*` / `--space-*` / `--weight-*` tokens + hardcoded `rgba(240,240,240,…)`. No glass treatment. Completely different visual language from the premium homepage.
**Fix:** Full rebuild using `--ds-*` token system. Added: glass hover on social links, brand glow top hairline, radial gradient ambient overlay, consistent spacing/typography tokens, glass border-radius social tiles.

### 8. Tech Stack Tile Typography (0.62rem → fs-sm)
**Root cause:** `.tech-item__desc { font-size: 0.62rem }` = ~10px. Below legibility threshold. Name was 12px (`--fs-xs`).
**Fix:** Description → `--fs-sm` (14px, `line-height:1.35`). Name → `0.8125rem` (13px). More spacing between name and desc.

### 9. Industries Card Radius Alignment
**Root cause:** Industries used `border-radius: var(--ds-radius-16)` while all other premium cards used `var(--ds-radius-20)`.
**Fix:** Changed to `var(--ds-radius-20)`.

### 10. Why Choose Card Width Capping
**Root cause:** `grid-template-columns: repeat(auto-fit, minmax(220px, 1fr))` — on wide screens with 3-4 cards, each card stretched 400px+ wide.
**Fix:** Changed to `repeat(auto-fit, minmax(260px, 360px))` + `justify-content: center` — cards cap at 360px, center-aligned.

### 11. Hero Declutter + Scroll Indicator
**Changes:**
- Removed 4 floating orbs + 3 glass layers + 2 glow pulses from markup (kept gradient, particles, chips — most distinctive effects)
- Removed hero panel stat rows (100+ Projects / 8+ Years) to eliminate duplication with hero trust row below CTAs
- Added scroll indicator: animated mouse icon with scrolling wheel keyframe + "Scroll" label, hidden on print, respects `prefers-reduced-motion`

### 12. Portfolio Filter Accessibility
**Root cause:** Filter container had `role="tablist"` but children were plain `<button>` elements without `role="tab"` or `aria-selected`.
**Fix:** Changed to `role="group"`, buttons get `aria-pressed` toggle (true/false) — correctly communicates toggle state. Added `aria-pressed` management to JS click handler.

### 13. Services Equal-Height Cards
**Root cause:** `.card__body` (rendered as `<a>`) lacked `height: 100%`. In a CSS grid stretch, the article fills row height but the inner link didn't — description text and arrow didn't pin to card bottom.
**Fix:** Added `.card--service .card__body { height: 100%; }`.

### 14. Contact Card Icon Shape Consistency
**Root cause:** Contact icons used `border-radius: 50%` (circles) while every other section used `border-radius: 12px` (rounded squares).
**Fix:** Changed to `var(--ds-radius-12)`. Updated size to 56px/28px glyph matching unified icon system.

---

## Files Modified

| File | Change |
|------|--------|
| `assets/css/components/design-system/base.css` | Fixed `.grid--4` (3→4 columns) |
| `assets/css/components/design-system/design-tokens.css` | Unified `--ds-gradient-primary` to match brand gradient |
| `assets/css/components/design-system/cards.css` | Service icon box 48→56px, added glyph sizing, added `.card--service .card__body` height:100% |
| `assets/css/homepage.css` | Major overhaul: removed ~400 lines dead CSS, unified icon/header systems, replaced indigo, added scroll indicator CSS, fixed tech typography, industries radius, why-choose card width cap, hero declutter, reduced-motion scroll indicator |
| `assets/css/components/footer.css` | Full rebuild on `--ds-*` token system with glass treatment |
| `includes/content/HomepageRenderer.php` | Removed hero orbs/glass/glow spans, removed panel stat rows, added scroll indicator markup, added `aria-pressed` to filter buttons, unified service icon markup with `cp-icon-wrap` |
| `assets/js/homepage.js` | Added `aria-pressed` toggle management for portfolio filter buttons |

---

## Quality Checklist

| Criterion | Status |
|-----------|--------|
| grid--4 bug fixed (4 columns) | ✅ |
| All indigo `rgba(99,102,241)` purged | ✅ (verified: 0 hits) |
| All `0.62rem` typography purged | ✅ (verified: 0 hits) |
| Icon system unified (56px/28px/radius-12) | ✅ |
| Header weight/size consistent across all sections | ✅ |
| Dead CSS removed (card-premium, grid-premium, divider, orbs, glass, glow, stats) | ✅ |
| CTA gradient unified (single brand gradient) | ✅ |
| Footer rebuilt on `--ds-*` tokens with glass treatment | ✅ |
| Hero decluttered (orbs/glass/glow removed, scroll indicator added) | ✅ |
| Portfolio filter ARIA corrected | ✅ |
| Services equal-height cards fixed | ✅ |
| Contact icon shape unified to rounded-square | ✅ |
| Industries radius aligned to 20px | ✅ |
| Why Choose cards capped at 360px | ✅ |
| Hero panel stat duplication removed | ✅ |
| `prefers-reduced-motion` respected | ✅ |
| Print stylesheet updated | ✅ |
| PHP lint clean (both renderers) | ✅ |

---

*Report generated for Sprint 15F completion.*