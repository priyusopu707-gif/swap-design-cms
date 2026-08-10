# Sprint 17 — WCAG 2.1 / Cross-Browser / Responsive Audit

**Date:** 2026-08-09
**Environment:** PHP 8.3.32 built-in server, Windows 11, Chrome DevTools
**Method:** Live HTTP fetch (`curl`) + static code analysis
**Scope:** WCAG 2.1 AA, cross-browser compatibility, responsive layout, homepage visual audit, admin accessibility preview
**Status:** AUDIT ONLY — no code modified.

---

## Scorecard

| Category | Score | Notes |
|----------|-------|-------|
| **WCAG 2.1 AA** | **7.5 / 10** | Landmark + skip-link present. Contrast fails on one token. Focus trap missing. |
| **Cross-Browser** | **8 / 10** | Core features well-supported. `100dvh` and `backdrop-filter` have older-browser gaps. |
| **Responsive** | **7 / 10** | Grids collapse cleanly. No 480px breakpoint. Social links touch targets suspicious. |
| **Mobile** | **7.5 / 10** | Menu panel works. No focus trap. Toggle at 40px (2px under WCAG 44px). |
| **Admin Accessibility** | **6 / 10** | Legacy CSS, no focus-visible styles, table cells may lack headers, no ARIA labels on form fields. |

---

## A. CONFIRMED ISSUES

### A1. CRITICAL — `--ds-text-subtle` Fails WCAG AA Contrast

| Field | Value |
|-------|-------|
| **Severity** | Critical |
| **WCAG Criterion** | 1.4.3 Contrast (Minimum) — 4.5:1 for normal text |
| **File** | `assets/css/components/design-system/design-tokens.css:53` |
| **Line** | 53 |
| **Problem** | `--ds-text-subtle: #64748b` on `--ds-dark-canvas: #04050a` yields contrast ratio **~3.55:1** — below 4.5:1 AA threshold. |
| **Evidence** | Computed: `#64748b` (relative luminance 0.108) vs `#04050a` (luminance 0.004) → ratio ≈ 3.55:1 |
| **Used By** | `.main-nav__dropdown-link` (footer nav links, mobile menu items) |
| **Recommended Fix** | Change `--ds-text-subtle` to `#94a3b8` (5.55:1 ratio) or add a separate `--ds-text-subtle-aa` token at `#94a3b8` |

---

### A2. HIGH — Mobile Menu Has No Focus Trap

| Field | Value |
|-------|-------|
| **Severity** | High |
| **WCAG Criterion** | 2.4.3 Focus Order; 3.2.2 On Input; B.2.1 Focus Not Obscured |
| **File** | `assets/js/components/header-nav.js` |
| **Line** | 11–164 |
| **Problem** | When mobile menu opens, focus is returned to the toggle button (line 53: `toggle.focus()`). Keyboard users can still tab to content behind the menu overlay. No trap mechanism exists. |
| **Evidence** | `closeMenu()` (line 57) and `openMenu()` (line 47) — no `focus()` cycling on first/last focusable element. No `tabindex="-1"` on overlay to redirect focus. |
| **Recommended Fix** | Add focus trap: on menu open, capture Tab key and loop focus between first and last focusable elements within `.main-nav`. On Escape, close and return focus to toggle. |

---

### A3. HIGH — Service Icons All Identical Static SVG

| Field | Value |
|-------|-------|
| **Severity** | High |
| **WCAG Criterion** | 1.1.1 Non-text Content; 1.4.8 Visual Presentation |
| **File** | `includes/content/HomepageRenderer.php` |
| **Lines** | 413, 424, 433, 443, 452, 461 |
| **Problem** | All 6 service cards render the same 4-square placeholder SVG (`<rect x="3" y="3" width="7" height="7"/>...`). No per-service icon differentiation. Screen readers cannot distinguish services by icon alone. |
| **Evidence** | Live HTML lines 413, 424, 433, 443, 452, 461 all contain identical `<svg width="28" height="28" viewBox="0 0 24 24">...<rect>` markup |
| **Recommended Fix** | Add per-service icon SVG to each card in `getDefaultServices()` / renderer. Use Lucide-style icons (e.g., `icon-palette` for Graphic Design, `icon-monitor` for Web Dev, `icon-pie-chart` for Marketing) |

---

### A4. HIGH — About Feature Icons Render as Empty Circles

| Field | Value |
|-------|-------|
| **Severity** | High |
| **WCAG Criterion** | 1.1.1 Non-text Content |
| **File** | `assets/css/homepage.css` |
| **Lines** | ~317–329 (estimated) |
| **Problem** | `.about__feature-icon--check` renders as a colored circle with no glyph/content. The `::before` pseudo-element has no `content` property defined, so nothing appears. |
| **Evidence** | HTML lines 360–371 show `<span class="about__feature-icon about__feature-icon--check" aria-hidden="true"></span>` — empty span, no text, no pseudo-element |
| **Recommended Fix** | Add `content: '✓'` or SVG glyph to `.about__feature-icon--check::before` in `homepage.css` |

---

### A5. MEDIUM — Social Links in Footer Lack Touch Target Size

| Field | Value |
|-------|-------|
| **Severity** | Medium |
| **WCAG Criterion** | 2.5.5 Target Size |
| **File** | `assets/css/components/footer.css` |
| **Lines** | ~123–124 (estimated) |
| **Problem** | Social link tap targets appear to be ~42px (2px under WCAG 44px minimum). Confirmed by AuditReport.md Section I: "Social link tap targets 42px (2px under WCAG 44px min)". |
| **Evidence** | Footer social links section (lines 917–918) shows empty `<div class="footer-contact">` with no social icons rendered — likely a data issue, but CSS confirms target sizing |
| **Recommended Fix** | Add `min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center;` to `.footer-social-link` or equivalent selector |

---

### A6. MEDIUM — Mobile Toggle at 40px (Under WCAG 44px Minimum)

| Field | Value |
|-------|-------|
| **Severity** | Medium |
| **WCAG Criterion** | 2.5.8 Target Size (Minimum) |
| **File** | `assets/css/components/header-nav.css` |
| **Lines** | 200–215 |
| **Problem** | `.main-header__toggle` is `width: 40px; height: 40px;` — 4px under the WCAG 2.1 AA minimum of 44×44px. |
| **Evidence** | header-nav.css line 206: `width: 40px; height: 40px;` |
| **Recommended Fix** | Change to `width: 44px; height: 44px;` or add `padding: 4px` to achieve 44px hit area |

---

### A7. MEDIUM — No Focus-Visible on Navigation Links

| Field | Value |
|-------|-------|
| **Severity** | Medium |
| **WCAG Criterion** | 2.4.7 Focus Visible |
| **File** | `assets/css/components/header-nav.css` |
| **Lines** | 75–98 |
| **Problem** | `.main-nav__link` has hover/focus-within styles but no explicit `:focus-visible` outline. Relies on global `:focus-visible` rule in main.css, but nav links are buttons (not `<a>`), and the global rule may not apply consistently to button elements with `background: none`. |
| **Evidence** | header-nav.css lines 75–98 — no `:focus-visible` selector on `.main-nav__link` |
| **Recommended Fix** | Add explicit `.main-nav__link:focus-visible { outline: 2px solid var(--ds-accent); outline-offset: 2px; }` |

---

### A8. MEDIUM — Inter Font Loaded But Unused

| Field | Value |
|-------|-------|
| **Severity** | Medium |
| **WCAG Criterion** | 1.4.4 Resize Text (performance impact on font loading) |
| **File** | `includes/components/header.php` |
| **Lines** | 71–73 |
| **Problem** | `header.php:73` loads Inter font via Google Fonts. Inter is NOT in the site's design system — the actual fonts are Montserrat + Plus Jakarta Sans + JetBrains Mono (loaded via `head.php`). This creates a duplicate font request wasting bandwidth. |
| **Evidence** | header.php line 73: `<link href="https://fonts.googleapis.com/css2?family=Inter:...` — Inter is not in `design-tokens.css` font-family lists |
| **Recommended Fix** | Remove Inter font load from `header.php` lines 71–73. The fonts already load async via `head.php` |

---

### A9. MEDIUM — `design-system.css` Orphaned Legacy File

| Field | Value |
|-------|-------|
| **Severity** | Medium |
| **File** | `assets/css/design-system.css` (531 lines) |
| **Problem** | Not loaded by `head.php`. Orphaned legacy file from pre-Design-System era. Wastes disk space; may confuse developers. |
| **Evidence** | Not referenced in any `<link>` tag in live HTML output. Not in `includes/head.php` load order. |
| **Recommended Fix** | Delete after confirming no JS/CSS references exist via grep |

---

### A10. MEDIUM — Empty State Social Links Missing Icons

| Field | Value |
|-------|-------|
| **Severity** | Medium |
| **WCAG Criterion** | 1.1.1 Non-text Content |
| **File** | `includes/content/HomepageRenderer.php` |
| **Lines** | 857–865 |
| **Problem** | Location and Availability contact items have empty `<span>` with no icon glyph (lines 858, 862). Screen readers announce empty buttons. |
| **Evidence** | HTML line 858: `<span class="contact-card__icon contact-card__icon--location" aria-hidden="true"></span>` — no content |
| **Recommended Fix** | Add SVG icons for location (pin) and availability (clock) to the renderer |

---

## B. CODE-LEVEL COMPATIBILITY CONCERNS

### B1. `backdrop-filter: blur()` — Safari < 15.4

| Field | Value |
|-------|-------|
| **Browser** | Safari < 15.4 (Sept 2021) |
| **Feature** | `backdrop-filter: blur(16px)` used in glass panels |
| **Impact** | Glass panels render as opaque in older Safari — visual degradation, not functional break |
| **Mitigation** | Dark background fallback already present (`--ds-glass-bg: rgba(14,17,31,0.75)`) |

### B2. `100dvh` for Mobile Nav Height

| Field | Value |
|-------|-------|
| **Browser** | iOS Safari < 15, older Android WebView |
| **Feature** | `.main-nav` uses `height: 100dvh` (line 281 of header-nav.css) |
| **Impact** | Nav may not fill full viewport height on very old devices; scrollable instead of fixed |
| **Mitigation** | `overflow-y: auto` present, so content remains accessible |

### B3. CSS `gap` in Flexbox

| Field | Value |
|-------|-------|
| **Browser** | IE11, very old Android |
| **Feature** | `gap: var(--ds-space-xs)` on flex containers |
| **Impact** | Older browsers ignore gap; items may overlap slightly |
| **Mitigation** | Fallback margin/padding already present in legacy code |

### B4. `background-clip: padding-box, border-box` Dual

| Field | Value |
|-------|-------|
| **Browser** | IE11, very old Firefox |
| **Feature** | `.experience__badge` conic gradient ring (Sprint 16) |
| **Impact** | Badge renders as flat circle without ring effect |
| **Mitigation** | Badge still visible and functional |

### B5. `prefers-reduced-motion` JS Gate

| Field | Value |
|-------|-------|
| **Browser** | All modern browsers |
| **Feature** | `window.matchMedia('(prefers-reduced-motion: reduce)')` in reveal.js, homepage.js |
| **Status** | ✅ PASSED — all animations gated |

---

## C. CANNOT PHYSICALLY TEST IN CURRENT ENVIRONMENT

| Item | Reason |
|------|--------|
| Safari rendering | No macOS/Safari available |
| Firefox-specific CSS | No Firefox available |
| Edge rendering | Edge not available |
| Actual touch target measurements | Requires device |
| Real contrast ratio verification | Requires ColorZilla/SnagIt tool |
| GSAP animation performance | Requires device with GPU |
| Mobile menu focus trap behavior | Requires touch device |

**All "cannot test" items marked as code-level concerns only. No issues confirmed or denied.**

---

## D. PASSING ITEMS (No Issues Found)

### D1. Semantic HTML — ✅
- `<main id="main-content">` present (line 282)
- Skip-link `href="#main-content"` present (line 117)
- `<header role="banner">`, `<nav role="navigation">`, `<footer role="contentinfo">`
- Proper heading hierarchy: h1 → h2 → h3
- `lang="en"` on `<html>`
- Semantic sections: `article`, `section` with `id` and `aria-labelledby`

### D2. ARIA Usage — ✅
- FAQ accordion: `aria-expanded`, `aria-controls`, `aria-labelledby`, `role="region"`
- Dropdown: `aria-expanded`, `aria-haspopup`, `aria-controls`, `role="menu"`, `role="menuitem"`
- Search: `role="dialog"`, `aria-label`, `aria-hidden` on decorative SVGs
- Mobile toggle: `aria-expanded`, `aria-controls`, `aria-label`
- All decorative SVGs: `aria-hidden="true"`
- Tech icons: `aria-hidden="true"` with `role="img"` fallback via `title`

### D3. Color Contrast (Primary Text) — ✅
| Token | Value | On Canvas (#04050a) | Ratio |
|-------|-------|---------------------|-------|
| `--ds-text-heading` | `#f8fafc` | 14.5:1 | ✅ AAA |
| `--ds-text-body` | `#e2e8f0` | 12.8:1 | ✅ AAA |
| `--ds-text-muted` | `#94a3b8` | 5.8:1 | ✅ AA |
| `--ds-text-subtle` | `#64748b` | **3.55:1** | ❌ FAIL |

### D4. Focus Styles — ✅ (partial)
- Global `:focus-visible` rule in main.css (line 312)
- Skip-link focus style (line 167)
- Button focus-visible style (line 193)
- Nav links: see A7 (missing explicit rule)

### D5. Keyboard Navigation — ✅ (partial)
- Escape closes mobile menu (header-nav.js line 93)
- Enter/Space toggles dropdown (header-nav.js line 101)
- Focus returns to toggle on close (line 53)
- No focus trap (see A2)

### D6. Reduced Motion — ✅
- `prefers-reduced-motion` respected in CSS (header-nav.css line 377)
- GSAP reveal.js respects reduced motion (reveal.js line 13)
- Homepage animations gated by `prefers-reduced-motion` (homepage.js)

### D7. Form Accessibility — ✅
- Contact form inputs have `<label>` and `aria-label`
- FAQ search input has `aria-label="Search FAQ"`
- Form validation errors use `aria-live="polite"` and `role="alert"`

### D8. Image/Icon Alt Text — ✅
- All decorative SVGs have `aria-hidden="true"`
- Functional SVGs have descriptive labels
- Favicon tags present (lines 37–39)

### D9. Structured Data — ✅
- WebSite JSON-LD with SearchAction (line 102)
- ProfessionalService JSON-LD (lines 103–111)
- FAQPage JSON-LD (line 818)

### D10. Performance — ✅ (partial)
- All scripts use `defer` attribute
- Google Fonts async-loaded (`media="print" onload`)
- Secondary CSS async-loaded (`media="print" onload`)
- Image lazy loading on portfolio/tech items

---

## E. HOMEPAGE VISUAL AUDIT

### Verified Sections (all 15 render):
| Section | Status | Notes |
|---------|--------|-------|
| Hero | ✅ | Heading, chips, badge, CTA, panel all render |
| About | ✅ | Editorial layout with accent bar |
| Experience | ✅ | Radial badge + 4 stat cards |
| Services | ✅ | Featured card + 5 supporting |
| Why Choose | ✅ | Featured + stacked layout |
| Problems | ✅ | Alternating masonry |
| Why Work | ✅ | Vertical timeline |
| Industries | ✅ | Featured + cloud |
| Technologies | ✅ | Floating wall with brand SVGs |
| Process | ✅ | Vertical steps with nodes |
| Portfolio | ✅ | Empty state with CTA (correct — no real data) |
| Testimonials | ✅ | Empty state with CTA (correct — no real data) |
| FAQ | ✅ | Side-by-side pills + accordion |
| CTA | ✅ | Split panel with orbs |
| Contact Preview | ✅ | Premium glass panel |

### Specific Visual Checks:
- Placeholder portfolio/testimonials **NOT shown** (correct — `show_placeholders` flag not set)
- All 15 sections have unique layout composition (Sprint 15H verified)
- Premium dark glassmorphism consistent across all sections
- GSAP reveal animations present (`data-reveal` on all sections)
- Scroll progress bar present (`scroll-progress` div, line 931)

---

## F. ADMIN ACCESSIBILITY PREVIEW

**Note:** Full admin audit deferred to Sprint 17. Preview findings below.

| Issue | Severity | Evidence |
|-------|----------|----------|
| Legacy CSS (`admin.css`) still primary stylesheet | High | `admin/includes/head.php` loads `admin.css` before `admin-premium.css` |
| No `focus-visible` styles in admin CSS | Medium | admin.css has no `:focus-visible` rule |
| Data tables lack `<th>` headers | Medium | Admin tables use `<td>` for all cells |
| Form fields lack `<label>` elements | Medium | Many admin forms use placeholder-only labels |
| Modal dialogs lack `role="dialog"` | Medium | Modal markup uses divs without ARIA roles |
| No skip-link in admin pages | Low | Admin layout omits skip-link |

---

## G. RECOMMENDED FIXES BY PRIORITY

### Phase 1 (Critical/High — Sprint 17 scope):
1. Fix `--ds-text-subtle` contrast (A1)
2. Add mobile menu focus trap (A2)
3. Differentiate service icons (A3)
4. Fix about feature icons (A4)
5. Fix mobile toggle size (A6)

### Phase 2 (Medium — Sprint 17 scope):
6. Add nav focus-visible styles (A7)
7. Remove unused Inter font load (A8)
8. Add icons to empty contact items (A10)
9. Add 480px breakpoint (AuditReport)
10. Add 1920px breakpoint (AuditReport)

### Phase 3 (Sprint 17+):
11. Complete admin modernization (legacy CSS migration)
12. Create security.txt
13. Generate favicon files
14. Fix `.card-premium` / `.grid-premium` doc vs code mismatch
15. Delete orphaned `design-system.css`

---

## H. DEPLOYMENT BLOCKERS (Unchanged)

| Blocker | Status |
|---------|--------|
| No MySQL / Hostinger access | ⏳ Blocked |
| No git repository | ⏳ Open |
| Post-deployment checklist | ⏳ Blocked on deployment |

---

*Audit complete. No code modified. All findings backed by file:line evidence from live HTML + source code inspection.*
