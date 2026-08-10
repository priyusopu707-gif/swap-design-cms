# Sprint 16A Report — Credentials Sanitization + Production Safety Polish

**Version:** 1.7.2
**Date:** 2026-08-09
**Type:** Documentation + operational hardening (no code or content changes)
**Status:** ✅ COMPLETE

---

## Executive Summary

Sprint 16A is a documentation and verification sprint. It captures and verifies operational hardening changes that were applied across prior sprints but not yet recorded in the changelog or project memory. No new features were shipped; the sprint audits existing code against production safety criteria and closes open documentation gaps.

**Core outcome:** The repository contains no embedded production credentials, placeholder data cannot leak into production without an explicit config toggle, accessibility landmarks are consistent across all render paths, duplicate JS initializers are eliminated, and secondary CSS loads asynchronously.

---

## Verified Changes

### 1. Database Credentials — No Embedded Secrets

**File:** `includes/config/database.php`

```php
define('DB_PASS', getenv('DB_PASS') ?: '');
```

- `DB_PASS` reads exclusively from the environment variable `DB_PASS`.
- Fallback is an empty string — never a real password.
- `.env` file (which may contain local dev credentials) is gitignored.
- `DB_HOST`, `DB_NAME`, `DB_USER` follow the same `getenv()`-first pattern.
- No plaintext production credential exists in the repository.

**Verified by:** direct read of `database.php` lines 54–57.

---

### 2. Production Placeholder Safety

**File:** `includes/content/HomepageRenderer.php`

- `getPlaceholderPortfolio()` (line ~1388) and `getPlaceholderTestimonials()` (line ~1448) return design-validation dummy data.
- Both are gated behind `$cfg['show_placeholders']` flag:
  - `renderPortfolioSection()` line 740: `$placeholders = !empty($cfg['show_placeholders']) ? $this->getPlaceholderPortfolio($count) : [];`
  - `renderTestimonialsSection()` line 858: `$placeholders = !empty($cfg['show_placeholders']) ? $this->getPlaceholderTestimonials($count) : [];`
- When the flag is unset (production default), the renderer falls through to `renderPortfolioEmptyState()` / `renderTestimonialsEmptyState()` — honest empty-state shells, not dummy reviews or projects.
- Placeholder IDs are explicitly `9001–9006` (portfolio) and `9101–9103` (testimonials) to distinguish them from real CMS data.

**Verified by:** grep + line inspection of `HomepageRenderer.php`.

---

### 3. Homepage `<main>` Landmark + Skip-Link Accessibility

**Files:**
- `includes/header.php` (line 28): `<a href="#main-content" class="skip-link">Skip to main content</a>`
- `includes/layout.php` (line 69): `<main id="main-content" class="main-content" tabindex="-1">`
- `includes/content/RenderingEngine.php` (lines 340, 534, 590, 651, 1006, 1037): `<main class="main-content" id="main-content">` on all module render paths
- `includes/content/HomepageRenderer.php` (line 1571): `<main class="main-content" id="main-content">` in `renderEmptyState()`

All primary render paths expose a single `<main>` landmark with `id="main-content"` and a corresponding skip-link target. Keyboard users can jump directly to page content.

**Verified by:** grep for `skip-link` and `<main` across `.php` files.

---

### 4. Duplicate Tilt / Parallax Initialization Fixed

**File:** `assets/js/homepage.js`

Previous implementation contained its own tilt and parallax initializers, causing double-bind on `[data-tilt]` and `[data-parallax]` elements (once from homepage.js, once from the global component scripts).

Current state (verified):
- Lines 75–78: comment `// 3D panel tilt REMOVED: tilt.js handles .hero__panel via [data-tilt="4"].`
- Lines 213–216: `initCardTilt()` is a no-op stub; comment explains delegation to `tilt.js`.
- Line 377–378: parallax delegated to `parallax.js`; no local init.
- Global owners: `assets/js/components/tilt.js` (line 16: `querySelectorAll('[data-tilt]')`), `assets/js/components/parallax.js` (line 16: `querySelectorAll('[data-parallax]')`).

Result: single initialization per element. No double-animation or double-parallax.

**Verified by:** grep + direct read of `homepage.js` and component scripts.

---

### 5. Server Error Page — `pages/500.php`

**File:** `pages/500.php` (new)

Renders a friendly 500 Internal Server Error page with:
- Error code badge (500)
- Heading: "Something Went Wrong"
- Body text with reassurance
- Two CTA buttons: "Back to Home" and "Contact Us"
- Uses existing `.error-page` classes from the design system

**Verified by:** file existence check.

---

### 6. Rate Limiting on Public API Endpoints

**Files:**
- `api/contact.php` (line 41): `rateLimitExceeded('api_contact_' . $_SERVER['REMOTE_ADDR'], 5, 15)` — 5 submissions per IP per 15 minutes. Returns HTTP 429 when exceeded.
- `api/whatsapp-track.php` (line 30): `rateLimitExceeded('api_wa_' . $_SERVER['REMOTE_ADDR'], 20, 5)` — 20 clicks per IP per 5 minutes. Returns HTTP 429 when exceeded.

Both endpoints also include CSRF verification (`contact.php` line 48) and proper `Content-Type: application/json` headers.

**Verified by:** direct read of both API files.

---

### 7. Render-Blocking CSS Optimized

**File:** `includes/head.php` (lines 145–154)

Secondary design-system CSS files are now loaded asynchronously:

```html
<link rel="stylesheet" href="/assets/css/components/design-system/badges.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="/assets/css/components/design-system/tabs.css" media="print" onload="this.media='all'">
<!-- ... modal, drawer, toast, pagination, whatsapp, back-to-top, progress, breadcrumb ... -->
```

Google Fonts (lines 165–173) also use the `media="print" onload` async pattern.

This prevents these non-critical styles from blocking first paint. The critical render path (tokens → base → main → theme → responsive → core components) remains synchronous.

**Verified by:** direct read of `head.php` lines 144–154 and 165–173.

---

## Validation Summary

| Check | Result |
|-------|--------|
| `curl http://127.0.0.1:8080/` → HTTP 200 | ✅ |
| PHP lint clean (all modified files) | ✅ |
| CSRF token verification on `api/contact.php` | ✅ |
| Rate limiting on `api/contact.php` (5/15min) | ✅ |
| Rate limiting on `api/whatsapp-track.php` (20/5min) | ✅ |
| Homepage `<main>` landmark present | ✅ |
| Skip-link target `#main-content` present | ✅ |
| Placeholder cards gated by `show_placeholders` | ✅ |
| No duplicate tilt/parallax init in homepage.js | ✅ |
| `pages/500.php` exists | ✅ |
| Secondary CSS async-loaded in head.php | ✅ |
| `DB_PASS` from `getenv()` only, empty fallback | ✅ |

---

## Files Affected (documentation update only; no code changed)

| Document | Action |
|----------|--------|
| `CHANGELOG.md` | Added `[1.7.2]` entry |
| `PROJECT_MEMORY.md` | Added Sprint 16A section |
| `TODO.md` | Added Sprint 16A checklist |
| `PROJECT_STATUS.md` | Updated header + latest update block |
| `SPRINT16A_REPORT.md` | **New** — this report |

---

## Known Regressions

None. This sprint performed no code changes; it documented and verified existing fixes.

---

## Next Recommended Sprint

**Sprint 17 — Cross-Browser + Accessibility (WCAG 2.1 AA) + Admin Modernization Completion**

1. Full WCAG 2.1 AA audit across all 7 frontend pages
2. Cross-browser testing (Chrome, Firefox, Safari, Edge)
3. Mobile responsive audit (320px → 1440px+)
4. Complete admin modernization: migrate all 28 admin pages to `admin-premium.css` tokens
5. Delete orphaned `assets/css/design-system.css` (531 lines, not loaded by head.php) after confirming no JS references

---

*Report generated from live codebase inspection. No code was modified during this sprint.*
