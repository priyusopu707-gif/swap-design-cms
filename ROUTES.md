# Swap Design CMS -- Application Routes

Complete reference documentation for every route and URL pattern in the Swap Design CMS.

---

## Routing Flow

```
Browser Request
    │
    ▼
┌──────────────────────────────────────────────────────────┐
│ .htaccess (Apache mod_rewrite)                           │
│                                                          │
│  1. Force HTTPS (301 redirect)                           │
│  2. Remove trailing slashes (301 redirect)               │
│  3. If the request IS a real file or directory:          │
│       → Serve directly (CSS, JS, images, PHP in admin/)  │
│  4. Otherwise:                                           │
│       → Rewrite to index.php?url={path}                  │
│  5. Block access: /includes/, /database/, /logs/,        │
│     /cache/, /.env, /.sql, /.log, /.md                   │
└──────────────────────┬───────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│ index.php (Frontend Entry Point)                         │
│                                                          │
│  1. Bootstrap (config, DB, session, auth, all managers)  │
│  2. Set security headers                                 │
│  3. DynamicRouter::resolve($url)                         │
│  4. If 301 redirect → send Location header, exit         │
│  5. If 404 → set http_response_code(404)                 │
│  6. RenderingEngine::render($context)                    │
│     └── Inject WhatsApp floating button before </body>   │
│  7. Output HTML                                          │
└──────────────────────────────────────────────────────────┘
```

### Resolution Order (DynamicRouter::resolve)

The router checks URLs in this priority sequence:

| Step | Check | Example Match |
|---|---|---|
| 1 | Homepage (`/`) | `/` |
| 2a | Content type archive (single segment matching a `content_types.slug`) | `/team`, `/news` |
| 2b | Portfolio archive (`/portfolio`) | `/portfolio` |
| 2c | Content type + entry slug (two segments: type-slug/entry-slug) | `/news/article-title` |
| 2d | Service detail (two segments: `/services/{slug}`) | `/services/web-design` |
| 2e | Portfolio detail (two segments: `/portfolio/{slug}`) | `/portfolio/project-name` |
| 3 | Page by exact slug match | `/about`, `/contact` |
| 4 | URL redirect lookup (`url_redirects` table) | Old slug → 301 to new slug |
| 5 | 404 Not Found | Anything unmatched |

---

## URL Standards

| Standard | Rule | Example |
|---|---|---|
| **Format** | Lowercase, hyphen-separated | `/web-design`, `/about-us` |
| **Trailing slashes** | Removed via 301 redirect | `/about/` → `/about` |
| **HTTPS** | Enforced via 301 redirect | `http://...` → `https://...` |
| **Query strings** | Passed through for filters, pagination | `/portfolio?category=web&page=2` |
| **Reserved prefixes** | `/admin/`, `/api/`, `/assets/`, `/includes/`, `/database/`, `/logs/`, `/cache/` | Served directly or blocked |
| **Slug uniqueness** | Slugs must be unique within their entity type; pages, services, and portfolio have separate slug namespaces | Page `/about` and Service `/about` do not conflict because `/about` routes as page first |

---

## Slug Rules

| Entity | Slug Format | Uniqueness Scope | Example | Generated From |
|---|---|---|---|---|
| **Pages** | `/` or `/{slug}` | Unique across `pages.slug` | `/about-us`, `/` (homepage) | User-defined |
| **Services** | `/services/{slug}` | Unique across `services.slug` | `/services/web-design` | User-defined |
| **Portfolio** | `/portfolio/{slug}` | Unique across `portfolio_items.slug` | `/portfolio/ecommerce-redesign` | User-defined |
| **Content Type Archive** | `/{content_type_slug}` | Must not conflict with pages or reserved prefixes | `/team` | `content_types.slug` |
| **Content Entry** | `/{content_type_slug}/{entry_slug}` | Unique within content type | `/team/john-doe` | `content_entries.slug` |

---

## Frontend Routes

### Homepage

| URL | Controller | Template/View | Access | Description |
|---|---|---|---|---|
| `/` | `index.php` → `DynamicRouter` → `RenderingEngine::renderHomepage()` | `HomepageRenderer::render()` with `includes/head.php`, `includes/header.php`, `includes/navigation.php`, `includes/footer.php`, `includes/scripts.php` | Public | Renders all 11 enabled homepage sections from `homepage_sections` table |

### Static Pages

| URL Pattern | Controller | Template/View | Access | Description |
|---|---|---|---|---|
| `/{slug}` | `index.php` → `DynamicRouter::resolvePage()` | `RenderingEngine::renderPage()` with assigned `layout` and `page_sections` per zone | Public | Any page with `status = 'published'` and matching slug |

### Services

| URL Pattern | Controller | Template/View | Access | Description |
|---|---|---|---|---|
| `/services/{slug}` | `index.php` → `DynamicRouter::resolveService()` | `RenderingEngine::renderServicePage()` → `ServiceRenderer::renderFromData()` with standard layout wrappers | Public | Service detail page with hero, features, benefits, process, FAQs, portfolio, testimonials, CTA, contact, SEO, blocks |

### Portfolio

| URL Pattern | Controller | Template/View | Access | Description |
|---|---|---|---|---|
| `/portfolio` | `index.php` → `DynamicRouter::resolvePortfolioArchive()` | `RenderingEngine::renderPortfolioArchive()` → `PortfolioRenderer::renderListing()` with standard layout wrappers | Public | Portfolio listing with category filters, search, pagination, card grid |
| `/portfolio/{slug}` | `index.php` → `DynamicRouter::resolvePortfolioSingle()` | `RenderingEngine::renderPortfolioSingle()` → `PortfolioRenderer::renderSingle()` with standard layout wrappers | Public | Portfolio project detail with hero, overview, gallery, solution, results, testimonials, related services, related projects, FAQ, CTA, blocks |

### Content Types (Dynamic)

| URL Pattern | Controller | Template/View | Access | Description |
|---|---|---|---|---|
| `/{content_type_slug}` | `index.php` → `DynamicRouter::resolveContentTypeArchive()` | `RenderingEngine::renderContentType()` with `content_types.list_template` or default archive | Public | Archive listing for a content type |
| `/{content_type_slug}/{entry_slug}` | `index.php` → `DynamicRouter::resolveContentEntry()` | `RenderingEngine::renderContentType()` with `content_types.single_template` or default single | Public | Single entry detail for a content type |

### About Page
| `/about-us` | index.php -> DynamicRouter::resolveAboutPage() | RenderingEngine::renderAboutPage() -> AboutRenderer | Public | Dynamic about page with 12 sections from about_sections table |
| `/about` | index.php -> DynamicRouter::resolveAboutPage() | RenderingEngine::renderAboutPage() -> AboutRenderer | Public | Legacy alias for /about-us (301 or direct match) |

### Contact Page
| `/contact-us` | index.php -> DynamicRouter::resolveContactPage() | RenderingEngine::renderContactPage() -> ContactRenderer | Public | Dynamic contact page with 6 sections and lead capture form |
| `/contact` | index.php -> DynamicRouter::resolveContactPage() | RenderingEngine::renderContactPage() -> ContactRenderer | Public | Legacy alias for /contact-us |
| `/ajax/contact.php` (POST) | `ajax/contact.php` | JSON | Public | **Active contact form handler** -- called by `assets/js/pages/contact.js` (lines 151, 185). Validates with CSRF + honeypot, IP rate limit (3/15min), optional file upload, saves to `leads`, sends admin + user emails via EmailManager |

**Endpoint note:** The contact page form submits to `/ajax/contact.php` (the feature-rich handler). The older `/api/contact.php` endpoint remains valid for the reusable `contact-form` component (`includes/components/contact-form.php`, registered via `ComponentLoader`) and is documented under API Routes; it is not used by the Contact page renderer.

### Blog
| `/blog` | index.php -> DynamicRouter::resolveBlogListing() | RenderingEngine::renderBlogListing() -> BlogRenderer | Public | Blog listing with paginated post cards |
| `/blog/{slug}` | index.php -> DynamicRouter::resolveBlogPost() | RenderingEngine::renderBlogPost() -> BlogRenderer | Public | Single blog post with TOC, share, author box, related posts. Canonical URL is `https://swapdesign.com/blog/{slug}/` |
| `/website/{slug}` | index.php -> DynamicRouter::resolveWebsitePost() | RenderingEngine::renderBlogPost() -> BlogRenderer | Public | Website-category blog post. Canonical URL is `https://swapdesign.com/website/{slug}/` |
| `/blog/category/{slug}` | index.php -> DynamicRouter::resolveBlogCategory() | RenderingEngine::renderBlogCategory() -> BlogRenderer | Public | Category archive with paginated posts |
| `/blog/tag/{slug}` | index.php -> DynamicRouter::resolveBlogTag() | RenderingEngine::renderBlogTag() -> BlogRenderer | Public | Tag archive with paginated posts |

### Static & Service Pages
| `/privacy-policy` | index.php -> DynamicRouter::resolvePageBySlug() | RenderingEngine::renderPage() | Public | Privacy policy page |
| `/services/{slug}` | index.php -> DynamicRouter::resolveService() | RenderingEngine::renderServicePage() -> ServiceRenderer | Public | Service detail page (logo-and-branding-design, graphic-design, ui-ux-design, website-design-and-development, digital-marketing, website-maintenance-packages) |
| `/our-work` | index.php -> DynamicRouter::resolvePortfolioArchive() | RenderingEngine::renderPortfolioArchive() | Public | Portfolio/our-work listing |
| `/logo-branding-design-work` | index.php -> DynamicRouter | RenderingEngine | Public | Portfolio category: logo & branding work |
| `/graphic-design-work` | index.php -> DynamicRouter | RenderingEngine | Public | Portfolio category: graphic design work |
| `/website-design-development-work` | index.php -> DynamicRouter | RenderingEngine | Public | Portfolio category: website design work |
| `/a-modest-story-branding-design` | index.php -> DynamicRouter | RenderingEngine | Public | Portfolio single project |
| `/silent-wheel-branding-design` | index.php -> DynamicRouter | RenderingEngine | Public | Portfolio single project |

### Search

| URL Pattern | Controller | Template/View | Access | Description |
|---|---|---|---|---|
| `/search` | `index.php` → `DynamicRouter::resolveSearch()` | `RenderingEngine::renderSearch()` → `SearchRenderer` with standard layout wrappers | Public | Full search results page with live AJAX suggestions, filters (type/category/tag/date/featured), 4 sort modes, keyword highlighting, pagination, did-you-mean, popular/latest fallbacks, related results |
| `/ajax/search.php?action=suggest` | `ajax/search.php` | JSON | Public | Live suggestion dropdown (rate-limited, min query length) |
| `/ajax/search.php?action=popular` | `ajax/search.php` | JSON | Public | Popular searches for empty dropdown |
| `/ajax/search.php` (POST `record_click`) | `ajax/search.php` | JSON | Public | Log a result click (CSRF protected, rate-limited) |

| URL | Controller | Template/View | Access | Description |
|---|---|---|---|---|
| Any unmatched URL | `index.php` → `DynamicRouter` returns 404 | `RenderingEngine::render404()` → `pages/404.php` or inline fallback | Public | Returns HTTP 404 with user-friendly page |

### Static Assets (Served Directly by Apache)

| URL Pattern | Path | Access | Description |
|---|---|---|---|
| `/assets/css/*` | `assets/css/` | Public | Stylesheets |
| `/assets/js/*` | `assets/js/` | Public | JavaScript files |
| `/assets/images/*` | `assets/images/` | Public | Static images and icons |
| `/assets/fonts/*` | `assets/fonts/` | Public | Custom web fonts |
| `/uploads/*` | `uploads/` | Public | User-uploaded media files |
| `/robots.txt` | `robots.txt` | Public | Search engine crawl rules |
| `/sitemap.xml` | `sitemap.xml` | Public | XML sitemap index |
| `/post-sitemap.xml` | `post-sitemap.xml` | Public | Blog posts sitemap (website + blog categories) |
| `/page-sitemap.xml` | `page-sitemap.xml` | Public | Static pages + services sitemap |
| `/local-sitemap.xml` | `local-sitemap.xml` | Public | Local business sitemap |
| `/locations.kml` | `locations.kml` | Public | Google Earth/Maps locations file |
| `/sitemap-services.xml` | `sitemap-services.xml` | Public | Services sitemap |
| `/sitemap-portfolio.xml` | `sitemap-portfolio.xml` | Public | Portfolio sitemap |
| `/sitemap-images.xml` | `sitemap-images.xml` | Public | Image sitemap |
| `/site.webmanifest` | `site.webmanifest` | Public | PWA manifest |

---

## Admin Routes

All admin routes require authentication. Unauthenticated users are redirected to `/admin/login.php`.

### Authentication

| URL | Controller / File | Template/View | Access | Middleware | Description |
|---|---|---|---|---|---|
| `/admin/login.php` | `admin/login.php` | Inline HTML + `admin/includes/header.php`, `admin/includes/footer.php` (light) | Public | CSRF token, rate limiting | Admin login form with email + password + remember-me |
| `/admin/logout.php` | `admin/logout.php` | Redirect to login | Authenticated | Session destroy, remember-token cleanup | Logs out and redirects to login page |

### Dashboard & Core

| URL | Controller / File | Template/View | Access | Description |
|---|---|---|---|---|
| `/admin/index.php` | `admin/index.php` | `admin/includes/header.php`, `admin/includes/sidebar.php`, `admin/includes/footer.php` | Admin, Editor | Dashboard with stat cards and quick actions |
| `/admin/settings.php` | `admin/settings.php` | Admin layout + inline form | Admin | Site-wide settings (branding, SEO defaults, social links, contact info) |
| `/admin/theme.php` | `admin/theme.php` | Admin layout + inline form | Admin | Theme customization (logo, favicon, colors, typography, layout) |

### Content Management

| URL | Controller / File | Template/View | Access | Description |
|---|---|---|---|---|
| `/admin/pages.php` | `admin/pages.php` | Admin layout + inline table | Admin, Editor | Page management -- list, create, edit, delete, status toggle |
| `/admin/content-types.php` | `admin/content-types.php` | Admin layout + inline table | Admin | Custom content type definitions with field schemas |
| `/admin/entries.php` | `admin/entries.php` | Admin layout + inline table | Admin, Editor | Content entries CRUD with type filter |
| `/admin/sections.php` | `admin/sections.php` | Admin layout + inline table | Admin, Editor | Reusable page sections management |
| `/admin/layouts.php` | `admin/layouts.php` | Admin layout + inline table | Admin | Page layout templates with zone definitions |

### Services Module

| URL | Controller / File | Template/View | Access | Description |
|---|---|---|---|---|
| `/admin/services.php` | `admin/services.php` | Admin layout + inline table, drag-drop reorder | Admin, Editor | Service listing with search, category/status filters, bulk actions, duplicate, delete |
| `/admin/services-edit.php` | `admin/services-edit.php` + `admin/ajax/services.php` | Admin layout + 13-tab editor | Admin, Editor | Service editor with hero, overview, features, benefits, process, FAQs, portfolio links, testimonials, CTA, contact, SEO, blocks |

### Portfolio Module

| URL | Controller / File | Template/View | Access | Description |
|---|---|---|---|---|
| `/admin/portfolio.php` | `admin/portfolio.php` | Admin layout + inline table, drag-drop reorder | Admin, Editor | Portfolio listing with search, category/status filters, bulk actions, duplicate, delete, featured toggle |
| `/admin/portfolio-edit.php` | `admin/portfolio-edit.php` + `admin/ajax/portfolio.php` | Admin layout + 12-tab editor | Admin, Editor | Portfolio editor with general, hero, overview, solution, results, gallery, testimonials, services, FAQ, CTA, SEO, blocks |

### Homepage

| URL | Controller / File | Template/View | Access | Description |
|---|---|---|---|---|
| `/admin/homepage.php` | `admin/homepage.php` + `admin/ajax/homepage.php` | Admin layout + drag-drop section list, inline forms, device preview | Admin, Editor | Homepage section editor (11 sections) with drag-drop reorder, enable/disable toggles, 800ms debounced auto-save |
| `/admin/about.php` | `admin/about.php` + `admin/ajax/about.php` | Admin layout + drag-drop section list, inline forms, revision history | Admin, Editor | About page editor (12 sections) with drag-drop reorder, enable/disable, auto-save, revision history, repeater fields |
| `/admin/contacts.php` | `admin/contacts.php` + `admin/ajax/contacts.php` | Admin layout + drag-drop section list, inline forms | Admin, Editor | Contact page editor (6 sections) with drag-drop reorder, toggles, auto-save, repeater fields |
| `/admin/leads.php` | `admin/leads.php` | Admin layout + list/detail view | Admin, Editor | Lead dashboard with status tabs, search/filter, CSV export, detail view with notes and email history |
| `/admin/search.php` | `admin/search.php` + `admin/ajax/search.php` | Admin layout + stat cards, maintenance actions, analytics tables | Admin, Editor | Search dashboard: total/unique/zero-result searches, indexed items, popular & zero-result keywords, most-viewed results, rebuild index, clear logs, toggle logging, CSV export |
| `/admin/blog.php` | `admin/blog.php` + `admin/ajax/blog.php` | Admin layout + dual-mode (list + rich editor) | Admin, Editor | Blog dashboard with status tabs, search/filter, rich post editor with toolbar, taxonomy, SEO, revisions |

### Navigation & Footer

| URL | Controller / File | Template/View | Access | Description |
|---|---|---|---|---|
| `/admin/navigation.php` | `admin/navigation.php` | Admin layout + inline table, drag-drop reorder | Admin, Editor | Navigation menu builder with multi-level support, locations, visibility |
| `/admin/footer.php` | `admin/footer.php` | Admin layout + inline table | Admin, Editor | Footer links (grouped), copyright text, social links |

### Blocks & Components

| URL | Controller / File | Template/View | Access | Description |
|---|---|---|---|---|
| `/admin/blocks.php` | `admin/blocks.php` | Admin layout + inline table | Admin, Editor | Global blocks management (CTAs, custom HTML, testimonials) with scheduling and device visibility |

### Media

| URL | Controller / File | Template/View | Access | Description |
|---|---|---|---|---|
| `/admin/media.php` | `admin/media.php` | Admin layout + grid/list view | Admin, Editor | Media library -- upload, organize folders, manage images, soft delete, alt text |

### Integrations

| URL | Controller / File | Template/View | Access | Description |
|---|---|---|---|---|
| `/admin/whatsapp.php` | `admin/whatsapp.php` | Admin layout + inline form + analytics dashboard | Admin | WhatsApp global settings (phone, message, position, business hours) + click analytics |

### Email & Users

| URL | Controller / File | Template/View | Access | Description |
|---|---|---|---|---|
| `/admin/email-settings.php` | `admin/email-settings.php` | Admin layout + inline form | Admin | SMTP configuration (host, port, encryption, auth), send-test-email, admin/user notification toggles |
| `/admin/email-templates.php` | `admin/email-templates.php` | Admin layout + tabbed template editor | Admin | Editable email templates (admin_notification, user_confirmation) with subject/HTML/plain-text bodies and variable reference |
| `/admin/users.php` | `admin/users.php` | Admin layout + inline table + modal forms | Admin | User management -- create, edit, role/status toggle, reset password (gated to `role = 'admin'`) |

### Admin AJAX Endpoints

| URL | File | Access | Description |
|---|---|---|---|
| `/admin/ajax/homepage.php` | `admin/ajax/homepage.php` | Authenticated | Homepage section save, reorder, toggle via AJAX |
| `/admin/ajax/services.php` | `admin/ajax/services.php` | Authenticated | Service sub-item CRUD (features, benefits, process, FAQs) and relation linking/unlinking |
| `/admin/ajax/portfolio.php` | `admin/ajax/portfolio.php` | Authenticated | Portfolio sub-item CRUD (gallery, FAQs) and relation linking/unlinking (testimonials, services, blocks) |
| `/admin/ajax/about.php` | `admin/ajax/about.php` | Authenticated | About section save, toggle, reorder, publish, revision CRUD |
| `/admin/ajax/contacts.php` | `admin/ajax/contacts.php` | Authenticated | Contact section save, toggle, reorder, publish |
| `/admin/ajax/blog.php` | `admin/ajax/blog.php` | Authenticated | Blog post save/delete/status/duplicate, revision, category/tag CRUD |
| `/admin/ajax/search.php` | `admin/ajax/search.php` | Authenticated | Search stats, recent queries, clear logs, toggle logging, rebuild index, export CSV |
| `/admin/ajax/leads.php` | `admin/ajax/leads.php` | Authenticated | Lead status update, add note, send email, delete, get detail |

### Admin Assets

| URL Pattern | Path | Access | Description |
|---|---|---|---|
| `/admin/assets/css/*` | `admin/assets/css/` | Public | Admin panel stylesheets |
| `/admin/assets/js/*` | `admin/assets/js/` | Public | Admin panel JavaScript |
| `/admin/assets/images/*` | `admin/assets/images/` | Public | Admin panel images |

---

## API Routes

Public API endpoints that handle form submissions and tracking.

| URL | Method | File | Access | CSRF | Description |
|---|---|---|---|---|---|
| `/api/contact.php` | POST | `api/contact.php` | Public | Yes | Legacy/basic contact form handler -- used by the reusable `contact-form` component (`includes/components/contact-form.php`). Validates name/email/subject/message, saves to `contact_messages`, sends email notification. The Contact page renderer does **not** use this endpoint; it submits to `/ajax/contact.php` (see Contact Page). |
| `/api/whatsapp-track.php` | POST | `api/whatsapp-track.php` | Public | No (origin check) | WhatsApp click tracking -- records click metadata to `whatsapp_clicks` analytics table |

---

## Authentication

### Access Control

| Level | Description | Redirect |
|---|---|---|
| **Public** | No authentication required | (none) |
| **Authenticated** | Must have a valid session or remember-me cookie | Redirect to `/admin/login.php` |
| **Admin** | Must have `role = 'admin'` | Redirect to `/admin/index.php` with error |
| **Admin, Editor** | Must have `role = 'admin'` or `role = 'editor'` | Redirect to `/admin/index.php` with error |

### Auth Flow

```
Unauthenticated Request
    → GET /admin/* (except login.php)
    → Auth::require() called in admin/includes/init.php
    → No valid session or remember-me cookie
    → Redirect to /admin/login.php

Login Form
    → POST /admin/login.php
    → Validate CSRF token
    → Verify email + password with bcrypt
    → Check rate limit (5 attempts per email/IP, 15-min lockout)
    → On success: regenerate session, set user data
    → If "Remember Me": generate split-token cookie (30 days)
    → Redirect to /admin/index.php

Session Check
    → Auth::check() on every admin page load
    → Valid session → user is logged in
    → No session but has remember-me cookie → validate selector + token_hash
    → Valid → regenerate session, issue new remember token
    → Invalid → redirect to login

Logout
    → POST /admin/logout.php
    → Delete remember token from DB
    → Clear session
    → Clear remember-me cookie
    → Redirect to /admin/login.php
```

### Middleware Stack

| Middleware | Applied To | Description |
|---|---|---|
| **Auth::require()** | All admin pages (via `admin/includes/init.php`) | Redirects to login if not authenticated |
| **CSRF Token** | Login form, all POST forms in admin and frontend | Generated via `Security::csrfToken()`, validated on submission |
| **Rate Limiting** | Login (`login_attempts` table), contact form (IP-based) | 5 attempts per 15 minutes, then lockout |
| **Security Headers** | All frontend pages (`index.php`) | CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy |
| **Session Regeneration** | On login, on privilege changes | Prevents session fixation |

---

## 404 Handling

### How 404s Work

1. `DynamicRouter::resolve()` returns `['type' => '404', 'status' => 404]` when no match is found
2. `index.php` sets `http_response_code(404)` before rendering
3. `RenderingEngine::render()` calls `render404()` which loads `pages/404.php` if it exists, otherwise renders an inline fallback
4. WhatsApp floating button is NOT injected on 404 pages

### Custom 404 Page

To customize the 404 page, create or edit `pages/404.php`. The file is loaded within the standard layout (head, header, navigation, footer, scripts).

---

## Redirect Strategy

### Types of Redirects

| Type | Trigger | HTTP Code | Mechanism |
|---|---|---|---|
| **HTTPS Enforcement** | HTTP request received | 301 | `.htaccess` `RewriteRule` |
| **Trailing Slash Removal** | URL ends with `/` and is not a directory | 301 | `.htaccess` `RewriteRule` |
| **Slug Change Redirect** | A page/entry slug was changed and recorded in `url_redirects` | 301 | `DynamicRouter` step 4, queries `url_redirects` table by `old_slug` |
| **Canonical Redirect** | (future) Non-canonical hostname or duplicate content URL | 301 | Planned |

### Redirect Table (`url_redirects`)

Redirects are automatically created by the `SlugManager` whenever a page or content entry slug is updated. Each redirect tracks:

- `old_slug` -- The previous URL path
- `new_slug` -- The new URL path
- `redirect_type` -- `page`, `entry`, or `custom`
- `target_id` -- The entity ID the slug belongs to
- `status_code` -- Always `301` (permanent)
- `hit_count` -- How many times the redirect has been followed

### Redirect Resolution

```
Request: /old-about-page
    → Step 1-3: Not homepage, not content type, not page match
    → Step 4: url_redirects lookup for old_slug = 'old-about-page'
    → Found: new_slug = 'about-us'
    → DynamicRouter returns ['status' => 301, 'target_url' => '/about-us']
    → index.php sends Location header and exits
```

---

## Route Summary by Prefix

| Prefix | Count | Type |
|---|---|---|
| `/` | 1 | Frontend homepage |
| `/services/{slug}` | Dynamic | Frontend service detail |
| `/portfolio` | 1 | Frontend portfolio archive |
| `/portfolio/{slug}` | Dynamic | Frontend portfolio single |
| `/about` | 1 | Frontend about page |
| `/contact` | 1 | Frontend contact page |
| `/blog` | 1 | Frontend blog listing |
| `/blog/category/{slug}` | Dynamic | Frontend blog category archive |
| `/blog/tag/{slug}` | Dynamic | Frontend blog tag archive |
| `/blog/{slug}` | Dynamic | Frontend blog post |
| `/search` | 1 | Frontend search page |
| `/{page_slug}` | Dynamic | Frontend static pages |
| `/{type_slug}` | Dynamic | Frontend content type archive |
| `/{type_slug}/{entry_slug}` | Dynamic | Frontend content entry |
| `/ajax/contact.php` | 1 | Public contact form handler (active) |
| `/ajax/search.php` | 1 | Public search AJAX (suggest/popular/record_click) |
| `/api/contact.php` | 1 | Public API (legacy component handler) |
| `/api/whatsapp-track.php` | 1 | Public API |
| `/admin/login.php` | 1 | Auth |
| `/admin/logout.php` | 1 | Auth |
| `/admin/index.php` | 1 | Admin dashboard |
| `/admin/{page}.php` | 28 | Admin management pages |
| `/admin/ajax/{handler}.php` | 8 | Admin AJAX endpoints |
| `/assets/*` | Static | Frontend assets |
| `/admin/assets/*` | Static | Admin assets |
| `/uploads/*` | Static | User uploads |
| **Total static routes** | **49** | (6 frontend pages + 2 frontend AJAX + 2 API + 2 auth + 1 dashboard + 28 admin pages + 8 admin AJAX; excludes dynamic slug routes and the 3 static asset path patterns) |
