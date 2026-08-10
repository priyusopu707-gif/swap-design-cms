# Swap Design CMS -- Project Analysis Report

## 1. Project Overview

| Attribute | Value |
|---|---|
| **Name** | Swap Design CMS |
| **Purpose** | Custom CMS for Swap Design creative agency website |
| **Backend** | PHP 8+ (no frameworks, no Composer) |
| **Database** | MySQL 5.7+ / MariaDB 10.3+ (InnoDB, utf8mb4) |
| **Frontend** | HTML5, CSS3, vanilla JavaScript ES6+ (no npm, no bundlers) |
| **Server** | Apache 2.4 with mod_rewrite |
| **Target Host** | Hostinger Premium Shared Hosting |
| **Total Files** | 111 PHP files + CSS/JS/SQL |
| **Total Tables** | 58 InnoDB tables |
| **Lines of Code** | ~40,000+ (estimated across PHP, SQL, CSS, JS) |
| **Current Sprints** | 1-4 complete; About, Contact/Leads, Blog, Search, SEO, Users modules shipped (see TODO.md) |

---

## 2. Folder Structure

```
swap-design/
├── admin/                          # 28 pages + 8 AJAX handlers
│   ├── index.php                   # Dashboard
│   ├── login.php / logout.php      # Auth
│   ├── pages.php                   # Page CRUD
│   ├── content-types.php           # Custom content type builder
│   ├── entries.php                 # Content entry manager
│   ├── sections.php                # Reusable section library
│   ├── layouts.php                 # Layout template builder
│   ├── services.php                # Service listing
│   ├── services-edit.php           # 13-tab service editor
│   ├── portfolio.php               # Portfolio listing
│   ├── portfolio-edit.php          # 12-tab portfolio editor
│   ├── homepage.php                # 11-section drag-drop editor
│   ├── about.php                   # 12-section drag-drop editor + revisions
│   ├── contacts.php                # 6-section drag-drop editor
│   ├── leads.php                   # Lead/CRM dashboard
│   ├── email-settings.php          # SMTP config + test email
│   ├── email-templates.php         # Email template editor
│   ├── blog.php                    # Dual-mode blog list + rich editor
│   ├── search.php                  # Search analytics dashboard
│   ├── navigation.php              # Menu builder
│   ├── footer.php                  # Footer links + copyright
│   ├── blocks.php                  # Global block library
│   ├── media.php                   # Media library
│   ├── settings.php                # Site settings
│   ├── theme.php                   # Theme customization
│   ├── whatsapp.php                # WhatsApp analytics
│   ├── users.php                   # User management (admin-gated)
│   ├── includes/                   # Admin bootstrap: init, header, footer, sidebar, topbar
│   ├── ajax/                       # homepage, services, portfolio, about, contacts, blog, search, leads
│   └── assets/                     # admin.css, admin.js + specialized CSS/JS files
│
├── includes/                       # Core engine (59 files, 30+ classes)
│   ├── config/                     # site.php, environment.php, error-handler.php, database.php
│   ├── content/                    # 22 classes: DynamicRouter, RenderingEngine, Managers & Renderers
│   ├── auth/                       # Auth.php (login, logout, rate limit, remember-me)
│   ├── blocks/                     # BlockEngine.php, ComponentLoader.php
│   ├── media/                      # FileUploader, ImageOptimizer, MediaLibrary
│   ├── navigation/                 # NavigationManager.php
│   ├── settings/                   # SettingsManager.php
│   ├── themes/                     # ThemeEngine.php
│   ├── integrations/               # WhatsAppManager.php
│   ├── search/                     # SearchIndexer, SearchManager, SearchRenderer
│   ├── seo/                        # SitemapGenerator, RobotsManager, SEOAuditor
│   ├── functions/                  # helpers, sanitize, security, seo, logger
│   ├── components/                 # header, footer, navigation, cta, contact-form, search
│   ├── Session.php, Database.php, layout.php, loader.php, head.php, breadcrumb.php, scripts.php
│
├── api/                            # contact.php, whatsapp-track.php
├── ajax/                           # Frontend AJAX: contact.php, search.php
├── assets/                         # 20 CSS files, 15 JS files
├── database/
│   └── schema.sql                  # 1230 lines, 58 tables (all CREATE TABLE IF NOT EXISTS)
├── pages/
│   └── 404.php
├── cache/, logs/, uploads/         # Writable directories
├── index.php                       # Front-end entry point
├── .htaccess                       # Rewrite rules, security, caching
└── Documentation                   # README, PROJECT_MEMORY, ARCHITECTURE, DATABASE_SCHEMA, ROUTES, CHANGELOG, TODO
```

---

## 3. Completed Modules

### 3.1 Core Infrastructure (Sprint 1)

| Component | File(s) | Status |
|---|---|---|
| Environment config | `includes/config/environment.php` | Done |
| Error/exception handler | `includes/config/error-handler.php` | Done |
| Session manager | `includes/Session.php` | Done |
| Database (PDO singleton) | `includes/Database.php` (233 lines) | Done |
| Logging system | `includes/functions/logger.php` | Done |
| Security helpers | `includes/functions/security.php` | Done |
| Authentication | `includes/auth/Auth.php` | Done |
| Admin bootstrap + auth guard | `admin/includes/init.php` (71 lines) | Done |
| Admin login/logout | `admin/login.php`, `admin/logout.php` | Done |
| Admin layout shell | `admin/includes/header.php`, `sidebar.php`, `topbar.php`, `footer.php` | Done |
| Admin dashboard | `admin/index.php` | Done |

### 3.2 Core Services + Admin Pages (Sprint 2)

| Component | File(s) | Notes |
|---|---|---|
| SettingsManager | `includes/settings/SettingsManager.php` | Key-value CRUD, JSON auto-handling |
| ThemeEngine | `includes/themes/ThemeEngine.php` | Reads theme.* settings, generates `:root` CSS |
| NavigationManager | `includes/navigation/NavigationManager.php` | Menu CRUD, parent/child tree, reorder |
| BlockEngine | `includes/blocks/BlockEngine.php` | 15 block types, CRUD, schedule/visibility |
| ComponentLoader | `includes/blocks/ComponentLoader.php` | Component registry, per-type renderers |
| FileUploader | `includes/media/FileUploader.php` | Secure upload, SHA-256 filenames, SVG sanitize |
| ImageOptimizer | `includes/media/ImageOptimizer.php` | 5 sizes via GD, WebP, dominant color |
| MediaLibrary | `includes/media/MediaLibrary.php` | Upload orchestration, soft-delete, folders |
| Theme Settings | `admin/theme.php` | Color pickers, typography, layout, CSS regen |
| Site Settings | `admin/settings.php` | Tabbed business profile + website settings |
| Navigation Manager | `admin/navigation.php` | Tree view, add/edit/delete/reorder |
| Footer Manager | `admin/footer.php` | Grouped links modal, copyright text |
| Global Blocks | `admin/blocks.php` | Card grid, search/filter, CRUD + duplicate |
| Media Library | `admin/media.php` | Drag-drop upload, folders, metadata modal |

### 3.3 Dynamic Content Engine (Sprint 3)

| Component | File(s) | Lines | Status |
|---|---|---|---|
| ContentTypeEngine | `includes/content/ContentTypeEngine.php` | ~ | Done |
| ContentEntryManager | `includes/content/ContentEntryManager.php` | ~ | Done |
| SectionManager | `includes/content/SectionManager.php` | ~ | Done |
| LayoutManager | `includes/content/LayoutManager.php` | ~ | Done |
| SlugManager | `includes/content/SlugManager.php` | ~ | Done |
| DynamicRouter | `includes/content/DynamicRouter.php` | 265 | Done |
| RenderingEngine | `includes/content/RenderingEngine.php` | 697 | Done |
| PageManager | `includes/content/PageManager.php` | ~ | Done |
| HomepageManager | `includes/content/HomepageManager.php` | ~ | Done |
| HomepageRenderer | `includes/content/HomepageRenderer.php` | 862 | Done |
| ServiceManager | `includes/content/ServiceManager.php` | ~ | Done |
| ServiceRenderer | `includes/content/ServiceRenderer.php` | 545 | Done |
| PortfolioManager | `includes/content/PortfolioManager.php` | ~ | Done |
| PortfolioRenderer | `includes/content/PortfolioRenderer.php` | 522 | Done |
| WhatsAppManager | `includes/integrations/WhatsAppManager.php` | ~ | Done |

### 3.4 Admin Pages (All Implemented -- 28 pages)

| Page | Status | Features |
|---|---|---|
| Pages | Done | CRUD, layout assignment, zone section management |
| Content Types | Done | Field schema JSON editor, system types |
| Entries | Done | Dynamic fields from schema |
| Sections | Done | 6 types, zone assignment, scheduling |
| Layouts | Done | Named zones, template builder |
| Services | Done | Listing + 13-tab editor |
| Portfolio | Done | Listing + 12-tab editor |
| Homepage | Done | 11-section drag-drop, 800ms auto-save |
| About | Done | 12-section drag-drop, inline editing, auto-save, revisions |
| Contacts | Done | 6-section drag-drop, inline forms, repeater FAQ, auto-save |
| Leads | Done | Lead/CRM dashboard: status tabs, filters, CSV export, notes, email history |
| Email Settings | Done | SMTP config, test email, notification toggles |
| Email Templates | Done | Template editor (subject/HTML/text) |
| Blog | Done | Dual-mode list + rich editor, toolbar, taxonomy, SEO, revisions |
| Search | Done | Stats, analytics tables, rebuild/clear/toggle/export |
| Navigation | Done | Multi-level menu builder, drag-drop reorder |
| Footer | Done | Grouped links, copyright, social links |
| Blocks | Done | Card grid, search/filter, CRUD + duplicate, scheduling |
| Media | Done | Drag-drop upload, folders, metadata modal |
| Settings | Done | Tabbed business profile + website settings |
| Theme | Done | Color pickers, typography, layout, CSS regen |
| WhatsApp | Done | Settings + analytics dashboard |
| Users | Done | Create/edit, role/status toggle, password reset (admin only) |

### 3.5 Database Schema (58 Tables)

Core tables 1-40 (documented in DATABASE_SCHEMA.md, sections 1-40): contact_messages, pages, portfolio_items, site_settings, users, login_attempts, remember_tokens, navigation_menu, footer_links, footer_settings, global_blocks, global_block_usage, media_library, media_folders, global_components, content_types, content_entries, layouts, sections, page_sections, url_redirects, whatsapp_page_overrides, whatsapp_clicks, homepage_sections, services (42 fields), service_features, service_benefits, service_process_steps, service_faqs, service_portfolio, service_testimonials, service_related_blocks, portfolio_gallery, portfolio_testimonials, portfolio_related_services, portfolio_faqs, portfolio_related_blocks, search_index, search_logs, search_result_clicks.

Extended module tables 41-58 (DATABASE_SCHEMA.md, sections 41-58):

- **About (41-44):** about_sections, about_revisions, about_related_portfolio, about_related_blocks
- **Contact & Leads (45-49):** contact_sections, leads, lead_notes, email_templates, email_log
- **Blog (50-56):** blog_categories, blog_tags, blog_posts, blog_post_categories, blog_post_tags, blog_revisions, blog_relationships
- **Portfolio (57-58):** portfolio_revisions, portfolio_related_blog

---

## 4. Completed Extended Modules

### 4.1 About Module [DONE]

| Component | Status | Notes |
|---|---|---|
| AboutManager | Done | 12-section CRUD, JSON config, seedDefaults, revision history |
| AboutRenderer | Done | Full page render, Person schema, dynamic data loading |
| Admin about.php + ajax/about.php | Done | Drag-drop reorder, inline editing, auto-save, revisions |
| Frontend about.css / about.js | Done | 12 sections, responsive, FAQ accordion, counters |
| Database | Done | about_sections, about_revisions, about_related_portfolio, about_related_blocks |

### 4.2 Contact & Lead Management Module [DONE]

| Component | Status | Notes |
|---|---|---|
| ContactManager / ContactRenderer | Done | 6-section CRUD + full render with ContactPage schema |
| LeadManager | Done | 7-status workflow, search/filter, pagination, CSV export, notes |
| EmailManager | Done | SMTP config, template engine, admin/user emails, email logging |
| Admin contacts.php / leads.php | Done | Section editor + lead dashboard/detail |
| Admin email-settings.php / email-templates.php | Done | SMTP config UI, template editor |
| Public ajax/contact.php | Done | Active form handler: CSRF, honeypot, rate limit, uploads, leads, email |
| Database | Done | contact_sections, leads, lead_notes, email_templates, email_log |

### 4.3 Blog & Knowledge Base Module [DONE]

| Component | Status | Notes |
|---|---|---|
| BlogManager / BlogRenderer | Done | 4-status workflow, taxonomy, revisions, related content, reading time |
| Admin blog.php + ajax/blog.php | Done | Dual-mode list + rich editor, toolbar, auto-save, SEO, revisions |
| Frontend blog.css / blog.js | Done | Listing grid, TOC, share, author box, prev/next, related |
| Routing | Done | 5 blog routes in DynamicRouter, 4 render methods |
| Database | Done | blog_posts, blog_categories, blog_tags, 2 pivot tables, blog_revisions, blog_relationships |

### 4.4 Advanced Search Module [DONE]

| Component | Status | Notes |
|---|---|---|
| SearchIndexer / SearchManager / SearchRenderer | Done | FULLTEXT + LIKE fallback, filters, sorts, pagination, suggestions, analytics |
| Public ajax/search.php | Done | suggest, popular, record_click (CSRF + rate limited) |
| Admin search.php + ajax/search.php | Done | Stat cards, rebuild/clear/toggle/export, 4 analytics tables |
| Header search component | Done | Live suggestions, keyboard nav, recent searches, mobile overlay |
| Auto-index hooks | Done | In BlogManager, ServiceManager, PortfolioManager, PageManager, ContentEntryManager, BlockEngine |
| Database | Done | search_index + search_logs + search_result_clicks + 12 search_* settings |

### 4.5 SEO Module [DONE]

| Component | Status | Notes |
|---|---|---|
| SitemapGenerator | Done | sitemap.xml + blog/services/portfolio/images child sitemaps, auto-regenerated |
| RobotsManager | Done | robots.txt generator respecting noindex |
| SEOAuditor | Done | Audit service implemented (admin UI pending) |
| Structured data | Done | Organization, WebSite+SearchAction, WebPage, CollectionPage, FAQPage, BreadcrumbList |
| Head meta | Done | Absolute canonical/og URLs, og image dimensions, twitter:url |

### 4.6 Frontend Integration (Sprint 4) [DONE]

| Task | Status | Notes |
|---|---|---|
| DB-driven navigation | Done | navigation.php reads from NavigationManager (falls back to site.php config) |
| DB-driven footer | Done | footer.php reads footer_links + SettingsManager (falls back to site.php) |
| ThemeEngine CSS integration | Done | head.php loads theme-generated.css between main.css and responsive.css |
| Homepage/portfolio/services templates | Done | Renderers produce full HTML from DB data |
| Content entry cards / dynamic lists | Done | Responsive card grids in assets/css/ |

---

## 5. Pending Work

### 5.1 Remaining Sprint 7 Items (partially done)

| Task | Priority | Notes |
|---|---|---|
| SEO audit dashboard UI | LOW | SEOAuditor service exists; needs an admin page |
| Per-page SEO meta fields UI | MEDIUM | Pages table has SEO columns; no dedicated admin SEO tab yet |
| Cross-browser testing | LOW | Pending manual pass |
| Accessibility audit (WCAG 2.1 AA) | MEDIUM | Skip-link + ARIA used, but no formal audit |
| Performance optimization (Core Web Vitals) | MEDIUM | Not measured on live hosting |
| Security audit | MEDIUM | Needs third-party review |
| Deployment to Hostinger | HIGH | No live deployment yet; DB import test blocked (no MySQL in dev env) |

### 5.2 Missing from TODO but Needed

| Task | Priority | Notes |
|---|---|---|
| **Automated tests** | HIGH | Zero tests exist. No PHPUnit, no integration tests, no JS tests. |
| **Content seeding** | HIGH | After installation, no content in any table. A seeder script or default content bundle is needed. |
| **Backup system** | MEDIUM | Mentioned in README roadmap, not in TODO.md. |
| **Dark mode toggle for admin** | LOW | Mentioned in README roadmap. |
| **Image picker integration** | MEDIUM | Homepage/blog editors have "Browse" buttons but media library picker is not fully connected in all editors. |
| **setup.php installer** | LOW | 8-step README install works, but a guided installer would reduce friction. |

### 5.3 Known Documentation/Code Drift (resolved in this audit)

- Docs previously claimed 37 tables / 77 PHP files / 19 admin pages; actual counts are **58 tables / 111 PHP files / 28 admin pages + 8 AJAX handlers**. DATABASE_SCHEMA.md, ROUTES.md, README.md, and this report have been reconciled to the implemented system (code is the source of truth for counts).
- `api/contact.php` and `includes/components/contact-form.php` form a legacy/basic contact path; the active Contact page uses `ajax/contact.php` via `assets/js/pages/contact.js`. Both remain valid (see ROUTES.md).

---

## 6. Risks

### 6.1 Critical Risks

1. **No automated tests (Critical)**
   - Zero unit tests, integration tests, or end-to-end tests
   - Any code change could silently break functionality
   - 111 PHP files with complex inter-dependencies - manual testing is not sustainable

2. **No content seeding mechanism (High)**
   - After running schema.sql, all tables are empty
   - No default content, no sample pages, no sample services
   - Site would render blank/error pages
   - Admin entry form would have nothing to edit

3. **Database credentials file is gitignored (Medium)**
   - `database.php` must be created manually on every install
   - Easy to forget during deployment
   - README covers this but it's a friction point

### 6.2 Architecture Risks

4. **Duplicate rendering paths (Medium)**
   - RenderingEngine has two rendering approaches:
     - `renderServicePage()` / `renderPortfolioArchive()` / `renderPortfolioSingle()` / `renderContentType()` / `renderHomepage()` each manually include head.php, header.php, navigation.php, footer.php, scripts.php
     - `renderPage()` uses `assembleLayout()` which does the same but with zone support
   - Every new page type duplicates the layout shell boilerplate instead of reusing assembleLayout()
   - This can lead to inconsistencies

5. **Shortcode rendering is a placeholder (Low)**
   - `RenderingEngine::renderShortcode()` outputs `<!-- shortcode: {$code} -->`
   - Section type "shortcode" exists but has no implementation

6. **Dual contact endpoints (Low)**
   - `/api/contact.php` (component path) and `/ajax/contact.php` (Contact page path) are separate handlers
   - Feature set diverges (the component path lacks CSRF/honeypot/lead capture)
   - Both are valid and documented; a future consolidation would reduce maintenance

### 6.3 Security Risks

7. **Contact messages viewer parity (Low)**
   - `contact_messages` table (used by the legacy component path) has no admin viewer; lead messages go to `leads` which has a full dashboard

### 6.4 Operational Risks

8. **No cache invalidation (Low)**
   - Cache directory exists but no caching logic is implemented
   - Could be a problem at scale but Hostinger shared hosting has resource limits

9. **Single admin account (Low)**
   - No self-service registration or password reset (users.php requires an existing admin login)
    - No self-service registration or password reset
    - Locked out? Edit database manually

---

## 7. Suggestions

### 7.1 Immediate Priorities (For v1.0 Deployment)

1. **Write a content seeder script**
   - Create `database/seed.php` that populates essential tables with demo content:
     - 1 admin user (or document the README INSERT)
     - 5-6 pages (Home, About, Services, Portfolio, Contact, 404)
     - 3-4 services with features, benefits, process, FAQs
     - 3-4 portfolio items with gallery
     - Default navigation menu items, footer links and copyright
     - Default homepage/about/contact/blog sections
   - This makes the project immediately usable after install

2. **Run the SEO audit + deploy**
   - Add the SEO audit dashboard admin page (SEOAuditor service already exists)
   - Deploy to Hostinger and run the post-deployment checklist in README

### 7.2 Completed Execution Items (from prior sprint plan)

- DB-driven navigation, footer, and theme CSS integration -- **done** (section 4.6)
- User management admin page (`admin/users.php`) -- **done**
- Blog module with categories/tags -- **done**
- Contact/lead management -- **done** (leads.php is the viewer/CRM)
- Search module with analytics -- **done**

### 7.3 Architecture Improvements

3. **Unify layout assembly in RenderingEngine**
   - Consolidate all page-type render methods to use `assembleLayout()`
   - Currently each renderer method (renderServicePage, renderPortfolioArchive, etc.) duplicates the head/header/nav/footer/scripts include chain
   - Create a `renderShell($content, $context)` method that wraps content with the standard shell

4. **Add PHPUnit tests**
   - Start with core classes: Database, Session, Auth
   - Add tests for DynamicRouter route resolution
   - Add tests for SlugManager uniqueness
   - Add tests for SettingsManager CRUD

5. **Consolidate the dual contact endpoints**
   - Point `includes/components/contact-form.php` at `ajax/contact.php` (or fold its features into the shared handler)
   - Deprecate `api/contact.php` after confirming no external consumers

### 7.4 Missing Features to Prioritize

6. **Per-page SEO meta fields UI** -- pages/services/portfolio/blog have SEO columns; add a dedicated admin SEO tab
7. **SEO audit dashboard** -- admin page wrapping the existing SEOAuditor service
8. **Self-service password change / reset** -- admin profile page
9. **Content seeding script** -- see 7.1.1

### 7.5 Operational

10. **Add `database.php.example`** - template with placeholder values for easy setup
11. **Create a deployment checklist** - document all Hostinger-specific steps (README has most of this)
12. **Add `composer.json` with PHPUnit only** - for development testing, not production dependency
