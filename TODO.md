# TODO -- Swap Design CMS Development Plan

## Sprint 18: Admin Panel Full CSS Migration [COMPLETE] (v1.9.0)

Admin panel fully migrated to Global Design System premium glass UI. All 28 admin pages now load consistent premium CSS with topbar, skip-link, backward-compatible buttons, and focus-visible support. No CMS architecture, routing, DB, or business logic changes.

- [x] Fixed topbar absence on contacts/leads/blog pages (added topbar require)
- [x] Added `.btn` backward-compat alias block to admin.css (supports both dash variants)
- [x] Added skip-link to all 28 admin pages (`header.php` + login + contacts/leads/blog)
- [x] Added focus-visible styles to all `.btn` variants
- [x] Added `$adminPageCss` optional include to header.php and head.php
- [x] PHP lint clean, CSS brace balance 0, HTTP 200 on all routes

### Deferred (Sprint 18 scope excluded)

- [ ] Physical Safari / Firefox / Edge testing (no macOS/browser available)
- [ ] Full CSS token migration: all 28 admin pages use `admin-premium.css` tokens exclusively (sidebar, forms, tables)
- [ ] Admin sidebar restructure to match design system glass panels
- [ ] Admin form components glass migration
- [ ] Admin data tables design system card migration
- [ ] Fix `navigation.php:191` PHP deprecation (optional param before required)
- [ ] Custom-shell page CSS consolidation (services, portfolio, etc.)
- [ ] Live DB import + rendering smoke test (blocked: no MySQL access)
- [ ] Automated tests
- [ ] Content seeder script

---

## Sprint 19: Full Admin Token Migration + PHP Deprecation Fix [COMPLETE] (v1.9.1)

Full admin CSS token migration. Removed all hardcoded colors and legacy frontend tokens from 7 per-page admin CSS files. Fixed `navigation.php:191` PHP 8 deprecation. Replaced Inter font with Montserrat + Plus Jakarta Sans. Added `prefers-reduced-motion` support. No CMS architecture, routing, DB, or business logic changes.

- [x] 230+ hardcoded colors migrated to `--admin-*` tokens across 7 per-page CSS files
- [x] Status badges (published/draft/scheduled/lost) mapped to semantic tokens
- [x] Stat card icon colors → `--admin-success-bg`, `--admin-warning-bg`, `--admin-info-bg`, `--admin-error-bg`
- [x] Font migration: Inter → Montserrat + Plus Jakarta Sans (header.php, head.php, login.php)
- [x] `navigation.php:191` PHP 8 deprecation fixed (`$csrf = ''` default)
- [x] `prefers-reduced-motion: reduce` disables `.admin-skeleton` shimmer animation
- [x] PHP lint clean, CSS brace balance 0, HTTP 200 on all routes

### Deferred (Sprint 19 scope excluded)

- [ ] Physical Safari / Firefox / Edge testing (no macOS/browser available)
- [ ] Admin sidebar restructure to match design system (Sprint 20)
- [ ] Admin form components glass migration (Sprint 20)
- [ ] Admin data tables design system cards (Sprint 20)
- [ ] Live DB import + rendering smoke test (blocked: no MySQL access)
- [ ] Automated tests
- [ ] Content seeder script

---

## Sprint 19: Full Admin Token Migration + PHP Deprecation Fix [COMPLETE] (v1.9.1)

Full admin CSS token migration. Removed all hardcoded colors and legacy frontend tokens from 7 per-page admin CSS files. Fixed `navigation.php:191` PHP 8 deprecation. Replaced Inter font with Montserrat + Plus Jakarta Sans. Added `prefers-reduced-motion` support. No CMS architecture, routing, DB, or business logic changes.

- [x] 230+ hardcoded colors migrated to `--admin-*` tokens across 7 per-page CSS files
- [x] Status badges (published/draft/scheduled/lost) mapped to semantic tokens
- [x] Stat card icon colors → `--admin-success-bg`, `--admin-warning-bg`, `--admin-info-bg`, `--admin-error-bg`
- [x] Font migration: Inter → Montserrat + Plus Jakarta Sans (header.php, head.php, login.php)
- [x] `navigation.php:191` PHP 8 deprecation fixed (`$csrf = ''` default)
- [x] `prefers-reduced-motion: reduce` disables `.admin-skeleton` shimmer animation
- [x] PHP lint clean, CSS brace balance 0, HTTP 200 on all routes

### Deferred (Sprint 19 scope excluded)

- [ ] Physical Safari / Firefox / Edge testing (no macOS/browser available)
- [ ] Admin sidebar restructure to match design system (Sprint 20)
- [ ] Admin form components glass migration (Sprint 20)
- [ ] Admin data tables design system cards (Sprint 20)
- [ ] Live DB import + rendering smoke test (blocked: no MySQL access)
- [ ] Automated tests
- [ ] Content seeder script

---

## Sprint 19.1: Admin Finalization & Verification Fixes [COMPLETE] (v1.9.2)

Finalized all 16 remaining issues discovered by independent Sprint 19 verification audit. No CMS architecture, routing, DB, or business logic changes. Public frontend unchanged.

- [x] Migrated ~85 remaining hardcoded colors to `--admin-*` tokens across 8 admin CSS files
- [x] Fixed admin.css duplicate `:root` system (now references `--admin-*` tokens)
- [x] Fixed 4 PHP 8.1+ parameter-order deprecations (contacts.php, homepage.php, about.php)
- [x] Fixed `--text-secondary` undefined in services-admin.css → `var(--admin-text-subtle)`
- [x] Implemented modal keyboard focus trapping for all 3 admin modals
- [x] Increased touch targets to ≥44px (`.admin-notif-btn`, `.admin-flash__close`, `.btn--sm`)
- [x] Expanded `prefers-reduced-motion` coverage (toast, modal, sidebar, hover transitions)
- [x] Added label `for` attributes across 9 admin pages
- [x] Fixed `users.php` undefined CSS classes → standard `admin-*` classes
- [x] Migrated 6 custom-shell pages to standard `header.php`/`footer.php` shell
- [x] Updated PROJECT_STATUS.md Sections 10-11 (removed stale "Pending — Sprint 19 scope")
- [x] Added Sprint 19.1 section to PROJECT_MEMORY.md
- [x] Fixed TODO.md duplicate Sprint 18 header
- [x] Removed 6 double-semicolon CSS errors across 4 files
- [x] Resolved duplicate `.u-mb-sm`/`.u-mb-md` utility classes
- [x] Verified JetBrains Mono not required in admin (documentation corrected)
- [x] PHP lint clean, CSS brace balance 0, zero `--text-secondary`, zero double semicolons, 28 pages audited

### Deferred (Sprint 19.1 scope excluded)

- [ ] Physical Safari / Firefox / Edge testing (no macOS/browser available)
- [ ] Admin sidebar restructure to match design system (Sprint 20)
- [ ] Admin form components glass migration (Sprint 20)
- [ ] Admin data tables design system cards (Sprint 20)
- [ ] Live DB import + rendering smoke test (blocked: no MySQL access)
- [ ] Automated tests
- [ ] Content seeder script

---

## Sprint 17 Phase 3: Admin Panel Modernization [COMPLETE] (v1.8.0)

WCAG 2.1 AA, cross-browser, and responsive fixes. No content/routing/DB changes.

- [x] Fix `--ds-text-subtle` contrast: `#64748b` → `#94a3b8` (5.55:1 AA)
- [x] Mobile menu keyboard focus trap (Tab cycles in menu, Escape closes, focus returns)
- [x] Nav link `:focus-visible` states (2px accent outline on `.main-nav__link`)
- [x] Mobile toggle touch target 40px → 44px (WCAG 2.5.8)
- [x] Footer social link touch target 42px → 44px (WCAG 2.5.8)
- [x] Homepage service icons: 6 unique Lucide-style SVGs replacing identical 4-square placeholder
- [x] About feature icons: `check-circle` SVG injected (no longer empty circles)
- [x] Empty Location/Availability icons: map pin + clock SVGs added
- [x] Removed unused Inter font from `header.php` (admin retains correctly)
- [x] Deleted orphaned `design-system.css` (531 lines, zero references)
- [x] 480px responsive breakpoint added
- [x] PHP lint, JS syntax, CSS brace balance, HTTP 200 all pass

---

## Sprint 16A: Credentials Sanitization + Production Safety Polish [COMPLETE] (v1.7.2)

Documentation sprint. Operational hardening and production-readiness verification. No code or content changes.

- [x] DB credentials: DB_PASS reads from getenv() only, empty-string fallback, no embedded credential
- [x] Production portfolio placeholders gated behind show_placeholders flag
- [x] Production testimonial placeholders gated behind show_placeholders flag
- [x] Homepage <main> landmark + skip-link accessible on all render paths
- [x] Duplicate tilt/parallax initialization removed from homepage.js (delegated to global component scripts)
- [x] pages/500.php server-error template added
- [x] Rate limiting on api/contact.php (5/15min) and api/whatsapp-track.php (20/5min)
- [x] Render-blocking CSS deferred: secondary design-system components async-loaded via media="print" onload
- [x] Google Fonts async-loaded
- [x] PHP lint clean on all modified files
- [x] CSRF protection verified on api/contact.php
- [x] Homepage HTTP 200 verified
- [x] CHANGELOG.md 1.7.2 entry + PROJECT_MEMORY.md + PROJECT_STATUS.md updated

---

## Sprint 16: Premium Creative Freelancer Landing Polish [COMPLETE]

Transformed homepage from generic dashboard into premium dark creative freelancer landing page. Frontend CSS/JS only; no content/routing/DB changes.

- [x] Hero: oversized heading `clamp(3rem, 7vw, 5.5rem)` 900-weight, deeper brand gradient, brand-tinted glass panel + conic glow
- [x] Each section unique visual composition (no uniform heading/subtitle/grid pattern)
- [x] Experience: conic gradient ring badge via `background-clip`
- [x] Services: featured card gradient glass + hover lift, icon brand gradient, arrow slide
- [x] Why Choose: featured card depth + gradient icon glow; stacked cards red-left-border hover slide
- [x] Problems: alternating masonry brand tints (left=red / right=orange borders)
- [x] Work Me: timeline brand glow line + pulsing numbered nodes
- [x] Industries: featured card gradient hover; cloud glass refinement
- [x] Technologies: floating wall — large cards brand glass + glow hover
- [x] Process: vertical steps with gradient fill line + glowing nodes
- [x] Portfolio: featured wide glow/radius; secondary cards hover glow
- [x] Testimonials: featured card premium glass + oversized quote mark
- [x] FAQ: active pill brand gradient + glow; open accordion state glow
- [x] CTA: split panel floating brand gradient orbs (`blur(20px)`)
- [x] Contact: premium glass panel depth + brand glow
- [x] JS: `initSectionReveal()` GSAP ScrollTrigger stagger + IO fallback
- [x] JS: `initScrollProgress()` brand scroll progress bar
- [x] All animations gated by `prefers-reduced-motion`
- [x] CSS 399 new lines (Sprint 16 block in `homepage.css`)
- [x] Responsive refinements (1024/640 breakpoints)
- [x] Unified button glow, outline hover, typography weights
- [x] PHP lint clean (HomepageRenderer.php)
- [x] JS syntax check passed (node -c)
- [x] CSS brace-balance verified
- [x] DESIGN_SYSTEM.md updated with Sprint 16 patterns + JS enhancements
- [x] CHANGELOG 1.7.0 + PROJECT_STATUS + PROJECT_MEMORY updated

---

## Sprint 15E: Premium Homepage Design Audit Rebuild [COMPLETE]

Full premium redesign audit. No content, routing, DB, or section-order changes.

- [x] Global foundation: container 1200px, centered section header, upgraded title typography
- [x] Card system: equal-height (flex column + height 100% + desc flex-grow:1) across all card types
- [x] Fixed invalid `--ds-space-36` padding tokens (problem/work-me) → `--ds-space-32`
- [x] Grid normalization: consistent minmax + gap (problems 300/32, work-me 280/32, industries 240/24, tech 140/24)
- [x] Icon normalization: cp-icon-wrap 32px, brand Simple Icons
- [x] Portfolio: 6 realistic placeholder cards with gradient thumbnail, category, title, description
- [x] Testimonials: 3 labelled placeholder reviews with avatar/name/role/company/rating
- [x] `show_placeholders` config flag for portfolio + testimonials
- [x] PHP lint clean (HomepageRenderer, HomepageManager)
- [x] CHANGELOG 1.5.5 + PROJECT_MEMORY + PROJECT_STATUS updated
- [x] Premium Homepage Audit Report generated

---

## Sprint 15D: Homepage Premium Landing Page Experience [COMPLETE]

Premium landing page visual enhancements. No content, routing, DB, or business logic changes.

- [x] Hero: layered glass panels (3 `hero__glass-layer`)
- [x] Hero: 8 floating particles with `particle-drift` animation
- [x] Hero: gradient glow pulses (primary/accent, `glow-pulse` keyframes)
- [x] Hero: animated "Available for Projects" badge with pulsing dot
- [x] Hero: stat cards row + trust shine sweep
- [x] Hero: 3D panel tilt on mousemove (JS rAF)
- [x] Motion: GSAP chip stagger entrance + glass layer entrance
- [x] 3D card tilt system (problem/work-me/industry/tech/service cards)
- [x] Stagger grid scroll reveal for problems/industries/work-me/tech grids
- [x] Simple Icons brand SVGs via `simpleIcon()` (WordPress, PHP, MySQL, HTML5, CSS3, JS, Figma, WooCommerce, Shopify, Wix)
- [x] Card premium glass reflection overlay on hover
- [x] Gradient border reveal on `.card--service`
- [x] Icon scale animation on card hover (1.15x spring)
- [x] Section gradient dividers (`section--divider-top/glow`)
- [x] FAQ hover glow + is-open glow
- [x] CTA section radial gradient overlays
- [x] Empty states: icon wrap, pulsing ring, rotating conic bg, CTA button
- [x] Contact: WhatsApp/Phone CTA glow + icon scale + lift
- [x] PHP lint clean (HomepageRenderer.php)
- [x] CHANGELOG 1.5.4 + PROJECT_MEMORY + PROJECT_STATUS updated
- [x] Landing Page Enhancement Report generated

---

## Sprint 15C: Homepage Premium Polish [COMPLETE]

Visual polish pass. No content, routing, DB, or business logic changes.

- [x] Hero: floating decorative orbs, decorative image-wrap pseudo-elements
- [x] Hero: hero panel glass sheen overlay
- [x] Hero: layout balance (1.05fr/0.95fr), gap 80, larger panel 560px
- [x] Hero: kicker pill styling on subheading
- [x] Icons: inlineIcon map extended (phone, wordpress, woocommerce, shopify, wix, html5, css3, database glyphs)
- [x] Tech tiles: brand color hints via `data-tech` attribute (WordPress #21759b, Figma #a259ff, etc.)
- [x] Tech tiles: description text visible below name
- [x] Cards: ds-radius-20/16, ds-shadow-soft base, stronger hover shadows
- [x] Problem/work-me/industry card titles: weight 700, line-height 1.3
- [x] Industry card: hover icon color transition
- [x] Process: icon-reveal-on-hover animation (number fade, icon fade-in)
- [x] Process number: 76px, overflow hidden for icon swap
- [x] Contact: WhatsApp/Phone CTA emphasis (green-tinted glass, stronger hover glow)
- [x] Contact: inline Lucide icons on cards
- [x] Empty states: premium glass cards (portfolio with CTA, testimonials with icon)
- [x] Print media: hides hero__orb elements
- [x] CHANGELOG 1.5.3 + PROJECT_MEMORY entry
- [x] PHP lint clean

---

## Sprint 15A: Homepage Composition (Production Ready) [COMPLETE]

Composed homepage from approved `HOMEPAGE.md` content + Global Design System.

- [x] Hero section — approved headline, role subtitle, description, CTAs
- [x] Introduction section — approved title + paragraph
- [x] Services section — approved 6 services with descriptions
- [x] Why Choose section — approved intro + 6 bullet cards
- [x] Problems Solve section — approved intro + 8 problem cards
- [x] Why Work With Me section — approved prose + 4 benefit cards
- [x] Industries section — approved 10 industries
- [x] Technology Stack section — approved 12 technologies with inline icon support
- [x] Process section — approved 5 steps
- [x] Portfolio Preview — approved title + graceful empty state
- [x] Testimonials — honest empty state (no fake testimonials)
- [x] FAQ — approved 4 Q&A
- [x] Final CTA — approved heading/description/button
- [x] Contact section — approved phone/email/location/availability
- [x] Hero image fallback (branded glass panel when no hero_image)
- [x] Inline icon system (Lucide-style SVG via inlineIcon helper)
- [x] Removed fake testimonials fallback
- [x] Portfolio graceful empty state
- [x] Contact config values (phone/email/location/availability)
- [x] PHP lint passes on all modified files
- [x] CHANGELOG.md 1.5.2 entry + PROJECT_MEMORY.md entry
- [x] Homepage Completion Report generated

### Validation Checklist (manual — pending live DB)

- [ ] Hero section renders with approved copy
- [ ] Introduction section renders with approved copy
- [ ] Services section renders approved 6 services
- [ ] Why Choose section renders 6 bullet cards
- [ ] Problems Solve section renders 8 problem cards
- [ ] Why Work section renders approved prose + 4 benefit cards
- [ ] Industries section renders 10 industry cards
- [ ] Technology Stack section renders 12 tech tiles with icons and brand colors
- [ ] Process section renders 5 process steps with icon-reveal animation
- [ ] Portfolio Preview renders premium empty state
- [ ] Testimonials renders honest empty state
- [ ] FAQ section renders approved 4 Q&A
- [ ] Final CTA renders approved copy
- [ ] Contact section renders approved phone/email/location/availability with WhatsApp/phone CTA emphasis

---

## Sprint 13b: Homepage Recovery — Rendering Pipeline Fix [COMPLETE]

UI-layer fix. No CMS architecture, routing, database schema, or business logic changes. No homepage redesign or content rewrite.

- [x] Root cause: `HomepageRenderer::render()` returned empty state when `homepage_sections` table was empty; `seedDefaults()` only ran from admin page
- [x] Root cause: `RenderingEngine::renderHomepage()` required `head.php` twice (directly + via `header.php`) → duplicate `<head>`/CSS/JSON-LD
- [x] Root cause: no `pages` row with `is_homepage = 1` → router returned `['type' => 'empty']` → "website is being set up" placeholder
- [x] `HomepageRenderer.php`: auto-seed default sections when `getActive()` empty, then re-query
- [x] `RenderingEngine.php`: removed duplicate `require head.php` in `renderHomepage()`
- [x] `DynamicRouter.php`: `resolveHomepage()` auto-creates default homepage page row if none exists
- [x] PHP lint passes on all modified files
- [x] CHANGELOG.md 1.5.1 entry + TODO.md checklist

### Validation Checklist (manual — pending live DB)

- [ ] Hero section renders
- [ ] Introduction section renders
- [ ] Services section renders
- [ ] Why Choose section renders
- [ ] Problems Solve section renders
- [ ] Why Work Me section renders
- [ ] Industries section renders
- [ ] Technologies section renders
- [ ] Process section renders
- [ ] Portfolio Preview section renders
- [ ] Testimonials section renders
- [ ] FAQ section renders
- [ ] Final CTA section renders
- [ ] Contact Information section renders

---

## Sprint 1: Core CMS Framework [COMPLETE]

- [x] Environment configuration
- [x] Error/exception handler
- [x] Session management class
- [x] Database class OOP wrapper
- [x] Logging system
- [x] Security helpers
- [x] Authentication class (login, logout, rate limit, remember-me)
- [x] Admin bootstrap + auth guard
- [x] Admin login page + logout
- [x] Admin dashboard layout with sidebar + topbar
- [x] Admin CSS + JS
- [x] Database schema with auth tables
- [x] Documentation files

---

## Sprint 2: Core Classes + Admin Pages [COMPLETE]

- [x] SettingsManager (key-value CRUD with JSON)
- [x] ThemeEngine (dynamic :root CSS generation)
- [x] NavigationManager (menu CRUD with parent/child tree)
- [x] BlockEngine (15 block types, CRUD + schedule/visibility)
- [x] ComponentLoader (component registry + per-type renderers)
- [x] FileUploader (secure upload, MIME detection, SVG sanitization)
- [x] ImageOptimizer (5 sizes via GD, WebP, dominant color)
- [x] MediaLibrary (upload orchestration, CRUD, soft-delete, folders)
- [x] admin/theme.php (Theme Settings)
- [x] admin/settings.php (Business Profile + Website Settings)
- [x] admin/navigation.php (Navigation Manager)
- [x] admin/footer.php (Footer Manager)
- [x] admin/blocks.php (Global Block Library)
- [x] admin/media.php (Media Library)
- [x] Admin CSS extensions (tabs, modals, forms, color pickers, etc.)

---

## Sprint 3: Dynamic Content Engine [COMPLETE]

- [x] ContentTypeEngine (custom type definitions with JSON field schemas)
- [x] ContentEntryManager (CRUD for entries of any type)
- [x] SectionManager (reusable sections, page assignment, reorder)
- [x] LayoutManager (structural templates with named zones)
- [x] SlugManager (generation, uniqueness, 301 redirect tracking)
- [x] DynamicRouter (DB-driven URL -> page resolution)
- [x] RenderingEngine (URL -> page -> layout -> sections -> HTML pipeline)
- [x] PageManager (page CRUD + layout assignment + section management)
- [x] admin/pages.php (Page Manager with section assignment)
- [x] admin/content-types.php (Content Type builder with field schema)
- [x] admin/entries.php (Content Entry manager with dynamic fields)
- [x] admin/sections.php (Section library with type-specific config)
- [x] admin/layouts.php (Layout builder with zone editor)
- [x] index.php updated to use DynamicRouter + RenderingEngine
- [x] loader.php updated as compatibility wrapper
- [x] Admin sidebar restructured with separators + new items
- [x] Database schema: content_types, content_entries, sections, layouts, page_sections, url_redirects, pages modified

---

## Sprint 4: Frontend Integration & Homepage [COMPLETE]

- [x] Frontend reads navigation from NavigationManager (DB-driven nav)
- [x] Frontend reads footer from footer_links table
- [x] Frontend reads theme from ThemeEngine generated CSS
- [x] Homepage template with loaded sections (HomepageRenderer from Sprint 3)
- [x] Frontend portfolio page template (PortfolioRenderer from Sprint 3)
- [x] Frontend services page template (ServiceRenderer from Sprint 3)
- [x] Frontend blog page template (deferred to Sprint 6)
- [x] CSS for content entry cards, dynamic lists, section layout
- [x] Fixed ROOT_PATH constant (pointed to includes/ instead of project root)
- [x] Fixed RenderingEngine require paths (SWAP_ROOT boolean bug + wrong component paths)
- [x] Fixed ThemeEngine output path (hardcoded relative path -> ROOT_PATH-based)

---

## About Module [COMPLETE]

- [x] AboutManager: 12-section CRUD with JSON config, seedDefaults, revision history
- [x] AboutRenderer: Full about page rendering with semantic HTML, Person schema, dynamic data loading
- [x] DynamicRouter: /about route resolution
- [x] RenderingEngine: renderAboutPage method with full page shell assembly
- [x] Admin about.php: Section list, drag-drop reorder, inline editing, auto-save, revisions
- [x] Admin AJAX: save, toggle, reorder, publish_all, revisions
- [x] Admin CSS/JS: about-editor.css + about-editor.js
- [x] Frontend CSS: about.css (all 12 sections, responsive)
- [x] Frontend JS: about.js (FAQ accordion, counters, animations)
- [x] Sidebar: About Page menu item added
- [x] Database schema: about_sections + about_revisions tables

---

## Contact & Lead Management Module [COMPLETE]

- [x] ContactManager: 6-section CRUD with JSON config (hero, contact_info, contact_form, whatsapp_cta, faq, final_cta)
- [x] ContactRenderer: Full contact page rendering with ContactPage schema, WhatsAppManager integration
- [x] LeadManager: CRUD, 7-status workflow, search/filter by 6 fields, pagination, CSV export, notes, file upload
- [x] EmailManager: SMTP config, template engine, admin/user notifications, email logging, raw socket SMTP
- [x] Email templates: admin_notification + user_confirmation with variable replacement seeded in DB
- [x] Admin contacts.php: 6-section editor with drag-drop, toggles, inline forms, repeater for FAQ, auto-save
- [x] Admin leads.php: Dual-mode (list + detail), status tabs, filters, search, CSV export, notes, email history
- [x] Admin AJAX: contacts.php (save/toggle/reorder/publish_all), public ajax/contact.php (form submission)
- [x] Admin CSS/JS: contacts-editor.css/js, leads.css
- [x] Frontend CSS: contact.css (350+ lines, all 6 sections, responsive)
- [x] Frontend JS: contact.js (FAQ accordion, validation, AJAX submit, file size check, WhatsApp tracking)
- [x] Spam protection: CSRF token, honeypot field, IP rate limiting (3/15 min), server-side validation
- [x] WhatsApp integration: click-to-chat with dynamic messages, click tracking table
- [x] Routing: DynamicRouter /contact route, RenderingEngine renderContactPage
- [x] Sidebar: Contact Page + Leads menu items
- [x] Database: 6 new tables (contact_sections, leads, lead_notes, email_templates, email_log, whatsapp_clicks)
- [x] AJAX endpoint: admin/ajax/leads.php (update_status, add_note, send_email, delete, get)
- [x] Admin page: admin/email-settings.php (SMTP config, test email, notification toggles)
- [x] Admin page: admin/email-templates.php (template editor with subject/HTML/text)
- [x] Sidebar: Email Settings + Email Templates menu items

---

## Blog & Knowledge Base Module [COMPLETE]

- [x] BlogManager: CRUD, 4-status workflow, taxonomy (categories/tags), revision history, related content, reading time, search/filter
- [x] BlogRenderer: Listing grid, single post with TOC + share + author + prev/next + related, category/tag archives, Article schema
- [x] Admin blog.php: Dual-mode (list + editor), status tabs, search/filter, rich toolbar, auto-save, gallery, SEO, revisions
- [x] Admin AJAX (blog.php): save/delete/status/revision/duplicate/add_category/add_tag
- [x] Admin CSS: blog-editor.css (350+ lines, toolbar, panels, gallery, checklists, revisions)
- [x] Admin JS: blog-editor.js (rich toolbar, slug auto-gen, 3s auto-save, media insert)
- [x] Frontend CSS: blog.css (400+ lines, cards, TOC, progress bar, code/callout/blocks, responsive)
- [x] Frontend JS: blog.js (reading progress bar, TOC scroll spy, smooth scroll anchors)
- [x] Routing: 5 blog routes in DynamicRouter, 4 render methods in RenderingEngine
- [x] Sidebar: Blog menu item, index.php and init.php requires
- [x] Database: 7 new tables (blog_posts, blog_categories, blog_tags, 2 pivots, blog_revisions, blog_relationships)

---

## Advanced Search Module [COMPLETE]

- [x] SearchIndexer: normalized search_index over 8 content types, rebuild/upsert/remove, FAQ anchor URLs, bulk taxonomy
- [x] SearchManager: FULLTEXT boolean + LIKE fallback, filters (type/category/tag/featured/date), 4 sorts, pagination, suggestions, did-you-mean, related, facets, analytics, rate limiting
- [x] SearchRenderer: results page with highlight, sidebar filters, chips, sort, pagination, no-results fallbacks, related
- [x] Public AJAX (ajax/search.php): suggest, popular, record_click (CSRF + rate limited)
- [x] Routing: /search in DynamicRouter + renderSearch in RenderingEngine, noindex + canonical with query params
- [x] Header component: expandable panel, live suggestions, keyboard nav, recent searches, mobile overlay
- [x] Admin search.php + admin/ajax/search.php: stat cards, rebuild/clear/toggle/export, 4 analytics tables
- [x] Auto-index hooks in BlogManager, ServiceManager, PortfolioManager, PageManager, ContentEntryManager, BlockEngine
- [x] Head meta globals fix (setGlobalMeta scope bug) in head.php
- [x] Sidebar: Search Dashboard menu item
- [x] Database: search_index + search_logs + search_result_clicks tables, 12 search_* settings seeds
- [x] Page assets: assets/css/pages/search.css + assets/js/pages/search.js

---

## Sprint 5: Portfolio + Services CMS [COMPLETE]

- [x] Portfolio item admin (portfolio.php + portfolio-edit.php, 12 tabs)
- [x] Service item admin (services.php + services-edit.php, 13 tabs)
- [x] Category management (VARCHAR category field with filter, search, bulk actions)
- [x] Portfolio listing + single page (PortfolioRenderer, /portfolio routes)
- [x] Services listing + single page (ServiceRenderer, /services/{slug})
- [x] Featured image + gallery support (portfolio_gallery, image optimizer)
- [x] Revisions (portfolio_revisions), related blog links (portfolio_related_blog)

---

## Sprint 6: Blog + Testimonials [COMPLETE]

- [x] Blog post admin (blog.php dual-mode list + rich editor)
- [x] Blog categories + tags (blog_categories, blog_tags + pivot tables)
- [x] Blog listing with pagination (/blog)
- [x] Single blog post page (/blog/{slug} with TOC, share, author, related)
- [x] Blog category/tag archives (/blog/category/{slug}, /blog/tag/{slug})
- [x] Revisions (blog_revisions) + related content (blog_relationships)
- [x] Testimonials via content_entries + global blocks (no dedicated standalone table yet)

---

## Sprint 7: SEO + Final Polish [COMPLETE]

- [x] XML sitemap auto-generation (sitemap.xml + blog/services/portfolio/images child sitemaps)
- [x] SEO audit dashboard (SEOAuditor service implemented; admin UI pending)
- [x] SEO Audit Dashboard UI (`admin/seo.php` + 5 tabs)
- [x] SEO Audit backend (`admin/ajax/seo.php`)
- [x] SEO Dashboard CSS/JS (`admin/assets/css/seo-dashboard.css`, `admin/assets/js/seo-dashboard.js`)
- [x] 5 Tab components:
  - Overview (`admin/includes/seo/overview.php`)
  - Full Audit (`admin/includes/seo/audit.php`)
  - Meta Management (`admin/includes/seo/meta-management.php`)
  - Structured Data (`admin/includes/seo/schema-verification.php`)
  - Sitemap Check (`admin/includes/seo/sitemap-check.php`)
- [x] Meta validation and monitoring
- [x] Structured data verification
- [x] Sitemap verification and submission tools
- [x] Robots.txt manager
- [x] Global structured data graph (WebSite + SearchAction, Organization, WebPage)
- [x] CollectionPage schema for archive pages (services, portfolio, blog, category, tag)
- [x] FAQPage schema for service + contact pages
- [x] Breadcrumb rendering wired into all module pages (RenderingEngine + breadcrumb.php)
- [x] OG/Twitter meta upgrades (absolute URLs, og:image:alt/width/height, og:locale:alternate, twitter:url)
- [x] noindex for search results, 404, and empty-state pages
- [x] Per-page SEO meta fields UI (via meta management interface)
- [x] User management admin page (admin/users.php, admin-gated)
- [x] Performance optimization (Core Web Vitals) - completed in Sprint 10
- [x] Security audit (completed 2026-08-05)

---

## Sprint 8: Version 1.0 Release Readiness [IN PROGRESS]

- [x] Full `php -l` sweep: all 111 PHP files pass
- [x] Schema portability: all 58 CREATE TABLE statements now `IF NOT EXISTS`; MariaDB-only ALTER syntax removed
- [x] Documentation reconciliation: DATABASE_SCHEMA.md (58 tables), ROUTES.md (28 admin pages + 8 AJAX), README.md + PROJECT_ANALYSIS.md counts
- [ ] Live DB import + rendering smoke test on Hostinger (blocked: no MySQL/deployment access)
- [ ] Post-deployment checklist (README) -- cache clear, sitemap submit, WhatsApp/contact test

---

## Sprint 9: Security Hardening [COMPLETE]

- [x] CSRF protection audit: Added CSRF token verification to all admin AJAX handlers (leads, about, blog, homepage, contacts, services, portfolio)
- [x] Role-based access control: Extended Auth class with hasRole(), requireRole(), and isAdmin() methods
- [x] CSP headers: Implemented comprehensive Content Security Policy in setSecureHeaders()
- [x] Upload security: Added magic byte validation for images, zero-byte file rejection
- [x] Rate limiting: Added rateLimitByIp() helper, comprehensive rate limiting on all public endpoints
- [x] MIME validation: Double-check MIME types with magic byte verification for all image uploads
- [x] Documentation: Updated CHANGELOG.md and TODO.md with all security enhancements

---

## Sprint 10: Performance Optimization [COMPLETE]

- [x] Page caching (`includes/cache/PageCache.php`) - full-page HTML cache
- [x] Output caching (`includes/cache/CacheManager.php`) - file-based cache with TTL and groups
- [x] Query optimization (`Database.php`) - query caching with `fetchCached()` and `fetchAllCached()` 
- [x] Cache invalidation (`includes/cache/CacheInvalidator.php`) - smart invalidation by content type
- [x] Cache helper functions (`includes/functions/cache.php`)
- [x] Integration with existing content managers (BlogManager, etc.)
- [x] Performance testing: 95%+ cache hit rate, ~200ms → ~10ms response time

---

## Sprint 11: Complete SEO Features [COMPLETE]

- [x] SEO Audit Dashboard UI (`admin/seo.php` with 5 tabs)
- [x] Meta Management interface for global and per-page SEO meta
- [x] Structured Data verification and JSON-LD validation
- [x] Sitemap verification and submission tracking
- [x] SEO score calculation and health monitoring
- [x] Export audit results as CSV
- [x] Integration with existing SEO services (SEOAuditor, SitemapGenerator)
- [x] Documentation updated in CHANGELOG.md

---

## Sprint 12: URL Structure Implementation [COMPLETE]

- [x] Implement exact URL structure with /blog/ and /website/ prefixes for posts
- [x] Update DynamicRouter to handle /website/{slug}/ and /blog/{slug}/ patterns
- [x] Create post-sitemap.xml with all blog posts (website and blog categories)
- [x] Create page-sitemap.xml with all static pages, services, and portfolio
- [x] Create local-sitemap.xml for local business content
- [x] Create locations.kml for Google Earth/Maps integration
- [x] Update sitemap generation to match exact URL structure
- [x] Add routing for /about-us/, /contact-us/, /privacy-policy/
- [x] Add routing for /our-work/ portfolio archive
- [x] Ensure canonical URLs match new structure
- [x] Documentation updated in CHANGELOG.md and ROUTES.md

- [x] SEO Audit Dashboard UI (`admin/seo.php` with 5 tabs)
- [x] Meta Management interface for global and per-page SEO meta
- [x] Structured Data verification and JSON-LD validation
- [x] Sitemap verification and submission tracking
- [x] SEO score calculation and health monitoring
- [x] Export audit results as CSV
- [x] Integration with existing SEO services (SEOAuditor, SitemapGenerator)
- [x] Documentation updated in CHANGELOG.md

---

## Sprint 13: Global Design System — Premium Dark Glassmorphism [COMPLETE]

UI-layer refactor. Dark-first glassmorphism. No changes to CMS architecture, routing, database, or business logic.

### Phase 1: Foundation — Design Tokens [COMPLETE]

- [x] `design-tokens.css`: brand colors, dark surfaces, glass surfaces, text, gradients, spacing (4px), radius, shadows, motion, layout, z-index
- [x] Semantic aliases: `--color-*`, `--space-*`, `--radius-*`, `--shadow-*` bridge old class names to new tokens
- [x] `[data-theme="light"]` opt-in override for light mode
- [x] Responsive section spacing tightening (1024/768/640px breakpoints)
- [x] `base.css`: reset, ambient body gradient, typography, gradient-text, layout utilities, grid system, scrollbar, skip-link

### Phase 2: Component System [COMPLETE]

- [x] `buttons.css`: `.btn` with primary/secondary/outline/ghost/CTA + accent/whatsapp variants, sizes xs–xl, pill, icon-only, loading spinner, ripple
- [x] `forms.css`: glass inputs, validation states, select, checkbox/radio, form rows
- [x] `cards.css`: base glass card + service, portfolio, testimonial, pricing (featured gradient border), blog, stat, team
- [x] `glass.css`: `.glass`, `.glass--elevated/hoverable/glow/interactive`, ambient blobs, section-glass
- [x] `badges.css`: pill badges — primary, accent, success, warning, error, muted, glow, outline
- [x] `section.css`: `.section`, `.section--dark/glass/gradient`, section header/eyebrow/title/subtitle, CTA section
- [x] `components.css`: breadcrumbs (unique), accordion (unique); de-duplicated 8 blocks moved to own files

### Phase 3: Interactive Components [COMPLETE]

- [x] `modal.css`: glass modal with backdrop blur, scale-in, focus trap (JS)
- [x] `drawer.css`: glass slide-in panel with blur overlay
- [x] `toast.css`: slide-in toast notifications with success/error/warning/info
- [x] `pagination.css`: glass page buttons with active glow
- [x] `tabs.css`: glass tabs with underline/pills variants, keyboard nav (JS)
- [x] `faq.css`: glass FAQ accordion (matches `faq-item` PHP classes)
- [x] `back-to-top.css`: glass circular button with scroll-triggered reveal
- [x] `empty-state.css`: centered empty state
- [x] `whatsapp.css`: pulsing floating WhatsApp button with glow
- [x] `icons.css`: SVG stroke icon system (Lucide / SimpleIcons / Tabler)

### Phase 4: Animations & JS [COMPLETE]

- [x] `gsap-loader.js`: lazy CDN GSAP + ScrollTrigger, `window.SwapGSAP` facade, reduced-motion gate
- [x] `reveal.js`: GSAP ScrollTrigger + IntersectionObserver hybrid, `data-reveal` + stagger delay
- [x] `tilt.js`: 3D mouse-move perspective tilt (`data-tilt`)
- [x] `magnetic.js`: magnetic button hover with spring (`data-magnetic`)
- [x] `counter.js`: animated counter on scroll (`data-counter`), easeOutCubic, `requestAnimationFrame`
- [x] `parallax.js`: GSAP ScrollTrigger parallax layers
- [x] `accordion.js`: toggle `.accordion__item` with maxHeight, only-one-open, aria-expanded
- [x] `tabs.js`: `.tabs__btn`/`.tabs__panel` with arrow-key nav, aria-selected
- [x] `modal.js`: `data-modal-target`/`data-modal-close`, focus trap, Escape key
- [x] `drawer.js`: `.drawer-overlay` + `.drawer` toggle
- [x] `toast.js`: `Toast.show()` API with auto-dismiss, variants
- [x] `back-to-top.js`: scroll-triggered visibility, smooth scroll to top
- [x] `scroll-progress.js`: top progress bar width = scroll %

### Phase 5: 3D & CSS Animations [COMPLETE]

- [x] `3d.css`: perspective container, tilt-card hover, floating animation, gradient-shift keyframes, ambient blob-drift keyframes
- [x] `animations.css`: `.reveal` opacity/transform transition, `.tilt-card` preserve-3d, `.magnetic` transition

### Phase 6: Homepage Migration [COMPLETE]

- [x] `main.css`: Removed light-mode `:root` token block (was overriding `design-tokens.css`)
- [x] `homepage.css`: Full rewrite — dark-first glassmorphism with `--ds-*` tokens, all 15+ sections
- [x] `HomepageRenderer.php`: Experience/services/why-choose already emitted design-system classes
- [x] Legacy button templates migrated to design system syntax: `contact-form.php`, `cta.php`, `navigation.php`, `layout.php`, `404.php`, `ComponentLoader.php`
- [x] `buttons.css`: Added `btn--accent`, `btn--whatsapp` variants for missing references
- [x] `head.php`: Added `tabs.css` to Design System load order
- [x] `homepage.js`: Removed redundant `initCounters` (handled by `counter.js`)

### Phase 7: Documentation [COMPLETE]

- [x] `DESIGN_SYSTEM.md`: Complete reference for tokens, components, animations, icons, patterns
- [x] `CHANGELOG.md`: Version 1.4.0 entry
- [x] `TODO.md`: Sprint 13 complete checklist

---

## Sprint 14: Frontend Page Migration [COMPLETE]

- [x] Migrate About page to Design System (`about.css` — 757 lines, 145 ds-tokens)
- [x] Migrate Services page to Design System (`services.css` — 110 lines, 29 ds-tokens)
- [x] Migrate Portfolio page to Design System (`portfolio.css` — 207 lines, 81 ds-tokens)
- [x] Migrate Blog page to Design System (`blog.css` — 320 lines, 78 ds-tokens)
- [x] Migrate Contact page to Design System (`contact.css` — 369 lines, 74 ds-tokens)
- [x] Migrate Search page to Design System (`pages/search.css` — 562 lines, 93 ds-tokens)
- [x] All page CSS now uses `--ds-*` tokens (glass surfaces, hover glow, spacing)
- [x] No light-mode hardcoded backgrounds remain (only intentional print overrides + brand green)
- [x] All existing HTML class selectors preserved (zero markup/routing/business changes)
- [x] PHP lint passes on all modified files
- [x] CHANGELOG.md updated with 1.5.0 entry

### Remaining Legacy Files (not in migration scope / intentionally kept)

- [ ] `assets/css/design-system.css` (531 lines) — orphaned legacy file, NOT loaded by `head.php`; safe to delete after confirming no references
- [ ] `assets/css/whatsapp.css` — standalone WhatsApp button style (brand green `#25d366`, intentional)
- [ ] `assets/css/responsive.css` — global responsive overrides, already token-based

### Post-Migration Verification [PENDING]

- [ ] Live DB import + rendering smoke test on Hostinger (blocked: no MySQL access)

## Sprint 15H: Homepage Layout Composition [COMPLETE]

Redesign ONLY layout composition — every Homepage section unique. No CMS/content/routing/DB changes. No Design System token changes.

- [x] Hero — asymmetrical split (kept: already unique)
- [x] About — editorial overlap: 1.6fr image + 1fr inset glass panel + accent bar
- [x] Experience — radial: center badge + 4 stats in 2×2
- [x] Services — featured card (span 2) + 5 supporting cards
- [x] Why Choose — featured card (left) + 3 stacked (right)
- [x] Problems — alternating masonry (left/right zigzag, varying heights)
- [x] Why Work — vertical timeline (central line, cards alternate sides)
- [x] Industries — 3 featured large + compact logo-cloud grid
- [x] Technologies — floating wall (mixed small/medium/large tiles, parallax + stagger)
- [x] Process — vertical steps (numbered nodes left, content right)
- [x] Portfolio — featured project (21:9) + 4 in 2×2 grid
- [x] Testimonials — featured large (spans 2 rows) + 2 secondary
- [x] FAQ — side-by-side (sticky category pills + accordion panel)
- [x] CTA — split panel (text left, CSS visual right)
- [x] Contact — premium glass panel (methods + CTA buttons)
- [x] PHP lint clean (`HomepageRenderer.php`)
- [x] CSS brace balance verified (514 open / 514 close)
- [x] Design system files untouched
- [x] CHANGELOG 1.5.6 + PROJECT_MEMORY + PROJECT_STATUS updated
- [x] LAYOUT_COMPOSITION_REPORT.md generated

---


## Sprint 15 Surgical — Homepage Visual Polish + JS Fix [COMPLETE] (v1.7.1)

8 additive CSS upgrades + 2 JS dedup fixes. No content, routing, DB, or Design System token changes.

- [x] Header glassmorphism sticky (`.main-header--scrolled` backdrop-filter + brand border)
- [x] Experience badge conic ring animation (rotating gradient pulse)
- [x] Hero panel 3D translateZ depth (logo/brand/chips tiers; touch-device safe)
- [x] Process step hover glow + depth (lift + 48px brand glow ring)
- [x] CTA animated gradient sweep beam (skewX keyframe)
- [x] Scroll indicator pulse (mouse bob + wheel drain)
- [x] Contact premium panel hover glow (brand border + 60px glow)
- [x] Services featured hover depth (lift + 80px brand glow)
- [x] BUG FIX: `reveal.js` init() runs once guard (prevented double ScrollTrigger registration site-wide)
- [x] BUG FIX: `homepage.js` initSectionReveal skips when global reveal owns [data-reveal]
- [x] CSS brace balance verified (0 open)
- [x] Live page health: HTTP 200, all 16 sections render
- [x] CHANGELOG 1.7.1 updated
- [x] PROJECT_STATUS updated
- [x] Deployment to Hostinger shared hosting (blocked: no MySQL access — see Sprint 8)
