# Swap Design CMS — Full Audit Report

**Date:** 2026-08-09  
**Environment:** Development (PHP 8.3.32 built-in server at http://127.0.0.1:8080/)  
**Method:** Live URL inspection + static code analysis. No files modified.  

---

## A. Current Actual Project State

| Metric | Value |
|--------|-------|
| PHP files | 123 |
| JS files | 19 (frontend) |
| CSS files | 52 (frontend) |
| Homepage HTML size | 91 KB |
| Homepage sections | 15 (hero → contact-preview) |
| Admin pages | 29 |
| Admin AJAX handlers | 9 |
| DB tables referenced | 38+ |
| Git repo | **NONE** — no version control |
| Latest version | 1.7.1 (Sprint 15 Surgical) |
| DB status | **Down** on Aug 8 (connection refused), live on Aug 9 |
| Active sprints | Sprint 16 marked complete, original Sprint 15 pending |
| Sprint numbering | Gaps: 15B, 15F, 15G undocumented |

**Homepage rendering:** All 15 sections render. Content loads from seeded defaults. Portfolio + testimonials show fabricated placeholder data (show_placeholders = '1'). Layout composition verified against LAYOUT_COMPOSITION_REPORT — all layout classes present.

---

## B. Completed Modules

| Module | Status | Evidence |
|--------|--------|----------|
| Homepage (15 sections) | ✅ Complete | All sections render; layout composition verified |
| Homepage glassmorphism/3D/GSAP | ✅ Complete | Tilt, parallax, particles, reveal, magnetic, counter |
| Design System tokens | ✅ Complete | design-tokens.css: 241 lines, all tokens defined |
| Admin panel (29 pages) | ✅ Complete | Login, CRUD, homepage editor, media, SEO |
| Auth system | ✅ Complete | Password hash, session regen, CSRF, remember-me |
| Routing | ✅ Complete | DynamicRouter: 16+ route patterns, all tested |
| Security headers | ✅ Complete | CSP, X-Frame, X-Content-Type, SameSite cookies |
| Portfolio CRUD | ✅ Complete | Admin editor + frontend renderer |
| Blog system | ✅ Complete | Posts, categories, tags, revisions |
| Services management | ✅ Complete | Admin editor + frontend renderer |
| Search | ✅ Complete | AJAX suggest, popular, category filtering |
| Email system | ✅ Complete | Templates, log, WhatsApp tracking |
| FAQ system | ✅ Complete | Category filter, accordion, search |
| CTA section | ✅ Complete | WhatsApp + primary CTA + gradient orbs |
| Contact form | ✅ Complete | CSRF + honeypot + rate limit + validation |
| Page cache | ✅ Complete | PageCache with TTL, conditional ETag |
| GSAP animations | ✅ Complete | Reveal, tilt, magnetic, parallax, counter, accordion |
| Scroll progress bar | ✅ Complete | Fixed top gradient bar |
| Back to top button | ✅ Complete | Glass circular, brand gradient |

---

## C. Partial Modules

| Module | Issue | Severity |
|--------|-------|----------|
| Hero heading | DB content doesn't match approved seed copy | Medium |
| Services icons | All 6 render identical static 4-square SVG; per-service icon field ignored | Medium |
| FAQ category filter | Only "General" category exists; filter is effectively single-pill | Low |
| About feature icons | .about__feature-icon--check renders as empty colored circle (no glyph) | Medium |
| Inter font loading | header.php:73 loads Inter (unused); wasted bandwidth | Low |
| Design system .card-premium/.grid-premium | Documented in DESIGN_SYSTEM.md but NOT in any CSS file | Medium |
| ARCHITECTURE.md | Describes pre-Sprint-3 loader.php→renderLayout pipeline (stale) | Medium |

---

## D. Broken Modules

| Module | Issue | File:Line | Severity |
|--------|-------|-----------|----------|
| Homepage `<main>` landmark | Missing; skip link `#main-content` target broken | RenderingEngine.php:320-347 | **Critical** |
| Placeholder content leaking | Portfolio (5 demo) + testimonials (3 demo) shown as real content | HomepageManager.php:192, 205 | **Critical** |
| Double tilt binding | tilt.js + homepage.js both write transform on same cards → flicker | homepage.js:276, tilt.js:16 | **Critical** |
| Double parallax binding | parallax.js + homepage.js both write transform on hero__visual-wrap | homepage.js:88, parallax.js:16 | **Critical** |
| Database password exposed | Real password in plaintext in source file | database.php:16 | **Critical** |
| APP_ENV forced to dev | router.php sets APP_ENV=development on every request | router.php:12-13 | **Critical** |
| Pages/500.php missing | No custom error page for 500 errors | pages/ directory | **High** |
| Favicon files missing | assets/images/favicon/ only has .gitkeep | assets/images/favicon/.gitkeep | **High** |

---

## E. Pending Modules

| Module | Status | Notes |
|--------|--------|-------|
| Original Sprint 15 (cross-browser + WCAG) | **PENDING** | Not started |
| security.txt | **NOT CREATED** | Required for responsible disclosure |
| Font preloading (WOFF2) | **NOT IMPLEMENTED** | Missed performance optimization |
| SMTP email (replace mail()) | **NOT DONE** | api/contact.php uses PHP mail() |
| File upload MIME pre-validation | **NOT DONE** | Extension check only, no finfo before move |

---

## F. Homepage Visual Score: 8/10

**Strengths:**
- Premium dark creative aesthetic with glassmorphism throughout
- 15 unique section layouts (no generic grid repetition)
- GSAP animations (reveal, tilt, magnetic, parallax, counter)
- 3D effects on hero panel, problem cards, process steps
- Brand colors consistently applied (#ff3e3e red, #ff763e orange)
- Particles, ambient blobs, scroll indicator, floating tech chips
- Mobile menu with overlay, keyboard support, aria attributes
- Hero branded glass panel fallback when no image

**Weaknesses (-2 points):**
- `-1`: Fabricated portfolio/testimonial data visible (placeholder content leak)
- `-0.5`: All service icons identical static SVG (no differentiation)
- `-0.5`: About feature icons render as empty circles

---

## G. Design System Consistency Score: 8.5/10

**Strengths:**
- All brand colors verified correct (#ff3e3e, #ff763e, #04050a, #070913)
- Typography: Montserrat headings, Plus Jakarta Sans body, JetBrains Mono — all loaded
- Glass system fully implemented (backdrop-filter, borders, hover states)
- Button system complete (primary, cta, outline, whatsapp)
- Spacing scale: 4px base, 13 steps (4→120)
- Animation utilities all present (data-reveal, tilt-card, magnetic, float)
- Icon system: inline SVG approach working

**Weaknesses (-1.5 points):**
- `-0.5`: `--ds-glass-border` docs say `rgba(255,255,255,0.10)` but actual is `rgba(255,62,62,0.10)` (red-tinted)
- `-0.5`: DESIGN_SYSTEM.md says "Inter (Display/Body)" fonts but actual is Montserrat+JakartaSans+JetBrainsMono
- `-0.3`: `.card-premium` / `.grid-premium` documented but NOT implemented
- `-0.2`: Several unused tokens defined but never referenced

---

## H. Functionality Score: 7.5/10

**Strengths:**
- All routes tested: homepage/about/services/portfolio/contact/blog/search → 200 OK
- 404 routing works correctly → HTTP 404
- Admin login with CSRF + session regeneration
- Contact form: CSRF + honeypot + rate limiting + server validation
- Auth rate limiting: 5 attempts / 15 min (Auth.php)
- Prepared statements throughout (no raw SQL)
- Page cache system functional with TTL
- GSAP loaded asynchronously, not render-blocking

**Weaknesses (-2.5 points):**
- `-1.0`: No rate limiting on api/contact.php (spam risk)
- `-0.5`: No CSRF on api/whatsapp-track.php
- `-0.5`: Missing pages/500.php (unhandled exceptions show raw errors)
- `-0.5`: api/contact.php uses PHP mail() without SMTP

---

## I. Responsive Score: 8/10

**Strengths:**
- Breakpoints: 1024, 900, 720, 640, 560 covered
- All grids collapse properly (hero 2col→1col, portfolio 3→2→1, etc.)
- Mobile menu: toggle, overlay, slide-in panel, 100dvh height, keyboard escape
- Fluid typography via clamp() (37+ instances)
- prefers-reduced-motion: global CSS kill switch + JS gates

**Weaknesses (-2 points):**
- `-0.5`: No 480px breakpoint (very small phones untested)
- `-0.5`: No 1920px (3xl) breakpoint despite config
- `-0.5`: Social link tap targets 42px (2px under WCAG 44px min)
- `-0.5`: Mobile toggle 40px (2px under WCAG 44px min)

---

## J. Accessibility Score: 6/10

**Strengths:**
- Skip-to-content link exists (header.php:28)
- 1 h1, 14 h2 — correct heading hierarchy
- 140+ aria-hidden, 30+ aria-labelledby, 15+ aria-label, 7 aria-expanded
- Contact form: proper labels, aria-live, role="alert"
- FAQ: aria-controls + aria-labelledby paired correctly
- prefers-reduced-motion handled in CSS + JS

**Weaknesses (-4 points):**
- `-2.0`: **No `<main>` landmark on homepage** — skip link target broken; screen readers can't identify main content region
- `-1.0`: No focus-visible on navigation links (header-nav.css)
- `-0.5`: `--ds-text-subtle` (#64748b) fails AA contrast on dark bg (3.55:1 < 4.5:1)
- `-0.5`: No focus trap in mobile menu overlay

---

## K. Performance Score: 6/10

**Strengths:**
- GSAP loaded dynamically via createElement (not render-blocking)
- All 19 JS files use `defer` attribute
- Google Fonts loaded via `media="print" onload` (non-blocking)
- `display=swap` on font CSS
- Page cache system functional
- No duplicate JS loads
- Lazy loading configured for images

**Weaknesses (-4 points):**
- `-2.0`: **34 CSS files render-blocking on every page** — massive parse/render cost
- `-1.0**: Font loading DUPLICATE: head.php:163 + header.php:73 (Inter) + noscript fallback — 3 font CSS requests
- `-0.5**: No font preloading (WOFF2 preload for critical fonts)
- `-0.5**: Homepage HTML 91KB — could be reduced with async rendering

---

## L. Critical Bugs

| # | Bug | File:Line | Impact | Severity |
|---|-----|-----------|--------|----------|
| 1 | Database password in plaintext | database.php:16 | Security: credential exposure | **Critical** |
| 2 | No `<main>` landmark on homepage | RenderingEngine.php:320-347 | A11y: screen readers broken | **Critical** |
| 3 | Portfolio placeholders shown as real | HomepageManager.php:192 | Misleading content to users | **Critical** |
| 4 | Testimonial placeholders shown as real | HomepageManager.php:205 | Fake reviews → reputation risk | **Critical** |
| 5 | Double tilt JS binding | homepage.js:276 + tilt.js:16 | Flickering card transforms | **Critical** |
| 6 | Double parallax JS binding | homepage.js:88 + parallax.js:16 | Hero jitter on scroll+mouse | **Critical** |
| 7 | APP_ENV=development forced | router.php:12-13 | Dev errors exposed in prod | **Critical** |
| 8 | No `<main>` means skip link target missing | header.php:28 | Keyboard nav broken on homepage | **Critical** |

---

## M. Non-Critical Issues

| # | Issue | File:Line | Severity |
|---|-------|-----------|----------|
| 1 | 34 render-blocking CSS files on homepage | head.php:116-159 | High |
| 2 | No rate limiting on api/contact.php | api/contact.php | High |
| 3 | No CSRF on api/whatsapp-track.php | api/whatsapp-track.php:31 | High |
| 4 | Missing pages/500.php | pages/ directory | High |
| 5 | No security.txt | Root directory | High |
| 6 | Favicon files missing (404) | assets/images/favicon/ | High |
| 7 | Service icons all identical static SVG | HomepageRenderer.php:358 | Medium |
| 8 | About feature icons empty circles | homepage.css:317-329 | Medium |
| 9 | --ds-glass-border doc/code mismatch | design-tokens.css:45 | Medium |
| 10 | DESIGN_SYSTEM.md says "Inter" fonts | DESIGN_SYSTEM.md:13 | Medium |
| 11 | .card-premium/.grid-premium not implemented | DESIGN_SYSTEM.md:5c | Medium |
| 12 | No 480px breakpoint | homepage.css | Medium |
| 13 | CSP allows unsafe-inline | security.php:107-108 | Medium |
| 14 | HomepageRenderer doc says "11 sections" | HomepageRenderer.php:5 | Medium |
| 15 | HOMEPAGE.md doesn't exist | N/A (referenced everywhere) | Medium |
| 16 | ARCHITECTURE.md describes stale pipeline | ARCHITECTURE.md:19-29 | Medium |
| 17 | Touch targets 42px (2px under WCAG) | footer.css:123-124 | Medium |
| 18 | --ds-text-subtle fails AA contrast | design-tokens.css:54 | Medium |
| 19 | No focus-visible on nav links | header-nav.css | Medium |
| 20 | Inter font loaded but unused | header.php:73 | Low |
| 21 | No font preloading (WOFF2) | head.php | Low |
| 22 | api/contact.php uses PHP mail() | api/contact.php:127 | Low |
| 23 | File upload no pre-validation | LeadManager.php:262-265 | Low |
| 24 | No focus trap in mobile menu | header-nav.js | Low |
| 25 | No 1920px (3xl) breakpoint | responsive.css | Low |
| 26 | HomepageRenderer section numbering stale | HomepageRenderer.php:98-1467 | Low |
| 27 | Empty cache directories | cache/output, cache/queries | Low |

---

## N. Recommended Development Order

### Phase 1: Critical Security & A11y (Immediate)

1. **Move DB password to .env** — Create `.env.example`, gitignore `.env`, load via `$_ENV` or `getenv()` in database.php. Rotate credential.
2. **Add `<main id="main-content">` wrapper on homepage** — RenderingEngine.php renderHomepage() must output `<main>` around section content.
3. **Disable placeholder defaults** — HomepageManager.php:192 → `'show_placeholders' => '0'`; HomepageManager.php:205 → `'show_placeholders' => '0'`.
4. **Remove router.php dev override** — Delete or comment `putenv('APP_ENV=development')` lines.
5. **Create pages/500.php** — Friendly error page. Uncomment .htaccess ErrorDocument line.

### Phase 2: High-Severity Fixes (This Week)

6. **Eliminate double tilt binding** — Remove homepage.js `initCardTilt()` entirely (tilt.js handles `[data-tilt]` globally).
7. **Eliminate double parallax** — Remove homepage.js hero mouse parallax OR remove data-parallax from hero__visual-wrap. Choose one handler.
8. **Add rate limiting to api/contact.php** — Use existing pattern from ajax/contact.php (3/15min).
9. **Add CSRF to api/whatsapp-track.php** — Verify token from request header or body.
10. **Consolidate CSS** — Merge design-system component files into 1-2 bundles to reduce 34 render-blocking CSS files.
11. **Remove Inter font load** — header.php:71-73; only Montserrat+JakartaSans+JetBrainsMono needed.
12. **Create security.txt** — /.well-known/security.txt with disclosure policy.

### Phase 3: Medium-Severity Polish (Sprint 17)

13. **Fix service icons** — Use per-service icon field instead of static SVG in HomepageRenderer.php:358.
14. **Add about feature icon glyphs** — ::before content on .about__feature-icon--check.
15. **Fix --ds-glass-border doc** — Update DESIGN_SYSTEM.md to match actual value.
16. **Fix DESIGN_SYSTEM.md font claim** — Change "Inter" to "Montserrat + Plus Jakarta Sans".
17. **Remove .card-premium/.grid-premium doc** — Or implement them.
18. **Add 480px breakpoint** — For very small phones.
19. **Fix nav focus-visible** — Add :focus-visible rule to .main-nav__link.
20. **Fix --ds-text-subtle contrast** — Use #94a3b8 (5.55:1) instead of #64748b (3.55:1).
21. **Fix touch targets** — Social links to 48px, mobile toggle to 48px.
22. **Add focus trap to mobile menu** — Trap tab within .main-nav--open when active.
23. **Update HomepageRenderer docblock** — "15 sections" not "11".
24. **Create HOMEPAGE.md or remove references** — Single source of truth document missing.
25. **Update ARCHITECTURE.md** — Sections 1, 4, 5, 6 all stale.
26. **Replace PHP mail() with SMTP** — Use PHPMailer in api/contact.php.
27. **Add file upload pre-validation** — finfo_open() check before move_uploaded_file.
28. **Add font preloading** — <link rel="preload" as="font"> for critical Montserrat weights.
29. **Add 1920px breakpoint** — Match site.php config.
30. **Fix CHANGELOG order** — Move [1.5.6] entry to correct position.
31. **Fix PROJECT_STATUS.md "Latest Update"** — Reference v1.7.1, not v1.7.0.

### Phase 4: Low-Severity Cleanup

32. **Create favicon files** — Generate 16/32/180px PNGs.
33. **Remove unused tokens** — --ds-dark-surface-2, --ds-shadow-large, --ds-text-on-accent, --ds-gradient-accent/dark/glass, --dur-slower, --ease-in-out.
34. **Update section counts** — README.md, ROUTES.md, DATABASE_SCHEMA.md table 24.
35. **Fix sprint numbering gaps** — Document 15B/15F/15G or remove references.
36. **Fix homepage.css duplicate declarations** — section__heading declared 3+ times.
37. **Set up git repository** — Currently no version control at all.
38. **Cache warm-up** — Ensure cache/pages/ and cache/queries/ are actively used.

---

*Audit complete. All findings backed by file:line evidence. No code was modified.*
