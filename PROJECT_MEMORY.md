# Swap Design CMS -- Project Memory

## Overview

Swap Design is a branded CMS for a remote freelance design studio (8+ years experience). Built from scratch with PHP 8+, MySQL, HTML5, CSS3, and vanilla JavaScript. No frameworks, no build tools. Target deployment: Hostinger Premium Shared Hosting.

## Tech Stack

- **Backend:** PHP 8+, MySQL 5.7+/MariaDB 10.3+, Apache 2.4 with mod_rewrite
- **Frontend:** HTML5, CSS3 (custom properties design system), vanilla JavaScript (ES6+)
- **No:** WordPress, Laravel, React, Vue, Angular, Bootstrap, Tailwind, jQuery, Node.js, Composer

## Architecture Decisions

| Decision | Rationale |
|----------|-----------|
| Procedural + OOP hybrid | Procedural for template rendering, OOP for core services (Session, Database, Auth) |
| File-based sessions | Hostinger shared hosting; no Redis/Memcached available |
| bcrypt password hashing | PHP 8+ native `password_hash(PASSWORD_BCRYPT)` with cost 12 |
| PDO singleton with prepared statements | Safe query pattern; single connection per request |
| Split-token remember-me | selector (plaintext DB lookup) + token_hash (SHA-256 hashed verifier) |
| Front-controller routing | All requests through index.php via .htaccess mod_rewrite |
| CSS custom properties design tokens | Enables future Theme Settings admin UI |
| No PHP autoloader/composer | Explicit require_once for clarity on shared hosting |

## Directory Structure

```
swap-design/
├── admin/                      # CMS admin panel (28 pages, 8 AJAX handlers)
│   ├── index.php               # Dashboard
│   ├── login.php               # Login page
│   ├── logout.php              # Logout handler
│   ├── pages.php, content-types.php, entries.php, sections.php, layouts.php
│   ├── services.php, services-edit.php, portfolio.php, portfolio-edit.php
│   ├── homepage.php, about.php, contacts.php, leads.php
│   ├── email-settings.php, email-templates.php
│   ├── blog.php, search.php, navigation.php, footer.php, blocks.php, media.php
│   ├── settings.php, theme.php, whatsapp.php, users.php
│   ├── ajax/                   # about, blog, contacts, homepage, leads, portfolio, search, services
│   ├── includes/               # init.php (bootstrap + auth guard), header, footer, sidebar, topbar
│   └── assets/                 # admin CSS/JS
├── includes/                   # Shared includes (59 PHP files)
│   ├── auth/                   # Auth.php (authentication class)
│   ├── blocks/                 # BlockEngine.php, ComponentLoader.php
│   ├── components/             # Frontend components (header, footer, nav, cta, contact-form, search)
│   ├── config/                 # site.php, database.php, environment.php, error-handler.php
│   ├── content/                # 22 classes: managers/renderers/router (About, Blog, Contact, Email, Lead)
│   ├── functions/              # helpers, sanitize, seo, security, logger
│   ├── integrations/           # WhatsAppManager.php
│   ├── media/                  # FileUploader.php, ImageOptimizer.php, MediaLibrary.php
│   ├── navigation/             # NavigationManager.php
│   ├── search/                 # SearchIndexer.php, SearchManager.php, SearchRenderer.php
│   ├── seo/                    # SitemapGenerator.php, RobotsManager.php, SEOAuditor.php
│   ├── settings/               # SettingsManager.php
│   ├── themes/                 # ThemeEngine.php
│   ├── Session.php, Database.php, loader.php, layout.php, head.php, breadcrumb.php, scripts.php
├── pages/                      # Frontend page templates
├── api/                        # API endpoints (contact, whatsapp-track)
├── ajax/                       # Frontend AJAX (contact, search)
├── assets/                     # Frontend assets (20 CSS, 15 JS)
│   ├── css/
│   ├── js/
│   ├── images/
│   └── fonts/
├── database/
│   └── schema.sql              # Database schema (58 tables, all CREATE TABLE IF NOT EXISTS)
├── uploads/                    # User uploads (gitignored)
├── .htaccess                   # Apache config (rewrite, security, caching)
├── .gitignore
├── index.php                   # Frontend entry point
├── PROJECT_MEMORY.md
├── CHANGELOG.md
├── TODO.md
└── ARCHITECTURE.md
```

## Completed (Pre-Sprint 1)

- Project scaffold with full directory tree
- Global site config (`site.php`) -- brand, navigation, services, design tokens, social, forms, features
- Database config (`database.php`) -- PDO connection with env vars
- Helper functions (`helpers.php`) -- esc(), csrfToken(), redirect(), etc.
- Input sanitization (`sanitize.php`) -- validation + sanitization
- SEO functions (`seo.php`) -- meta, structured data (LocalBusiness, BreadcrumbList)
- Frontend layout engine:
  - `head.php` -- full <head> with SEO, OG, Twitter Card, CSS, GA
  - `header.php` -- doctype + head + skip-link
  - `navigation.php` -- sticky nav with Services dropdown, mobile panel, ARIA
  - `breadcrumb.php` -- dynamic trail with Schema.org microdata
  - `cta.php` -- call-to-action component
  - `footer.php` -- 4-column footer with contact + social
  - `scripts.php` -- deferred JS loader
  - `loader.php` -- route-to-context resolver
  - `layout.php` -- renderLayout() orchestrator
- Frontend CSS components: main.css (design tokens), responsive.css, header-nav.css, footer.css, layout.css, breadcrumb.css, cta.css, contact-form.css
- Frontend JS: main.js (smooth scroll, animations, focus mgmt), header-nav.js, animations.js, helpers.js
- Contact form (PHP + JS + API endpoint)
- 404 page template
- Database schema: contact_messages, pages, portfolio_items, site_settings tables
- Apache .htaccess: URL rewriting, HTTPS force, caching, gzip, security blocks

## Sprint 1 -- Core CMS Framework

### Delivered
- [x] Environment configuration (`environment.php`)
- [x] Error/exception handler (`error-handler.php`)
- [x] Session management class (`Session.php`)
- [x] Database class OOP wrapper (`Database.php`)
- [x] Logging system (`logger.php`)
- [x] Security helpers (`security.php`)
- [x] Authentication class (`auth/Auth.php`) -- login, logout, rate limiting, remember-me
- [x] Admin bootstrap + auth guard (`admin/includes/init.php`)
- [x] Admin login page + logout handler
- [x] Admin dashboard layout with sidebar + topbar
- [x] Admin CSS + JS
- [x] Updated database schema with `users`, `login_attempts`, `remember_tokens` tables
- [x] Documentation: PROJECT_MEMORY.md, CHANGELOG.md, TODO.md, ARCHITECTURE.md

## Sprint 2 -- Core Classes + Admin Pages

### Delivered
- [x] SettingsManager class (`includes/settings/SettingsManager.php`) -- key-value CRUD with JSON auto-handling
- [x] ThemeEngine class (`includes/themes/ThemeEngine.php`) -- reads theme.* keys, generates `:root` CSS
- [x] NavigationManager class (`includes/navigation/NavigationManager.php`) -- menu CRUD with parent/child tree
- [x] BlockEngine class (`includes/blocks/BlockEngine.php`) -- 15 block types, CRUD + duplicate + usage tracking
- [x] ComponentLoader class (`includes/blocks/ComponentLoader.php`) -- component registry, per-type renderers
- [x] FileUploader class (`includes/media/FileUploader.php`) -- secure upload, hash filenames, SVG sanitization
- [x] ImageOptimizer class (`includes/media/ImageOptimizer.php`) -- 5 sizes via GD, WebP, dominant color
- [x] MediaLibrary class (`includes/media/MediaLibrary.php`) -- upload orchestration, CRUD, soft-delete, folders
- [x] Theme Settings admin page (`admin/theme.php`) -- color pickers, typography, layout, regenerates CSS
- [x] Settings admin page (`admin/settings.php`) -- tabbed Business Profile + Website Settings
- [x] Navigation Manager admin page (`admin/navigation.php`) -- tree view, add/edit/delete/reorder
- [x] Footer Manager admin page (`admin/footer.php`) -- grouped links CRUD via modal, copyright text
- [x] Global Blocks admin page (`admin/blocks.php`) -- card grid, search/filter, create/edit/duplicate/delete
- [x] Media Library admin page (`admin/media.php`) -- drag-and-drop upload, folders, metadata modal
- [x] Admin CSS extended -- tabs, modals, form grids, color pickers, toggles, dropzones, media cards
- [x] Helper functions: `escJs()`, `sizeFormat()`
- [x] Database schema: navigation_menu, footer_links, footer_settings, global_blocks, global_block_usage, media_library, media_folders, global_components

## Sprint 3 -- Dynamic Content Engine

### Delivered
- [x] ContentTypeEngine (`includes/content/ContentTypeEngine.php`) -- custom type definitions with JSON field schemas
- [x] ContentEntryManager (`includes/content/ContentEntryManager.php`) -- CRUD for entries of any type
- [x] SectionManager (`includes/content/SectionManager.php`) -- reusable sections, page assignment, reorder
- [x] LayoutManager (`includes/content/LayoutManager.php`) -- structural templates with named zones
- [x] SlugManager (`includes/content/SlugManager.php`) -- generation, uniqueness, 301 redirect tracking
- [x] DynamicRouter (`includes/content/DynamicRouter.php`) -- DB-driven URL -> page resolution
- [x] RenderingEngine (`includes/content/RenderingEngine.php`) -- URL -> page -> layout -> sections -> HTML pipeline
- [x] PageManager (`includes/content/PageManager.php`) -- page CRUD + layout assignment + section management
- [x] HomepageManager / HomepageRenderer (`includes/content/`) -- 11-section homepage
- [x] ServiceManager / ServiceRenderer, PortfolioManager / PortfolioRenderer (`includes/content/`)
- [x] Admin pages: pages.php, content-types.php, entries.php, sections.php, layouts.php
- [x] index.php updated to use DynamicRouter + RenderingEngine; loader.php kept as compatibility wrapper
- [x] Database schema: content_types, content_entries, sections, layouts, page_sections, url_redirects, pages modified, homepage_sections, services + sub-tables, portfolio + sub-tables

## Sprint 4 -- Frontend Integration [COMPLETE]

### Delivered
- [x] DB-driven navigation: `includes/components/navigation.php` reads from `NavigationManager` (falls back to site.php config)
- [x] DB-driven footer: `includes/components/footer.php` reads from `footer_links` + `SettingsManager` (falls back to site.php config)
- [x] Theme CSS integration: `includes/head.php` loads `theme-generated.css` between main.css and responsive.css
- [x] Content entry card CSS: responsive grid (1-2-3 columns), card hover effects, single entry layout
- [x] Dynamic list CSS: card grid for query-based listings
- [x] Layout row CSS: two-column content + sidebar for zone-based layouts
- [x] Bug fix: ROOT_PATH now points to project root (`dirname(__DIR__, 2)`)
- [x] Bug fix: RenderingEngine require paths use correct component paths
- [x] Bug fix: ThemeEngine output path uses ROOT_PATH

## About Module [COMPLETE]

- [x] AboutManager: 12-section CRUD (hero, personal_intro, my_story, experience, core_services, working_process, why_work_with_me, skills, tools, testimonials, faq, final_cta) with revision history
- [x] AboutRenderer: Full rendering with semantic HTML, Person JSON-LD, dynamic service/testimonial loading
- [x] Admin: about.php with drag-drop reorder, toggles, inline editing, auto-save, revisions
- [x] AJAX: about.php handler for all CRUD + revision operations
- [x] Frontend: about.css (400+ lines, 12 sections) + about.js (FAQ, counters, animations)
- [x] Routing: DynamicRouter /about route, RenderingEngine renderAboutPage method
- [x] Database: about_sections + about_revisions tables

## Contact & Lead Management Module [COMPLETE]

- [x] ContactManager: 6-section CRUD (hero, contact_info, contact_form, whatsapp_cta, faq, final_cta)
- [x] ContactRenderer: Full rendering with ContactPage schema, WhatsAppManager integration
- [x] LeadManager: CRUD, 7-status workflow (new/contacted/follow_up/proposal_sent/won/lost/archived), search/filter, pagination, CSV export, notes, delete cascading
- [x] EmailManager: SMTP config, template engine, admin/user emails, email logging, raw socket SMTP with TLS/SSL
- [x] Admin: contacts.php (6-section editor), leads.php (dashboard + detail)
- [x] AJAX: admin/ajax/contacts.php + admin/ajax/leads.php (5 endpoints) + public ajax/contact.php (form handler)
- [x] Admin: email-settings.php (SMTP config UI + test email), email-templates.php (template editor)
- [x] Spam protection: CSRF token, honeypot field, IP rate limiting (3/15min), server-side validation
- [x] Frontend: contact.css (350+ lines, 6 sections) + contact.js (FAQ, validation, AJAX submit)
- [x] Routing: DynamicRouter /contact route, RenderingEngine renderContactPage
- [x] Database: contact_sections, leads, lead_notes, email_templates, email_log, whatsapp_clicks
- [x] Email templates: admin_notification + user_confirmation seeded in schema.sql

## Blog & Knowledge Base Module [COMPLETE]

- [x] BlogManager: CRUD, 4-status workflow, taxonomy (categories/tags), revision history, related content, reading time calc
- [x] BlogRenderer: Listing grid, single post (TOC, share, author, prev/next, related), category/tag archives, Article schema
- [x] Admin: blog.php (dual-mode list + editor), rich toolbar, auto-save, SEO panel, revisions
- [x] AJAX: admin/ajax/blog.php (10 actions)
- [x] Frontend: blog.css (400+ lines) + blog.js (progress bar, TOC spy, scroll anchors)
- [x] Routing: 5 blog routes in DynamicRouter, 4 render methods in RenderingEngine
- [x] Database: blog_posts, blog_categories, blog_tags, blog_post_categories, blog_post_tags, blog_revisions, blog_relationships
- [x] Note: Content blocks (callout, code, video-embed) use contenteditable HTML with CSS class styling on frontend

## SEO Module [COMPLETE]

- [x] includes/seo/SitemapGenerator: writes sitemap.xml + sitemap-blog/services/portfolio/images.xml from live DB tables; auto-regenerates on content changes via sitemapGenerator() helper hook in managers (mirrors SearchIndexer pattern)
- [x] includes/seo/RobotsManager: robots.txt generator respecting noindex settings
- [x] includes/seo/SEOAuditor: SEO audit analysis service (admin UI pending)
- [x] seo.php helpers: getOrganizationSchema, getWebsiteSchema (+SearchAction), getWebPageSchema, getCollectionPageSchema, getFaqPageSchema, getBaseSchemas (dedup graph)
- [x] head.php: absolute canonical/og:image URLs, og:image:alt/width/height, og:locale:alternate, twitter:url, base JSON-LD graph emission
- [x] RenderingEngine: CollectionPage schema on archives, WebPage+schema for CMS pages, FAQPage for service/contact, noindex for search/404/empty
- [x] breadcrumb.php: supports $pageBreadcrumb global + label keys; breadcrumb trail + BreadcrumbList schema wired into all module pages

## Production Deployment Preparation [COMPLETE]

- [x] schema.sql: removed MariaDB-only `ADD COLUMN/INDEX IF NOT EXISTS` (breaks MySQL 5.7+/MariaDB 10.3 import); schema now imports cleanly on documented minimums
- [x] README Step 5: documented `DB_CHARSET` constant required by Database.php
- [x] README Step 6: corrected config keys -- site.php returns an object (`brand.name`, `urls.base`, `brand.email`, `forms.contactRecipient`), not SITE_NAME/SITE_URL/ADMIN_EMAIL constants; SITE_URL auto-derived from APP_URL
- [x] README Step 7: fixed admin INSERT (non-existent `username` column -> `email` + `name`)
- [x] .htaccess: Apache 2.4 `Require all denied` (with legacy fallback) + `Options -Indexes`
- [x] robots.txt / sitemap.xml: placeholder `swapdesign.example.com` -> `https://swapdesign.com` (matches site.php urls.base); both auto-generated by SEO module
- [x] Verified: no MySQL available in env -- DB import/rendering tests blocked until Hostinger access provided

## Version 1.0 Release Readiness [DONE]

- [x] Full `php -l` sweep: all 111 PHP files pass (no errors)
- [x] Schema portability: all 58 `CREATE TABLE` statements now `IF NOT EXISTS`; MariaDB-only `ADD COLUMN/INDEX IF NOT EXISTS` removed earlier; portable `MODIFY COLUMN` ALTERs only
- [x] Documentation reconciliation (code = source of truth for counts):
  - DATABASE_SCHEMA.md now documents all 58 tables (18 added) + updated ER diagram + corrected search-module column definitions
  - ROUTES.md now lists all 28 admin pages + 8 admin AJAX handlers + frontend AJAX/API endpoints + dual contact-form path note
  - README.md + PROJECT_ANALYSIS.md counts updated (111 PHP, 58 tables, 28 pages, 8 AJAX, 20 CSS/15 JS)
  - TODO.md: Sprints 1-6 complete, Sprint 7 partially (SEO audit UI + per-page SEO tab pending), Sprint 8 release readiness
- [x] CHANGELOG 1.0.0 entry added; admin sidebar shows "CMS v1.0" marker
- [ ] Live DB import + rendering smoke test (blocked: no MySQL in dev env; needs Hostinger access)
- [ ] Post-deployment checklist (README): clear cache, submit sitemap, test WhatsApp/contact forms

## Known Remaining Gaps (non-blocking for v1.0)

- SEO audit dashboard admin UI (SEOAuditor service exists)
- Per-page SEO meta tab in admin
- Content seeder script / default content bundle
- Automated tests (none exist)
- Dual contact endpoints (`api/contact.php` component path vs `ajax/contact.php` page path) -- both valid, documented in ROUTES.md

## Homepage Recovery — Rendering Pipeline Fix [COMPLETE]

Sprint 13b (2026-08-06). Targeted repair of broken homepage render pipeline. No redesign, no content rewrite, no architecture/business changes.

- **Root cause 1**: `HomepageRenderer::render()` returned empty state when `homepage_sections` table was empty — `seedDefaults()` only ran from `admin/homepage.php`, never on the frontend render path. Fresh/imported DB rendered the "homepage is being configured" placeholder instead of the 14 default sections.
- **Root cause 2:** `RenderingEngine::renderHomepage()` emitted `<head>` twice (`require head.php` directly, then `header.php` which requires `head.php` again). Duplicate CSS links, meta, and JSON-LD.
- **Root cause 3:** No `pages` row with `is_homepage = 1` → `DynamicRouter::resolveHomepage()` returned `['type' => 'empty']` → "website is being set up" placeholder.
- **Fixes:**
  - `HomepageRenderer.php`: `render()` auto-seeds (`seedDefaults()`) when `getActive()` empty, then re-queries.
  - `RenderingEngine.php`: removed duplicate `require head.php` in `renderHomepage()` (header.php already includes it).
  - `DynamicRouter.php`: `resolveHomepage()` auto-creates default homepage page row (`is_homepage = 1`) if none exists.
- PHP lint passes. Homepage now auto-seeds 14 sections, emits `<head>` once, and resolves on a fresh DB.
- Remaining: live DB smoke test blocked (no MySQL); cross-browser + WCAG 2.1 AA pending (Sprint 17).

## Sprint 15A — Homepage Content Composition (Production Ready) [COMPLETE]

UI-layer composition. No CMS architecture, routing, database schema, or business logic changes.
Sources of truth: `C:\Users\swap\.claude\homepage.md` (approved Homepage content) and the Global Design System.

- **HomepageManager.php** — replaced all 14 section defaults with approved HOMEPAGE.md content:
  - Hero: approved headline, role subtitle, intro paragraphs, CTA labels
  - Introduction: approved title + paragraph
  - Services: approved title + mapped to 6 approved service descriptions (via `getDefaultServices`)
  - Why Choose: approved intro + 6 bullet cards with descriptions
  - Problems Solve: approved intro + 8 problem bullet cards
  - Why Work: approved prose + 4 benefit cards
  - Industries: approved 10-industry list with descriptions
  - Technology Stack: approved 12-technology list with inline Lucide icon support
  - Process: approved 5-step flow (Discussion, Planning, Design & Development, Testing & Launch, Ongoing Support)
  - Portfolio Preview: approved title + "View Portfolio" CTA + graceful empty state
  - Testimonials: approved title; removed fake testimonials fallback, added honest empty-state
  - FAQ: approved 4 Q&A pairs
  - Final CTA: approved heading/description/button labels
  - Contact: approved phone/email/location/availability (reads from section config, falls back to site.php)

- **HomepageRenderer.php** — fixed 5 production gaps:
  - Hero: added branded glass panel fallback when no hero_image (avoids broken image)
  - Services: updated `getDefaultServices()` to approved 6-service descriptions
  - Testimonials: removed `getDefaultTestimonials()` (fake testimonials); added `renderTestimonialsEmptyState()`
  - Portfolio: changed `return ''` empty-case to `renderPortfolioEmptyState()` rendering shell + CTA
  - Contact: extended to read `phone`/`email`/`location`/`availability` from section config (preferred) falling back to `site.php`
  - Added centralized `inlineIcon($name)` helper (Lucide-style inline SVG paths) used across why_choose, problems_solve, why_work_me, industries, technologies, process steps
  - Added `.cp-icon-wrap`, `.cp-icon-wrap--process`, `.cp-icon-wrap--white` icon wrapper CSS
  - Added hero branded panel markup and CSS

- **homepage.css** — extended with hero panel layout, icon helper rules, and contact icon variants

- **Docs updated**: PROJECT_MEMORY.md, CHANGELOG.md (1.5.2), TODO.md (Sprint 15A marked complete), PROJECT_STATUS.md (new)

## Sprint 15D — Homepage Premium Landing Page Experience [COMPLETE] (v1.5.4)

Premium landing page enhancements. No CMS architecture, routing, database, or business logic changes.

- **Hero**: layered glass panels (3 `.hero__glass-layer`), 8 floating particles, gradient glow pulses (`hero__glow-pulse`), animated "Available for Projects" badge, stat cards row with glass hover effect, hero panel 3D tilt on mousemove
- **Cards**: premium glass reflection overlay (`::after` pseudo), gradient border reveal (`::before` pseudo), icon scale on hover, enhanced shadow system
- **Motion**: GSAP chip stagger entrance, glass layer fade-in, 3D card tilt system (vanilla JS mousemove), stagger grid reveal for `.problems__grid`, `.industries__grid`, `.work-me__grid`, `.technologies__grid`
- **Visual**: section gradient dividers (`section--divider-top`, `section--divider-glow`), FAQ hover glow, CTA radial gradient overlays, empty state rotating conic gradient background + pulsing icon ring
- **Technology**: `simpleIcon()` helper — official Simple Icons filled SVG paths for WordPress, PHP, MySQL, HTML5, CSS3, JavaScript, Figma, WooCommerce, Shopify, Wix. Falls back to `inlineIcon()` for unmatched brands
- **Empty states**: premium glass cards with animated icon wrap (`empty-state__icon-wrap`), pulsing ring animation, CTA button
- **Contact**: WhatsApp/Phone CTA enhanced with icon scale + glow box-shadow, translateY scale transform
- **Files**: `HomepageRenderer.php` (simpleIcon, hero markup, empty state markup), `homepage.css` (+200 lines premium enhancement layer), `homepage.js` (+60 lines 3D tilt, stagger grids, hero animations)

### Files Modified

| File | Change |
|------|--------|
| `includes/content/HomepageRenderer.php` | `simpleIcon()` helper, hero badge + particles + glass layers + glow pulses, section header wrapper for empty states |
| `assets/css/homepage.css` | +200 lines: particles, glass layers, glow pulses, badge, stats row, dividers, card reflections, gradient borders, FAQ glow, CTA gradients, empty state icon wrap |
| `assets/js/homepage.js` | +60 lines: card tilt system, stagger grid scroll reveal, GSAP chip/glass animations |

---

## Sprint 15E — Premium Homepage Design Audit Rebuild [COMPLETE] (v1.5.5)

Full premium redesign pass. No CMS architecture, routing, DB, or approved content changes.

- **Foundation**: `.container` standardized to 1200px, `.section__header` centered, `.section__title` upgraded (800 weight, tighter letter-spacing)
- **Card system**: unified `display:flex; flex-direction:column; height:100%` with desc `flex-grow:1` for equal-height cards across problems/work-me/industries/tech/testimonials. Fixed invalid `--ds-space-36` padding tokens (undefined → fell to 0) to `--ds-space-32`.
- **Grids**: normalized gaps to `--ds-space-32` and min-widths (`problems 300px`, `work-me 280px`, `industries 240px`, `tech 140px`)
- **Icons**: `cp-icon-wrap` normalized to 32px; tech uses official Simple Icons (fill-based).
- **Portfolio**: `getPlaceholderPortfolio()` — 6 realistic dummy cards (id9001-9006) with gradient `pf-card__placeholder` thumbnail, category, title, description. Shown when DB empty + `show_placeholders` config. Replaced by real CMS data automatically.
- **Testimonials**: `getPlaceholderTestimonials()` — 3 labelled dummy reviews (id9101-9103) with name/role/company/rating via `fields` map + avatar initial fallback.
- **Files**: `homepage.css` (foundation + card/grid/icon normalization + portfolio placeholder), `HomepageManager.php` (`show_placeholders` flags), `HomepageRenderer.php` (placeholder methods + fallbacks + pf-card desc/placeholder markup)

---

## Sprint 16A — Credentials Sanitization + Production Safety Polish [COMPLETE]

Documentation-only sprint. No code or content changes. Verified changes that underpin the fix log:
- `includes/config/database.php`: `DB_PASS` reads from `getenv('DB_PASS')` with empty-string fallback; no embedded credential. `.env` gitignored.
- `HomepageRenderer.php`: portfolio/testimonial placeholder cards gated behind `show_placeholders` config flag (not enabled by default). Prevents production placeholder dummies appearing as real reviews/projects.
- `includes/header.php` + `includes/layout.php` + `includes/content/RenderingEngine.php`: `<main id="main-content">` landmark + skip-link `href="#main-content"` present on all rendered paths (homepage, module pages, empty-state).
- `assets/js/homepage.js`: duplicate tilt/parallax init removed — `tilt.js` and `parallax.js` (global component scripts) are the single owner of `[data-tilt]` / `[data-parallax]` interactions. homepage.js contains stub/no-op comments instead.
- `pages/500.php`: new server-error template rendered on unhandled PHP exceptions.
- `api/contact.php` + `api/whatsapp-track.php`: rate limiting applied (`rateLimitExceeded` with 5/15min and 20/5min limits respectively).
- `includes/head.php`: secondary design-system CSS loaded async via `media="print" onload="this.media='all'"` (badges, tabs, modal, drawer, toast, pagination, whatsapp, back-to-top, progress, breadcrumb). Google Fonts also async-loaded.
- Verified: `curl http://127.0.0.1:8080/` → HTTP 200, PHP lint clean on all modified files, CSRF protection functional, rate limiting active on api endpoints.

---

## Sprint 16 — Premium Creative Freelancer Landing Polish [COMPLETE] (v1.7.0)

Transformed homepage from generic dashboard into a premium dark creative freelancer / digital designer landing page. Frontend CSS/JS only; no content, routing, DB, or business-logic changes.

- **Hero**: Oversized `clamp(3rem, 7vw, 5.5rem)` heading (weight 900, tighter letter-spacing), deeper brand gradient overlay (`--ds-dark-canvas` base), brand-tinted glass panel with enhanced conic glow, description top-border accent.
- **Section Unification**: Each of 15 sections now has a distinct visual composition (no repeating heading→subtitle→grid pattern).
- **Experience**: Conic-gradient brand ring on `.experience__badge` via dual `background-clip` (padding-box + border-box).
- **Services**: Featured full-width card with gradient glass + hover lift/glow; icon on brand gradient; arrow slide on hover.
- **Why Choose**: Featured card gradient depth + icon glow; stacked cards get red-left-border with hover slide.
- **Problems**: Alternating masonry zigzag with brand tints (left=red / right=orange 3px borders).
- **Work Me**: Vertical timeline with brand glow line + pulsing numbered nodes (`node-pulse` keyframe).
- **Industries**: Featured cards gradient hover; cloud cards glass refinement.
- **Technologies**: Floating wall with large cards on brand glass + glow hover.
- **Process**: Vertical steps with gradient fill line + glowing numbered nodes.
- **Portfolio**: Featured wide project (21/9 aspect) with layered glow + radius; secondary cards hover glow.
- **Testimonials**: Featured card premium glass with oversized quote mark; star ratings preserved.
- **FAQ**: Side-by-side sticky category pills + accordion; active pill on brand gradient with glow; open accordion state glow.
- **CTA**: Full-width split panel with floating brand gradient orbs (`blur(20px)`).
- **Contact**: Premium glass panel depth with brand glow; method rows with hover states.
- **JS Enhancements**:
  - `initSectionReveal()`: GSAP ScrollTrigger staggered `[data-reveal]` reveal per section (fallback to IntersectionObserver).
  - `initScrollProgress()`: brand-gradient scroll progress bar (fixed at top, updates on scroll).
  - All animations gated by `prefers-reduced-motion`.
- **CSS**: 399 new lines in `Sprint 16` block of `assets/css/homepage.css`; unified button glow (`btn--cta`/`btn--primary`), outline hover, section typography weights (700→800), responsive refinements at 1024px/640px.
- **Validation**: PHP lint clean, JS syntax check passed, CSS brace-balance verified (660/660), page re-renders with all premium classes present.
- **Files**: `assets/css/homepage.css`, `assets/js/homepage.js`, `DESIGN_SYSTEM.md`, `CHANGELOG.md`, `PROJECT_STATUS.md`, `PROJECT_MEMORY.md`, `TODO.md`

---

## Sprint 15C — Homepage Premium Polish [COMPLETE]

Visual polish pass. No content, routing, DB, or business logic changes.

- **Hero**: floating decorative CSS orbs (`hero__orb--sm/md/lg`), decorative image-wrap pseudo-elements, hero panel glass sheen overlay, kicker pill styling on subheading, layout balance (1.05fr/0.95fr), larger panel (560px, 40px padding)
- **Icons**: extended `inlineIcon()` map with `phone`, `wordpress`, `woocommerce`, `shopify`, `wix`, `html5`, `css3`, `database` glyphs; brand-color tints via `data-tech` attribute on tech tiles
- **Cards**: upgraded to `ds-radius-20`/`ds-radius-16`, `ds-shadow-soft` base shadow, stronger hover shadows; `weight:700`, `line-height:1.3` on all card titles; icon color fill on hover
- **Process**: icon-reveal-on-hover animation (number fades, icon fades in); 76px circle, `overflow:hidden`
- **Contact**: WhatsApp/Phone CTA emphasis via `.contact-card__item--cta` (green glass tint, stronger hover glow); inline Lucide icons (`mail`, `phone`, `message-circle`)
- **Empty states**: premium glass cards — portfolio empty state with icon + CTA button, testimonials empty state with icon + honest messaging
- **Print**: hides `hero__orb` elements
- **Files**: `HomepageRenderer.php` (empty states, contact icons, tech `data-tech`/descriptions), `homepage.css` (all polish changes)

### Remaining Tasks (non-blocking)

- Live DB import + rendering smoke test on Hostinger (blocked: no MySQL access)
- Cross-browser + accessibility (WCAG 2.1 AA) audit → **completed Sprint 17 Phase 2** (v1.7.3)
- Simple Icons official brand SVG swap-in for technology section (optional follow-up)
- Post-deployment checklist (cache clear, sitemap submit, WhatsApp/contact test)

## Sprint 17 Phase 2 — Accessibility + Cross-Browser Fixes [COMPLETE] (v1.7.3)

WCAG 2.1 AA, cross-browser, responsive fixes. No content, routing, DB, CMS architecture, or design-token changes (except contrast fix).

- **`--ds-text-subtle` contrast** (A1): `#64748b` → `#94a3b8` in `design-tokens.css` (5.55:1 on dark canvas, passes AA)
- **Mobile menu focus trap** (A2): `header-nav.js` — Tab cycles within open mobile menu, Shift+Tab wraps, Escape closes, focus returns to toggle
- **Service icons** (A3): `serviceIcon()` in `HomepageRenderer.php` — 6 unique Lucide-style SVGs (branding/pen-tool, graphic/palette, uiux/layout, web/code, marketing/target, maintenance/settings); eliminated identical 4-square placeholder
- **About feature icons** (A4): `renderAbout()` injects `inlineIcon('check-circle')`; `.about__feature-icon .cp-icon` 14px sizing rule added
- **Social links** (A5): `.social-link` 42px → 44px
- **Mobile toggle** (A6): `.main-header__toggle` 40px → 44px
- **Nav focus-visible** (A7): explicit `:focus-visible` rule on `.main-nav__link` and `.main-nav__dropdown-toggle`
- **Inter font** (A8): removed from `header.php` (frontend); admin correctly retains
- **orphaned `design-system.css`** (A9): deleted (531 lines, zero references)
- **Location/Availability icons** (A10): map pin + clock SVGs added
- **Responsive** (D11): 480px breakpoint added in `responsive.css`

**Validated**: PHP lint clean, JS syntax clean, CSS brace balance 0, HTTP 200, 15 sections render, 0 four-square placeholders in live HTML, all 6 service icons + about + location/availability SVGs confirmed live.

**Deferred**: physical Safari/Firefox/Edge testing (no browsers available); admin modernization → Sprint 17 Phase 3.

## Sprint 17 Phase 3 — Admin Panel Modernization [COMPLETE] (v1.8.0)

Modernized admin panel with existing Global Design System tokens and premium glass UI. No CMS architecture, routing, DB, or business logic changes.

- **Premium glass loading** (Task 15): `admin-premium.css` added to `admin/includes/header.php` — all 28 standard admin pages now load the glassmorphism polish layer
- **Token contrast** (Task 16): `--admin-text-subtle` `#64748b` → `#a8b6c8` (AA on `--admin-bg: #05070f`); fixed `admin-required` undefined `--admin-danger` → `--admin-error`
- **Touch targets** (Task 17): `.admin-topbar__toggle` 36px → 44px (WCAG 2.5.8)
- **Modal ARIA** (Task 18): `role="dialog"`, `aria-modal="true"`, `aria-labelledby` added to footer link modal, media folder modal, media meta modal
- **Form labels** (Task 20): `aria-label` added to search/filter inputs/blocks, pages, media, sections; native selects restyled with `appearance:none`+chevron
- **Responsive** (Task 19): 480px + 390px breakpoints (stacked page headers, single-col stats, stacked form grids, full-width buttons); mobile sidebar width `min(280px, 85vw)`

**Validated**: PHP lint clean, CSS brace balance 0 (admin.css 297/297, admin-premium.css 88/88), HTTP 200 on homepage + admin login.

**Deferred**: physical Safari/Firefox/Edge testing (no browsers); Hostinger deployment + live DB smoke test (no MySQL access); admin remaining polish (sidebar restructure, glass forms, data table cards) → Sprint 20.

## Sprint 18 — Admin Panel Full CSS Migration [COMPLETE] (v1.9.0)

Admin panel fully migrated to Global Design System premium glass UI. All 28 admin pages now load consistent premium CSS with topbar, skip-link, backward-compatible buttons, and focus-visible support. No CMS architecture, routing, database schema, or business logic changes.

- **Topbar fix**: `contacts.php`, `leads.php`, `blog.php` now load `topbar.php` (were bypassing `header.php` — missing topbar + glass topbar styling)
- **Skip-link**: Added `.admin-skip-link` CSS + anchor to all 28 admin pages
- **`.btn` backward-compat**: Added comprehensive `.btn` alias block to `admin.css` supporting BOTH dash variants (`btn-primary` for SEO sub-pages + `btn--primary` for custom-shell pages)
- **Focus-visible**: Added `:focus-visible` to `.btn` variants
- **Header.php**: Added `$adminPageCss` optional include for custom-shell pages
- **Login skip-link**: Added skip-link to login page

**Validated**: PHP lint clean, CSS brace balance 0 (admin.css 314/314, admin-premium.css 88/88), HTTP 200 on homepage + admin login, all 28 admin pages load premium glass + topbar + skip-link.

**Known non-blocking**: `navigation.php:191` PHP deprecation (optional param before required param) — all callers pass all args, function works correctly.

## Sprint 19.1 — Admin Finalization & Verification Fixes [COMPLETE] (v1.9.2)

Finalized all 16 remaining issues discovered by independent Sprint 19 verification audit. No CMS architecture, routing, DB, or business logic changes. Public frontend unchanged.

### Sprint 19 Original Objective
Full admin CSS token migration + PHP deprecation fix (v1.9.1).

### Sprint 19.1 Fixes Completed
1. **Color migration**: ~85 remaining hardcoded colors migrated across 8 admin CSS files to `--admin-*` tokens (57 total values)
2. **Duplicate :root fix**: admin.css second `:root` block now references `var(--admin-*)` tokens instead of hardcoded values
3. **PHP deprecations**: 4 functions fixed (`renderContactField`, `renderField` ×2, `renderFormRow`) — added `$value = ''` default
4. **--text-secondary fix**: 4 references in services-admin.css replaced with `var(--admin-text-subtle)`
5. **Modal focus trap**: `adminModalTrap()` utility added to `admin.js`; all 3 modals have `tabindex="-1"`, focus cycling, Escape close, focus restoration
6. **Touch targets**: `.admin-notif-btn` 36px → 44px; `.admin-flash__close` 24px → 44px; `.btn--sm` min 44×44px
7. **Reduced motion**: Expanded to cover toast, modal, sidebar, topbar menu, cards, buttons, flash, stat cards, links, tooltips
8. **Label associations**: 9 admin pages now have proper `for`/`id` pairing on form labels
9. **users.php CSS**: All `users-*` CSS classes migrated to standard `admin-*` classes
10. **Custom-shell pages**: 6 pages migrated to standard `header.php`/`footer.php` shell (services, services-edit, portfolio, portfolio-edit, homepage, seo)
11. **PROJECT_STATUS.md**: Sections 10-11 updated; stale "Pending — Sprint 19 scope" items removed
12. **PROJECT_MEMORY.md**: Sprint 19.1 section added (this entry)
13. **TODO.md**: Duplicate Sprint 18 header fixed
14. **CSS syntax**: 6 double-semicolon errors removed across 4 files
15. **Utility classes**: Duplicate `.u-mb-sm`/`.u-mb-md` resolved (single definition)
16. **JetBrains Mono**: Confirmed not required in admin; code blocks use generic `monospace`

### Final Verification Status
- PHP lint clean across all modified files
- CSS brace balance 0 across all admin CSS files
- Zero `--text-secondary` references remaining
- Zero double-semicolon errors remaining
- Zero duplicate utility class conflicts
- 28 admin pages audited individually
- Public frontend: zero cross-contamination detected
- Deployment blocker: No MySQL/Hostinger access (unchanged)

## Upcoming Work

- Deploy to Hostinger shared hosting (once credentials provided)
- SEO audit dashboard admin page (Sprint 7 scope, note: SEOAuditor service exists)
- Per-page SEO meta fields UI (Sprint 7 scope, note: meta management interface exists)
- Content seeding script
- Physical cross-browser testing (Safari/Firefox/Edge) — no browser env available
- Sprint 20: admin remaining polish (sidebar restructure, glass forms, data table cards)
- Navigation.php:191 PHP deprecation — fixed in Sprint 19
- Simple Icons SVG swap for Technology Stack (optional follow-up)

## Sprint 15H — Homepage Layout Composition Redesign [COMPLETE] (v1.5.6)

Unique visual rhythm per section. Every Homepage section now has a distinct layout composition — no more repetitive Heading → Subtitle → Grid pattern. Only layout HTML/CSS modified; content, CMS architecture, Design System tokens, colors, and typography unchanged.

### Layout Changes Per Section

| Section | Composition |
|---------|------------|
| About | Editorial overlap: `.about__editorial` grid (1.6fr/1fr), `.about__content--inset` glass panel with negative margin overlap + `.about__accent-bar` (4px gradient vertical bar) |
| Experience | Radial: `.experience__radial` grid (2×2) with `.experience__badge` center circle (absolute positioned, 168px, glass elevated) |
| Services | Featured + support: `.services__featured-grid` with `.services__featured` spanning 2 columns; remaining 5 cards in standard grid below |
| Why Choose | Featured + stacked: `.why-choose__grid--featured` (1.2fr/0.8fr); `.why-choose__featured` (elevated glass with brand gradient icon); `.why-choose__stacked-col` vertical stack |
| Problems | Alternating masonry: `.problems__grid--masonry` (2-col, dense flow); `.problem-card--left`/`--right` with offset margins; `.problem-card--tall` for height variation |
| Why Work | Vertical timeline: `.work-me__timeline` with `::before` central gradient line; `.work-me-card--left`/`--right` (50% width, positioned); `.work-me-card__node` numbered circle |
| Industries | Featured + cloud: `.industries__featured` (3-col large); `.industries__cloud` (auto-fill min 200px, compact tiles, desc hidden) |
| Technologies | Floating wall: `.technologies__wall` (6-col dense flow); `.tech-item--medium` (2-col), `.tech-item--large` (2×2, 48px icon); `data-parallax="0.02"` |
| Process | Vertical steps: `.process__vertical` flex column; `.process-step` (flex row: `.process-step__node` 76px circle + `.process-step__content`); `::before` gradient line |
| Portfolio | Featured + secondary: `.portfolio__featured` (full-width card, 21:9 aspect); `.portfolio__secondary` (2-col grid, 4 cards) |
| Testimonials | Featured + secondary: `.testimonials__featured` (2-col); `.testimonial-card--featured` spans 2 rows, larger quote |
| FAQ | Side-by-side: `.faq__side-by-side` (240px/1fr grid); `.faq__categories` (sticky pill list); `.faq__category-pill` toggle buttons |
| CTA | Split panel: `.cta__split` (1fr/1fr grid); `.cta__copy` (left); `.cta__visual` (right, 3 floating gradient shapes with `hero-float` animation) |
| Contact | Premium panel: `.contact__premium-panel` (single glass card, 1.4fr/0.6fr grid); `.contact__methods` (vertical list); `.contact__cta` (CTA buttons) |

### Files Modified

| File | Change |
|------|--------|
| `includes/content/HomepageRenderer.php` | 14 render methods restructured; Hero untouched |
| `assets/css/homepage.css` | +548 lines layout composition CSS using `--ds-*` tokens only |
| `LAYOUT_COMPOSITION_REPORT.md` | NEW — full composition map, class reference, visual rhythm map |

### Constraints (confirmed)

- Design System (`assets/css/components/design-system/`): NOT modified
- Colors / typography tokens: NOT modified
- CMS architecture / DB schema / routing: NOT modified
- All approved content: preserved
- Existing JS (reveal, parallax, tilt, counter): unchanged
- [Sprint 15 Surgical](#) — 8 CSS premium upgrades + 2 JS reveal-dedup fixes (v1.7.1)
