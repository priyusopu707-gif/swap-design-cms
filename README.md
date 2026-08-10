# Swap Design CMS

A professional, lightweight Content Management System built for Swap Design, a creative design agency. Hand-crafted with PHP 8+, MySQL, and vanilla web technologies -- no frameworks, no dependencies, no bloat.

## Overview

Swap Design CMS is a purpose-built content management platform designed to power the Swap Design agency website. It provides complete control over dynamic content -- pages, services, portfolio projects, homepage sections, navigation, global blocks, and WhatsApp integration -- through a clean admin panel.

Every line of code is written to be fast, secure, accessible, and SEO-friendly. The system runs on standard shared hosting (Hostinger) with no special requirements beyond PHP 8+ and MySQL.

## Features

**Content Management**
- **Pages** -- Full CRUD with custom slugs, layouts, sections, and SEO metadata
- **Services** -- 42-field service editor with features, benefits, process steps, FAQs, testimonials, portfolio links, and related blocks
- **Portfolio** -- Rich project pages with hero, overview, solution, results, gallery, testimonials, related services, FAQs, blog links, revisions, and CTA sections
- **Homepage** -- 15 drag-and-drop sections (Hero, About/Introduction, Experience, Services, Why Choose, Problems, Why Work, Industries, Technologies, Process, Portfolio, Testimonials, FAQ, CTA, Contact) with inline editing and 800ms debounced auto-save
- **About** -- 12 drag-and-drop sections with revision history, related portfolio items and blocks
- **Blog / Knowledge Base** -- Posts with categories, tags, revisions, related content, reading time, rich editor, and SEO fields
- **Content Types** -- Custom content type engine for arbitrary structured content with entries and sections
- **Leads / CRM** -- 7-status lead pipeline with search, filters, CSV export, internal notes, and email history

**Design & Layout**
- **Navigation Manager** -- Multi-level menu builder with drag-and-drop reordering
- **Footer Manager** -- Link groups, social links, copyright, and layout control
- **Global Blocks** -- Reusable content blocks that can be embedded on any page
- **Theme Engine** -- Logo, favicon, color scheme, typography, and layout settings
- **Component System** -- Modular PHP components (header, footer, CTA, contact form, navigation, search)

**Media & Assets**
- **Media Library** -- Upload, organize, and manage images with folder support
- **Image Optimizer** -- Automatic optimization and thumbnail generation
- **File Uploader** -- Secure upload handling with type validation

**SEO & Performance**
- Per-page SEO meta (title, description, keywords, canonical URL, OG image)
- Auto-generated JSON-LD structured data (WebSite, Organization, WebPage, CollectionPage, FAQPage, BreadcrumbList)
- Auto-generated XML sitemaps (main + blog/services/portfolio/images) and robots.txt
- Full-text site search with live suggestions, filters, and analytics
- Clean semantic HTML5 with ARIA landmarks
- Mobile-first responsive design
- Core Web Vitals optimized (lazy loading, responsive images, efficient CSS/JS)

**Integrations**
- **WhatsApp** -- Floating chat button with business hours, per-page overrides, click tracking, and analytics dashboard
- **Contact Form** -- Server-side validated form with CSRF protection, honeypot, rate limiting, file uploads, lead capture, and email notifications
- **Email** -- SMTP configuration, editable templates, send-log audit trail

**Security**
- BCrypt password hashing with split-token remember-me
- Progressive rate limiting (per-email + per-IP, 5 attempts, 15-minute lockout)
- CSRF protection on all forms
- PDO prepared statements throughout
- Secure session management with automatic regeneration
- XSS prevention via context-aware escaping
- Security headers (CSP, X-Frame-Options, X-Content-Type-Options)

## Technology Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8+ (no frameworks) |
| Database | MySQL / MariaDB with utf8mb4 |
| Frontend | HTML5, CSS3, vanilla JavaScript (ES6+) |
| Server | Apache 2.4+ with mod_rewrite |
| Dependencies | Zero external libraries or package managers |

## Server Requirements

- PHP 8.0 or higher with these extensions: `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `json`, `filter`, `openssl`
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled
- `allow_url_fopen` enabled (for sitemap generation)
- Write permissions on `cache/`, `logs/`, and `uploads/` directories
- SSL certificate (recommended for production)
- 50 MB minimum disk space (plus uploads)

## Folder Structure

```
swap-design/
├── admin/                  # Admin panel (28 pages, 8 AJAX handlers)
│   ├── ajax/               # AJAX endpoint handlers
│   ├── assets/             # Admin CSS, JS, images
│   └── includes/           # Admin bootstrap, layout, sidebar, topbar
├── api/                    # Public API endpoints (contact, WhatsApp tracking)
├── ajax/                   # Frontend AJAX endpoints (contact, search)
├── assets/                 # Frontend assets
│   ├── css/                # 20 stylesheets (main, responsive, per-page, components)
│   ├── js/                 # 15 scripts (main, per-page, components, utils)
│   ├── images/             # Static images and icons
│   └── fonts/              # Custom web fonts
├── cache/                  # File-based cache (writable)
├── database/
│   └── schema.sql          # Full database schema (58 tables)
├── includes/               # Core application (59 PHP files)
│   ├── auth/               # Authentication system
│   ├── blocks/             # Block engine & component loader
│   ├── components/         # Reusable UI components (header, footer, CTA, contact form)
│   ├── config/             # Environment, database, site, error handler
│   ├── content/            # Content engine (22 classes: managers, renderers, router)
│   ├── functions/          # Helpers, logger, sanitizer, security, SEO
│   ├── integrations/       # WhatsApp Manager
│   ├── media/              # File uploader, image optimizer, media library
│   ├── navigation/         # Navigation & menu manager
│   ├── search/             # Search indexer, manager, renderer
│   ├── seo/                # Sitemap generator, robots manager, SEO auditor
│   ├── settings/           # Settings manager
│   └── themes/             # Theme engine
├── logs/                   # Application logs (writable)
├── pages/                  # Static page templates (404, custom)
├── uploads/                # User-uploaded media (writable)
├── .htaccess               # Apache rewrite rules
├── index.php               # Frontend entry point (dynamic router)
├── robots.txt              # Search engine crawl rules (auto-generated)
└── sitemap.xml             # Auto-generated XML sitemap
```

## Installation

### Step 1 -- Upload Files

Upload the entire `swap-design/` directory to your web server's document root (e.g., `public_html/` on Hostinger).

### Step 2 -- Set Permissions

```
chmod 755 cache/ logs/ uploads/
chmod 644 .htaccess
```

### Step 3 -- Create Database

Create a new MySQL database and user through your hosting control panel (e.g., Hostinger hPanel or phpMyAdmin). Note the database name, username, and password.

### Step 4 -- Import Schema

```
mysql -u your_user -p your_database < database/schema.sql
```

Alternatively, upload `database/schema.sql` through phpMyAdmin.

### Step 5 -- Configure Environment

Copy the example configuration and edit it with your database credentials:

```
cp includes/config/database.php.example includes/config/database.php
```

Then edit `includes/config/database.php` and set your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');
```

Optionally, set these as environment variables on your server and the system will use them automatically.

### Step 6 -- Configure Site Settings

Edit `includes/config/site.php` to set your brand identity. The file returns a config object (not constants); the keys you must update are:

- `brand.name` -- Your agency name
- `urls.base` -- Your live domain URL (used for canonical links and JSON-LD)
- `brand.email` -- Admin contact email
- `forms.contactRecipient` -- Where contact form submissions are emailed
- Social media links, default SEO metadata, and other brand settings

The `SITE_URL` constant used by sitemaps and search URLs is derived automatically from `APP_URL` (see `includes/config/environment.php`), which defaults to the current request host.

### Step 7 -- Create Admin User

Run the following SQL to create your first admin account (replace values with your own):

```sql
INSERT INTO users (email, password_hash, name, role, status, created_at)
VALUES (
    'your@email.com',
    '$2y$12$...', -- Generate with: php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"
    'Admin',
    'admin',
    'active',
    NOW()
);
```

Generate the password hash using the PHP CLI or an online bcrypt generator. The default cost factor is 12.

### Step 8 -- Verify Installation

Visit your domain. The homepage should load with placeholder content. Navigate to `/admin/login.php` and sign in with your admin credentials.

## Admin Login Setup

After creating your admin user in the database:

1. Navigate to `https://yourdomain.com/admin/login.php`
2. Enter your email and password
3. Optionally check "Remember Me" for persistent login (30-day split-token cookie)
4. You will be redirected to the admin dashboard

**Security features at login:**
- 5 failed attempts trigger a 15-minute lockout (per email + per IP)
- CSRF token validation on every login form submission
- Session regenerated on every login
- HTTP-only, secure cookies in production

## Project Architecture

The CMS follows a modular, class-based architecture with clear separation of concerns:

**Request Flow:**
```
Browser Request
    └── .htaccess rewrites to index.php?url={path}
        └── DynamicRouter resolves URL to content type
            └── RenderingEngine delegates to appropriate renderer
                ├── HomepageRenderer (15 sections from homepage_sections table)
                ├── PageRenderer (pages table)
                ├── ServiceRenderer (services + sub-tables)
                ├── PortfolioRenderer (portfolio_items + sub-tables)
                ├── AboutRenderer (about_sections table)
                ├── ContactRenderer (contact_sections table)
                ├── BlogRenderer (blog_posts + taxonomy tables)
                ├── SearchRenderer (search_index table)
                └── ContentEntryRenderer (content_entries + content_types)
                    └── ComponentLoader assembles final HTML
                        └── WhatsAppManager injects floating button
                            └── Response to browser
```

**Core Classes (30+ total):**

| Category | Classes | Purpose |
|---|---|---|
| Config | `environment.php`, `database.php`, `site.php`, `error-handler.php` | Bootstrap and configuration |
| Core | `Database`, `Session` | PDO singleton, session management |
| Auth | `Auth` | Login, logout, remember-me, rate limiting, CSRF |
| Content | `DynamicRouter`, `RenderingEngine`, `SlugManager`, `PageManager`, `HomepageManager`, `HomepageRenderer`, `ServiceManager`, `ServiceRenderer`, `PortfolioManager`, `PortfolioRenderer`, `ContentTypeEngine`, `ContentEntryManager`, `SectionManager`, `LayoutManager`, `AboutManager`, `AboutRenderer`, `ContactManager`, `ContactRenderer`, `LeadManager`, `EmailManager`, `BlogManager`, `BlogRenderer` | Content lifecycle from URL to HTML |
| Settings | `SettingsManager`, `ThemeEngine` | Site settings and theme configuration |
| Navigation | `NavigationManager` | Menu and footer management |
| Blocks | `BlockEngine`, `ComponentLoader` | Global blocks and component rendering |
| Media | `MediaLibrary`, `FileUploader`, `ImageOptimizer` | Media upload, optimization, library |
| Integration | `WhatsAppManager` | WhatsApp button, tracking, business hours |
| Search | `SearchIndexer`, `SearchManager`, `SearchRenderer` | Full-text index, search, results page |
| SEO | `SitemapGenerator`, `RobotsManager`, `SEOAuditor` | Sitemap/robots generation and audits |
| Utilities | `helpers.php`, `logger.php`, `sanitize.php`, `security.php`, `seo.php` | Shared utility functions |

**Admin Panel:**
- 28 admin pages covering all content types
- 8 AJAX endpoint handlers for inline editing
- Tabbed editors for services (13 tabs), portfolio (12 tabs), and blog
- Drag-and-drop reordering on lists, homepage/about/contact sections
- Debounced auto-save (800ms) on inline fields

## Development Workflow

### Local Development

1. Clone the repository to your local environment
2. Set up a local PHP/MySQL stack (XAMPP, MAMP, Laravel Valet, or Docker)
3. Create a local database and import `database/schema.sql`
4. Configure `includes/config/database.php` for local credentials
5. Set `APP_ENV` to `development` in `includes/config/environment.php`
6. Start your local server: `php -S localhost:8000`

### Making Changes

- **Content types and pages** -- Use the admin panel; no code changes needed
- **Styling** -- Edit CSS files in `assets/css/` and `admin/assets/css/`
- **Behavior** -- Edit JS files in `assets/js/` and `admin/assets/js/`
- **New features** -- Add classes under `includes/` following existing patterns
- **Database changes** -- Update `database/schema.sql` and any affected Manager classes

### Adding a New Module

Follow the established pattern used by Services and Portfolio:

1. Create database tables in `database/schema.sql`
2. Create a Manager class (CRUD, relations, filtering) in `includes/content/`
3. Create a Renderer class (section-by-section HTML output) in `includes/content/`
4. Add routes to `DynamicRouter.php`
5. Add rendering dispatch to `RenderingEngine.php`
6. Create admin list and editor pages in `admin/`
7. Create AJAX handler in `admin/ajax/`
8. Add frontend CSS in `assets/css/` and JS in `assets/js/pages/`
9. Update admin sidebar in `admin/includes/sidebar.php`

## Coding Standards

- **PHP** -- PSR-12 style, `snake_case` for functions and variables, `PascalCase` for classes, 4-space indentation
- **SQL** -- `snake_case` table and column names, `id` primary keys, `created_at`/`updated_at` timestamps, `sort_order` for ordering
- **CSS** -- BEM-inspired naming (block__element--modifier), mobile-first media queries, CSS custom properties for theming
- **JavaScript** -- ES6+, IIFE-wrapped modules, `'use strict'`, progressive enhancement
- **HTML** -- Semantic HTML5 elements, ARIA attributes, heading hierarchy, `loading="lazy"` on images
- **Database** -- All queries use PDO prepared statements, no raw concatenation
- **Naming** -- Descriptive, self-documenting names; avoid abbreviations

## Security Notes

- All user input is sanitized and validated before processing
- All database queries use PDO prepared statements with parameter binding
- Passwords are hashed with BCrypt (cost factor 12)
- Session cookies are HTTP-only and secure in production
- CSRF tokens are validated on all state-changing requests (forms, AJAX)
- Rate limiting is applied to login attempts and contact form submissions
- File uploads are validated by MIME type and extension
- Sensitive files (config, logs) are outside the web root or protected by `.htaccess`
- Security headers are set automatically: CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy

**Production checklist:**
1. Set `APP_ENV` to `production` and `APP_DEBUG` to `false`
2. Enable HTTPS and HSTS
3. Change default admin password immediately
4. Set strong database credentials
5. Restrict file permissions: `chmod 640 includes/config/database.php`
6. Regularly review `logs/` for suspicious activity
7. Keep PHP and MySQL versions up to date

## Deployment Notes

**Hostinger Shared Hosting:**
- Upload files via FTP or Hostinger File Manager to `public_html/`
- Database credentials and site URL in `includes/config/database.php`
- Ensure `mod_rewrite` is enabled (it is by default on Hostinger)
- Set `upload_max_filesize` and `post_max_size` as needed in `.htaccess` or via hPanel

**Post-deployment steps:**
1. Clear the `cache/` directory
2. Test all admin pages and public routes
3. Submit sitemap to Google Search Console
4. Verify SSL certificate is active
5. Test WhatsApp integration with real phone number
6. Test contact form submission

## Future Roadmap

- **SEO Audit Dashboard** -- Admin UI wrapping the existing SEOAuditor service
- **Per-page SEO tab** -- Dedicated SEO meta editor in admin for all content types
- **Testimonials Module** -- Dedicated testimonial management with star ratings
- **Self-service password reset** -- Email-based password recovery
- **Analytics Integration** -- Google Analytics / Plausible dashboard widget
- **Mailchimp Integration** -- Newsletter signup and list management
- **Backup System** -- One-click database and file backups from admin
- **Multi-language** -- i18n support with language switcher
- **Dark Mode** -- Admin panel dark mode toggle
- **API** -- RESTful API for headless / mobile app consumption

## License

Proprietary. All rights reserved. Built for Swap Design.

## Credits

Developed for Swap Design -- a creative agency specializing in branding, web design, and digital experiences.
