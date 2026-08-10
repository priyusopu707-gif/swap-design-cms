# Swap Design CMS -- Database Schema

Complete reference documentation for the Swap Design CMS MySQL/MariaDB database.

---

## Entity Relationship Overview

```
                         ┌─────────────────┐
                         │      users      │
                         └────────┬────────┘
                                  │
                    ┌─────────────┼─────────────┐
                    │             │             │
              ┌─────┴─────┐ ┌────┴─────┐ ┌──────┴──────┐
              │ remember_ │ │  login_  │ │   media_    │
              │  tokens   │ │ attempts │ │  library    │
              └───────────┘ └──────────┘ └─────────────┘

   ┌──────────────┐    ┌──────────────┐    ┌──────────────────┐
   │ site_settings│    │   layouts    │    │ url_redirects    │
   └──────────────┘    └──────┬───────┘    └──────────────────┘
                              │
   ┌──────────────┐    ┌──────┴───────┐    ┌──────────────────┐
   │   sections   │◄───┤ page_sections├───►│      pages       │
   └──────────────┘    └──────────────┘    └────────┬─────────┘
                                                    │
              ┌─────────────────────────────────────┤
              │                                     │
   ┌──────────┴──────────┐              ┌───────────┴───────────┐
   │ whatsapp_page_      │              │   content_entries     │
   │    overrides        │              └───────────┬───────────┘
   └─────────────────────┘                          │
              │                          ┌──────────┴──────────┐
   ┌──────────┴──────────┐    ┌─────────┴─────────┐ ┌─────────┴─────────┐
   │  whatsapp_clicks    │    │  service_          │ │  portfolio_       │
   └─────────────────────┘    │  testimonials      │ │  testimonials     │
                              └────────────────────┘ └───────────────────┘

   ┌─────────────────────────────────────────────────────────────────────┐
   │                         SERVICES MODULE                              │
   │                                                                      │
   │  ┌──────────┐                                                        │
   │  │ services │                                                        │
   │  └────┬─────┘                                                        │
   │       │ 1:N                                                          │
   │       ├── service_features      (icon, title, description)           │
   │       ├── service_benefits      (icon, title, description)           │
   │       ├── service_process_steps (icon, title, description)           │
   │       ├── service_faqs          (question, answer)                   │
   │       ├── service_portfolio     ──► portfolio_items                  │
   │       ├── service_testimonials  ──► content_entries                  │
   │       └── service_related_blocks──► global_blocks                    │
   └─────────────────────────────────────────────────────────────────────┘

   ┌─────────────────────────────────────────────────────────────────────┐
   │                        PORTFOLIO MODULE                              │
   │                                                                      │
   │  ┌────────────────┐                                                  │
   │  │ portfolio_items│                                                  │
   │  └───────┬────────┘                                                  │
   │          │ 1:N                                                       │
   │          ├── portfolio_gallery           (image, caption, type)      │
   │          ├── portfolio_faqs              (question, answer)          │
   │          ├── portfolio_testimonials      ──► content_entries         │
   │          ├── portfolio_related_services  ──► services                │
   │          └── portfolio_related_blocks    ──► global_blocks           │
   └─────────────────────────────────────────────────────────────────────┘

   ┌─────────────────────────────────────────────────────────────────────┐
   │                          CONTENT ENGINE                              │
   │                                                                      │
   │  ┌──────────────┐    ┌─────────────────┐                             │
   │  │ content_types│───►│ content_entries │                             │
   │  └──────────────┘    └─────────────────┘                             │
   └─────────────────────────────────────────────────────────────────────┘

   ┌─────────────────────────────────────────────────────────────────────┐
   │                         NAVIGATION & FOOTER                          │
   │                                                                      │
   │  ┌───────────────┐     ┌──────────────┐     ┌──────────────┐        │
   │  │navigation_menu│     │ footer_links │     │footer_settings│        │
   │  └───────────────┘     └──────────────┘     └──────────────┘        │
   └─────────────────────────────────────────────────────────────────────┘

   ┌─────────────────────────────────────────────────────────────────────┐
   │                         MEDIA & BLOCKS                               │
   │                                                                      │
   │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌────────────┐  │
   │  │media_folders│  │media_library│  │global_blocks│  │global_block│  │
   │  └──────┬──────┘  └─────────────┘  └──────┬──────┘  │   _usage   │  │
   │         │                                 │          └──────┬─────┘  │
   │         └─────────────────────────────────┘                 │        │
   │   (folder_id FK on media_library)          (block_id FK on usage)    │
   └─────────────────────────────────────────────────────────────────────┘

   ┌─────────────────────────────────────────────────────────────────────┐
   │                           ABOUT MODULE                               │
   │                                                                      │
   │  ┌──────────────┐   1:N  ┌──────────────┐                            │
   │  │about_sections│───►───│about_revisions│                            │
   │  └──────┬───────┘        └──────────────┘                            │
   │         ├── about_related_portfolio ──► portfolio_items              │
   │         └── about_related_blocks   ──► global_blocks                 │
   └─────────────────────────────────────────────────────────────────────┘

   ┌─────────────────────────────────────────────────────────────────────┐
   │                     CONTACT & LEAD MANAGEMENT                        │
   │                                                                      │
   │  ┌────────────────┐      ┌──────────┐      ┌───────────────┐        │
   │  │contact_sections│      │  leads   │──1:N─│  lead_notes   │        │
   │  └────────────────┘      └────┬─────┘      └───────────────┘        │
   │                               │ 0:N                                  │
   │                        ┌──────┴──────┐                               │
   │                        │ email_log   │                               │
   │                        └─────────────┘                               │
   │  ┌────────────────┐      ┌──────────────┐                            │
   │  │email_templates │      │whatsapp_clicks│ (see Integrations)        │
   │  └────────────────┘      └──────────────┘                            │
   └─────────────────────────────────────────────────────────────────────┘

   ┌─────────────────────────────────────────────────────────────────────┐
   │                       BLOG / KNOWLEDGE BASE                          │
   │                                                                      │
   │  ┌─────────────────┐   ┌──────────────────┐   ┌────────────────┐    │
   │  │blog_categories  │◄──│blog_post_        │──►│  blog_posts    │    │
   │  └─────────────────┘   │categories (N:M)  │   └───────┬────────┘    │
   │  ┌─────────────────┐   ┌──────────────────┐           │ 1:N         │
   │  │blog_tags        │◄──│blog_post_tags    │   ┌───────┴────────┐    │
   │  └─────────────────┘   └──────────────────┘   │blog_revisions │    │
   │  ┌──────────────────────────────────────────┐ │blog_relations-│    │
   │  │ portfolio_related_blog  ──► blog_posts   │ │  hips (N:M)   │    │
   │  └──────────────────────────────────────────┘ └────────────────┘    │
   └─────────────────────────────────────────────────────────────────────┘

   (portfolio_revisions: 1:N child of portfolio_items, alongside the
    portfolio_gallery / faqs / testimonials / services / blocks links.)
```

---

## Table Reference

All 58 tables defined in `database/schema.sql` are documented below (40 core/feature tables plus the About, Contact/Lead/Email, Blog, and Search modules).

### 1. contact_messages

**Purpose:** Stores contact form submissions from the public website.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(100) | No | | Sender name |
| `email` | VARCHAR(254) | No | | Sender email address |
| `subject` | VARCHAR(200) | No | `'General Inquiry'` | Message subject |
| `message` | TEXT | No | | Message body |
| `ip_address` | VARCHAR(45) | No | `''` | Sender IP (IPv4/IPv6) |
| `is_read` | TINYINT(1) | No | `0` | Read status flag |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Submission timestamp |

**Primary Key:** `id`
**Foreign Keys:** None
**Indexes:** `idx_created_at`, `idx_is_read`

---

### 2. pages

**Purpose:** Stores CMS page content -- the primary page entity with layout, template, and navigation flags.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `slug` | VARCHAR(100) | No | | Unique URL slug |
| `title` | VARCHAR(200) | No | | Page title (h1) |
| `meta_desc` | VARCHAR(320) | No | `''` | Meta description for SEO |
| `layout_id` | INT UNSIGNED | Yes | NULL | FK to layouts table |
| `template` | VARCHAR(100) | Yes | NULL | Custom template override |
| `is_homepage` | TINYINT(1) | No | `0` | Is this the site homepage? |
| `show_in_nav` | TINYINT(1) | No | `0` | Include in navigation menus? |
| `nav_label` | VARCHAR(100) | Yes | NULL | Custom label for nav display |
| `content` | LONGTEXT | No | | Page body HTML |
| `status` | ENUM('draft','published') | No | `'draft'` | Publication status |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** `layout_id` -> `layouts(id)`
**Indexes:** `idx_slug` (unique), `idx_status`, `idx_layout`, `idx_homepage`

**Relationships:**
- Has many `page_sections` (page_sections.page_id)
- Has one `whatsapp_page_overrides` (whatsapp_page_overrides.page_id)

---

### 3. portfolio_items

**Purpose:** Portfolio project pages -- showcases completed design/development work with rich multi-section content.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `title` | VARCHAR(200) | No | | Project title |
| `slug` | VARCHAR(200) | No | | Unique URL slug |
| `description` | TEXT | No | | Short description |
| `full_description` | LONGTEXT | Yes | NULL | Full project description |
| `image_url` | VARCHAR(500) | No | `''` | Featured image URL |
| `gallery_images` | JSON | Yes | NULL | Legacy gallery (prefer portfolio_gallery table) |
| `category` | VARCHAR(100) | No | `''` | Project category |
| `client_name` | VARCHAR(200) | Yes | NULL | Client/organization name |
| `industry` | VARCHAR(100) | Yes | NULL | Client industry |
| `completion_date` | DATE | Yes | NULL | Project completion date |
| `project_url` | VARCHAR(500) | Yes | NULL | Live project URL |
| `hero_title` | VARCHAR(200) | Yes | NULL | Hero section title |
| `hero_description` | TEXT | Yes | NULL | Hero section description |
| `hero_image` | VARCHAR(500) | Yes | NULL | Hero section image |
| `hero_bg_image` | VARCHAR(500) | Yes | NULL | Hero background image |
| `hero_cta_text` | VARCHAR(100) | Yes | NULL | Hero CTA button text |
| `hero_cta_url` | VARCHAR(300) | Yes | NULL | Hero CTA button URL |
| `overview_summary` | TEXT | Yes | NULL | Project summary |
| `overview_requirements` | TEXT | Yes | NULL | Client requirements |
| `overview_problem` | TEXT | Yes | NULL | Challenge / problem statement |
| `overview_objectives` | TEXT | Yes | NULL | Project objectives |
| `solution_strategy` | TEXT | Yes | NULL | Design strategy |
| `solution_branding` | TEXT | Yes | NULL | Branding approach |
| `solution_process` | TEXT | Yes | NULL | Development process |
| `solution_tech` | TEXT | Yes | NULL | Technologies used |
| `results_summary` | TEXT | Yes | NULL | Outcome summary |
| `results_achievements` | TEXT | Yes | NULL | Key achievements |
| `results_feedback` | TEXT | Yes | NULL | Client feedback quote |
| `project_duration` | VARCHAR(100) | Yes | NULL | Project timeline |
| `project_deliverables` | TEXT | Yes | NULL | Deliverables list |
| `project_services_used` | VARCHAR(500) | Yes | NULL | Services applied |
| `cta_heading` | VARCHAR(200) | Yes | NULL | CTA heading |
| `cta_description` | TEXT | Yes | NULL | CTA description |
| `cta_button_text` | VARCHAR(100) | Yes | NULL | CTA button text |
| `cta_button_url` | VARCHAR(300) | Yes | NULL | CTA button URL |
| `cta_show_whatsapp` | TINYINT(1) | No | `0` | Show WhatsApp in CTA? |
| `cta_whatsapp_label` | VARCHAR(100) | Yes | NULL | WhatsApp button label |
| `cta_bg_image` | VARCHAR(500) | Yes | NULL | CTA background image |
| `seo_title` | VARCHAR(200) | Yes | NULL | SEO meta title |
| `meta_description` | VARCHAR(320) | Yes | NULL | SEO meta description |
| `focus_keyword` | VARCHAR(200) | Yes | NULL | Target keyword |
| `canonical_url` | VARCHAR(500) | Yes | NULL | Canonical URL |
| `og_image` | VARCHAR(500) | Yes | NULL | Open Graph image |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |
| `is_featured` | TINYINT(1) | No | `0` | Featured flag |
| `status` | ENUM('draft','published') | No | `'draft'` | Publication status |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | Yes | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** None (referenced by sub-tables)
**Indexes:** `slug` (unique), `idx_category`, `idx_status`, `idx_sort`

**Relationships:**
- Has many `portfolio_gallery` (portfolio_id)
- Has many `portfolio_faqs` (portfolio_id)
- Has many `portfolio_testimonials` (portfolio_id -> content_entries)
- Has many `portfolio_related_services` (portfolio_id -> services)
- Has many `portfolio_related_blocks` (portfolio_id -> global_blocks)
- Has many `service_portfolio` (portfolio_item_id -> services)

---

### 4. site_settings

**Purpose:** Key-value store for site-wide configuration (branding, SEO defaults, social links, API keys).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `setting_key` | VARCHAR(100) | No | | Primary key -- setting identifier |
| `setting_value` | TEXT | Yes | NULL | Setting value (any format) |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `setting_key`
**Foreign Keys:** None
**Indexes:** None (PK is the lookup key)

---

### 5. users

**Purpose:** Admin user accounts for CMS access.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `email` | VARCHAR(254) | No | | Unique login email |
| `password_hash` | VARCHAR(255) | No | | BCrypt hash (cost 12) |
| `name` | VARCHAR(100) | No | | Display name |
| `role` | ENUM('admin','editor') | No | `'admin'` | Access level |
| `status` | ENUM('active','inactive') | No | `'active'` | Account status |
| `last_login_at` | DATETIME | Yes | NULL | Last successful login |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** None (referenced by other tables)
**Indexes:** `idx_email` (unique), `idx_status`

**Relationships:**
- Has many `remember_tokens` (user_id) -- ON DELETE CASCADE
- Referenced by `media_library.uploaded_by`

---

### 6. login_attempts

**Purpose:** Tracks all login attempts for rate limiting (per-email + per-IP lockout after 5 failures).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `email` | VARCHAR(254) | No | | Attempted email |
| `ip_address` | VARCHAR(45) | No | | Client IP (IPv4/IPv6) |
| `attempted_at` | DATETIME | No | CURRENT_TIMESTAMP | Attempt timestamp |
| `success` | TINYINT(1) | No | `0` | Was login successful? |

**Primary Key:** `id`
**Foreign Keys:** None
**Indexes:** `idx_lookup` (email, ip_address, attempted_at), `idx_cleanup` (attempted_at)

---

### 7. remember_tokens

**Purpose:** Split-token remember-me authentication. `selector` is stored in the cookie (plaintext) for DB lookup; `token_hash` is a SHA-256 hash of the actual token.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `user_id` | INT UNSIGNED | No | | FK to users |
| `selector` | VARCHAR(64) | No | | Plaintext selector (cookie) |
| `token_hash` | VARCHAR(128) | No | | SHA-256(token) -- hash only |
| `expires_at` | DATETIME | No | | Token expiry (30 days) |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |

**Primary Key:** `id`
**Foreign Keys:** `user_id` -> `users(id)` ON DELETE CASCADE
**Indexes:** `idx_selector` (unique), `idx_user`, `idx_expires`

---

### 8. navigation_menu

**Purpose:** Multi-level navigation menu items with drag-and-drop ordering and location support.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `parent_id` | INT UNSIGNED | Yes | NULL | FK to self for sub-menus |
| `label` | VARCHAR(100) | No | | Display text |
| `url` | VARCHAR(255) | No | `'#'` | Link destination |
| `slug` | VARCHAR(100) | No | `''` | URL slug reference |
| `location` | VARCHAR(30) | No | `'primary'` | Menu location (primary, footer, etc.) |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |
| `is_visible` | TINYINT(1) | No | `1` | Visibility toggle |
| `open_new_tab` | TINYINT(1) | No | `0` | Open in new tab? |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** `parent_id` -> `navigation_menu(id)` ON DELETE SET NULL
**Indexes:** `idx_location_sort` (location, sort_order), `idx_parent`

---

### 9. footer_links

**Purpose:** Footer link groups with labels, URLs, and ordering.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `group_name` | VARCHAR(50) | No | | Link group (e.g., Services, Company) |
| `label` | VARCHAR(100) | No | | Display text |
| `url` | VARCHAR(255) | No | `'#'` | Link destination |
| `sort_order` | INT UNSIGNED | No | `0` | Display order within group |
| `is_visible` | TINYINT(1) | No | `1` | Visibility toggle |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** None
**Indexes:** `idx_group_sort` (group_name, sort_order)

---

### 10. footer_settings

**Purpose:** Footer-wide configuration (copyright text, social media links stored via site_settings).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `copyright_text` | VARCHAR(500) | No | `'&copy; {year} Swap Design...'` | Copyright text with {year} token |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** None
**Indexes:** None

---

### 11. global_blocks

**Purpose:** Reusable content blocks (CTAs, testimonials, custom HTML) that can be embedded on any page.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(200) | No | | Block name |
| `slug` | VARCHAR(200) | No | | Unique identifier |
| `block_type` | VARCHAR(50) | No | `'custom_html'` | Block type |
| `description` | TEXT | Yes | NULL | Internal description |
| `content` | JSON | No | | Block content as JSON |
| `status` | ENUM('draft','published','archived') | No | `'draft'` | Publication status |
| `category` | VARCHAR(50) | Yes | NULL | Block category |
| `full_width` | TINYINT(1) | No | `0` | Full-width display? |
| `background_color` | VARCHAR(20) | Yes | NULL | CSS background color |
| `custom_css` | TEXT | Yes | NULL | Custom CSS styles |
| `device_visibility` | ENUM('all','desktop','tablet','mobile') | No | `'all'` | Device visibility filter |
| `schedule_start` | DATETIME | Yes | NULL | Scheduled display start |
| `schedule_end` | DATETIME | Yes | NULL | Scheduled display end |
| `usage_count` | INT UNSIGNED | No | `0` | How many pages use this block |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** None (referenced by junction tables)
**Indexes:** `idx_type`, `idx_status`, `idx_category`, `idx_slug` (unique)

**Relationships:**
- Has many `global_block_usage` (block_id) -- ON DELETE CASCADE
- Has many `service_related_blocks` (global_block_id)
- Has many `portfolio_related_blocks` (global_block_id)

---

### 12. global_block_usage

**Purpose:** Tracks where each global block is placed (which page, which section zone, order).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `block_id` | INT UNSIGNED | No | | FK to global_blocks |
| `page_id` | INT UNSIGNED | Yes | NULL | FK to pages (optional) |
| `page_slug` | VARCHAR(200) | No | | Page URL slug |
| `section_key` | VARCHAR(100) | No | `'content'` | Section zone identifier |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |

**Primary Key:** `id`
**Foreign Keys:** `block_id` -> `global_blocks(id)` ON DELETE CASCADE
**Indexes:** `idx_block`, `idx_page`

---

### 13. media_library

**Purpose:** Centralized media asset storage with metadata, optimization flags, and trash support.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `filename` | VARCHAR(255) | No | | Stored filename |
| `original_name` | VARCHAR(255) | No | | Original upload filename |
| `mime_type` | VARCHAR(100) | No | | MIME type |
| `file_size` | INT UNSIGNED | No | `0` | File size in bytes |
| `file_hash` | VARCHAR(64) | No | | SHA-256 file hash |
| `alt_text` | VARCHAR(255) | No | `''` | Accessibility alt text |
| `title` | VARCHAR(255) | No | `''` | Media title |
| `caption` | TEXT | Yes | NULL | Media caption |
| `folder_id` | INT UNSIGNED | Yes | NULL | FK to media_folders |
| `width` | INT UNSIGNED | Yes | NULL | Image width in pixels |
| `height` | INT UNSIGNED | Yes | NULL | Image height in pixels |
| `dominant_color` | VARCHAR(7) | Yes | NULL | Hex dominant color |
| `has_webp` | TINYINT(1) | No | `0` | WEBP version generated? |
| `has_thumb` | TINYINT(1) | No | `0` | Thumbnail generated? |
| `is_trashed` | TINYINT(1) | No | `0` | Soft-delete flag |
| `trashed_at` | DATETIME | Yes | NULL | When trashed |
| `uploaded_by` | INT UNSIGNED | Yes | NULL | Uploading user ID |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Upload timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** `folder_id` -> `media_folders(id)`
**Indexes:** `idx_hash`, `idx_type`, `idx_trashed`, `idx_folder`, `idx_created`

---

### 14. media_folders

**Purpose:** Virtual folder hierarchy for organizing media assets.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `parent_id` | INT UNSIGNED | Yes | NULL | FK to self for nesting |
| `name` | VARCHAR(100) | No | | Folder display name |
| `slug` | VARCHAR(100) | No | | URL-safe identifier |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** None (self-referencing parent_id is implied but not declared as FK)
**Indexes:** `idx_parent`, `idx_parent_slug` (unique on parent_id, slug)

---

### 15. global_components

**Purpose:** Registry of reusable frontend components (header, footer, CTA, contact form, navigation) with template and asset paths.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(100) | No | | Component name |
| `slug` | VARCHAR(100) | No | | Unique identifier |
| `category` | VARCHAR(50) | No | `'general'` | Component category |
| `description` | TEXT | Yes | NULL | Component description |
| `template_path` | VARCHAR(255) | No | | PHP template file path |
| `css_path` | VARCHAR(255) | Yes | NULL | CSS file path |
| `js_path` | VARCHAR(255) | Yes | NULL | JS file path |
| `default_config` | JSON | Yes | NULL | Default configuration |
| `is_active` | TINYINT(1) | No | `1` | Active toggle |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** None
**Indexes:** `idx_category`, `idx_active`

---

### 16. content_types

**Purpose:** Defines custom content types with JSON field schemas -- flexible structured content beyond pages.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(100) | No | | Content type name |
| `slug` | VARCHAR(100) | No | | Unique URL prefix |
| `description` | TEXT | Yes | NULL | Internal description |
| `fields_schema` | JSON | Yes | NULL | Array of field definitions |
| `icon` | VARCHAR(50) | No | `'file'` | Admin icon identifier |
| `is_system` | TINYINT(1) | No | `0` | System type (non-deletable)? |
| `has_entries` | TINYINT(1) | No | `1` | Supports entries? |
| `list_template` | VARCHAR(255) | Yes | NULL | Archive/list template path |
| `single_template` | VARCHAR(255) | Yes | NULL | Single entry template path |
| `status` | ENUM('active','inactive') | No | `'active'` | Active toggle |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** None (referenced by content_entries)
**Indexes:** `idx_slug` (unique), `idx_status`

**Relationships:**
- Has many `content_entries` (content_type_id) -- ON DELETE CASCADE

---

### 17. content_entries

**Purpose:** Content records belonging to a content type, with JSON field values.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `content_type_id` | INT UNSIGNED | No | | FK to content_types |
| `title` | VARCHAR(255) | No | | Entry title |
| `slug` | VARCHAR(255) | No | | URL slug |
| `fields` | JSON | Yes | NULL | Field values as key-value pairs |
| `excerpt` | TEXT | Yes | NULL | Short excerpt |
| `featured_image` | VARCHAR(500) | Yes | NULL | Featured image URL |
| `status` | ENUM('draft','published','archived') | No | `'draft'` | Publication status |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |
| `created_by` | INT UNSIGNED | Yes | NULL | Author user ID |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** `content_type_id` -> `content_types(id)` ON DELETE CASCADE
**Indexes:** `idx_type`, `idx_status`, `idx_slug`, `idx_sort`

**Relationships:**
- Referenced by `service_testimonials.testimonial_entry_id`
- Referenced by `portfolio_testimonials.testimonial_entry_id`

---

### 18. layouts

**Purpose:** Page structural templates defining named content zones.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(100) | No | | Layout name |
| `slug` | VARCHAR(100) | No | | Unique identifier |
| `description` | TEXT | Yes | NULL | Layout description |
| `zones` | JSON | No | | Array of zone definitions |
| `template_path` | VARCHAR(255) | No | `''` | PHP template file path |
| `status` | ENUM('active','inactive') | No | `'active'` | Active toggle |
| `is_default` | TINYINT(1) | No | `0` | Is the default layout? |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** None (referenced by pages.layout_id)
**Indexes:** `idx_status`, `idx_default`

---

### 19. sections

**Purpose:** Reusable page content sections -- custom HTML, global blocks, components, dynamic lists, or shortcodes.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(150) | No | | Section name |
| `slug` | VARCHAR(150) | No | | Unique identifier |
| `section_type` | ENUM('custom_html','global_block','component','content_entries','dynamic_list','shortcode') | No | `'custom_html'` | Section type |
| `config` | JSON | No | | Type-specific configuration |
| `status` | ENUM('draft','published') | No | `'draft'` | Publication status |
| `category` | VARCHAR(50) | Yes | NULL | Section category |
| `description` | TEXT | Yes | NULL | Internal description |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** None
**Indexes:** `idx_type`, `idx_status`, `idx_category`

---

### 20. page_sections

**Purpose:** Junction table linking sections to pages, with zone placement, ordering, scheduling, and per-page config overrides.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `page_id` | INT UNSIGNED | No | | FK to pages |
| `section_id` | INT UNSIGNED | No | | FK to sections |
| `zone_key` | VARCHAR(50) | No | `'content'` | Layout zone identifier |
| `sort_order` | INT UNSIGNED | No | `0` | Display order within zone |
| `is_enabled` | TINYINT(1) | No | `1` | Enable/disable toggle |
| `custom_config` | JSON | Yes | NULL | Per-page config overrides |
| `schedule_start` | DATETIME | Yes | NULL | Scheduled display start |
| `schedule_end` | DATETIME | Yes | NULL | Scheduled display end |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:**
- `page_id` -> `pages(id)` ON DELETE CASCADE
- `section_id` -> `sections(id)` ON DELETE CASCADE

**Indexes:** `idx_page_zone`, `idx_page_sort`, `uk_page_section_zone` (unique on page_id, section_id, zone_key)

---

### 21. url_redirects

**Purpose:** Tracks slug changes to generate 301 redirects and maintain SEO equity.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `old_slug` | VARCHAR(255) | No | | Original URL slug |
| `new_slug` | VARCHAR(255) | No | | Target URL slug |
| `redirect_type` | ENUM('page','entry','custom') | No | `'page'` | Entity type |
| `target_id` | INT UNSIGNED | Yes | NULL | Target entity ID |
| `status_code` | SMALLINT | No | `301` | HTTP redirect code |
| `hit_count` | INT UNSIGNED | No | `0` | Redirect usage counter |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |

**Primary Key:** `id`
**Foreign Keys:** None
**Indexes:** `uk_old_slug_type` (unique on old_slug, redirect_type), `idx_new_slug`

---

### 22. whatsapp_page_overrides

**Purpose:** Per-page WhatsApp button settings -- overrides global WhatsApp configuration.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `page_id` | INT UNSIGNED | No | | FK to pages |
| `is_enabled` | TINYINT(1) | No | `1` | Enable on this page? |
| `custom_number` | VARCHAR(30) | Yes | NULL | Per-page phone number |
| `custom_message` | VARCHAR(500) | Yes | NULL | Per-page greeting message |
| `position_override` | ENUM('global','left','right') | No | `'global'` | Button position override |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** `page_id` -> `pages(id)` ON DELETE CASCADE
**Indexes:** `uk_page_id` (unique)

---

### 23. whatsapp_clicks

**Purpose:** Analytics -- tracks every WhatsApp button click with context (source, device, timestamp).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `page_id` | INT UNSIGNED | Yes | NULL | FK to pages (nullable) |
| `page_title` | VARCHAR(200) | Yes | NULL | Page title at click time |
| `source` | VARCHAR(100) | Yes | NULL | Click source (floating_button, hero_cta, service_card, portfolio_item, global_block, contact_page) |
| `source_label` | VARCHAR(255) | Yes | NULL | Context label (service name, portfolio title) |
| `device_type` | ENUM('desktop','tablet','mobile','unknown') | No | `'unknown'` | Device category |
| `visitor_ip` | VARCHAR(45) | Yes | NULL | Visitor IP address |
| `clicked_at` | DATETIME | No | CURRENT_TIMESTAMP | Click timestamp |

**Primary Key:** `id`
**Foreign Keys:** None (page_id is not a declared FK for flexibility)
**Indexes:** `idx_page`, `idx_source`, `idx_device`, `idx_clicked`

---

### 24. homepage_sections

**Purpose:** Ordered, editable sections composing the homepage. Each section stores type-specific fields as JSON config.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `section_key` | VARCHAR(50) | No | | Unique key (hero, about, experience, services, why_choose, process, portfolio, testimonials, faq, cta, contact) |
| `label` | VARCHAR(100) | No | | Human-readable label |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |
| `is_enabled` | TINYINT(1) | No | `1` | Enable/disable toggle |
| `config` | JSON | No | | All editable fields for this section |
| `status` | ENUM('draft','published') | No | `'draft'` | Publication status |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** None
**Indexes:** `idx_order`, `idx_enabled`

**Defined section keys:** `hero`, `about`, `experience`, `services`, `why_choose`, `process`, `portfolio`, `testimonials`, `faq`, `cta`, `contact`

---

### 25. services

**Purpose:** Service detail pages with hero, overview, features, benefits, process, FAQ, portfolio, testimonials, CTA, contact, and SEO sections.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `title` | VARCHAR(200) | No | | Service title |
| `slug` | VARCHAR(200) | No | | Unique URL slug |
| `short_description` | TEXT | Yes | NULL | Brief description |
| `full_description` | LONGTEXT | Yes | NULL | Full service description |
| `featured_image` | VARCHAR(500) | Yes | NULL | Featured image URL |
| `icon` | VARCHAR(100) | Yes | NULL | Icon identifier |
| `category` | VARCHAR(100) | Yes | NULL | Service category |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |
| `status` | ENUM('draft','published','archived') | No | `'draft'` | Publication status |
| `hero_title` | VARCHAR(200) | Yes | NULL | Hero section title |
| `hero_description` | TEXT | Yes | NULL | Hero description |
| `hero_image` | VARCHAR(500) | Yes | NULL | Hero image |
| `hero_bg_image` | VARCHAR(500) | Yes | NULL | Hero background image |
| `hero_cta_primary_text` | VARCHAR(100) | Yes | NULL | Primary CTA button text |
| `hero_cta_primary_url` | VARCHAR(300) | Yes | NULL | Primary CTA button URL |
| `hero_cta_secondary_text` | VARCHAR(100) | Yes | NULL | Secondary CTA button text |
| `hero_cta_secondary_url` | VARCHAR(300) | Yes | NULL | Secondary CTA button URL |
| `overview_intro` | TEXT | Yes | NULL | Overview introduction |
| `overview_benefits` | TEXT | Yes | NULL | Overview benefits |
| `overview_why` | TEXT | Yes | NULL | Why choose this service |
| `cta_heading` | VARCHAR(200) | Yes | NULL | CTA heading |
| `cta_description` | TEXT | Yes | NULL | CTA description |
| `cta_button_text` | VARCHAR(100) | Yes | NULL | CTA button text |
| `cta_button_url` | VARCHAR(300) | Yes | NULL | CTA button URL |
| `cta_show_whatsapp` | TINYINT(1) | No | `0` | Show WhatsApp in CTA? |
| `cta_whatsapp_label` | VARCHAR(100) | Yes | NULL | WhatsApp button label |
| `cta_bg_image` | VARCHAR(500) | Yes | NULL | CTA background image |
| `contact_show_whatsapp` | TINYINT(1) | No | `1` | Show WhatsApp in contact? |
| `contact_show_phone` | TINYINT(1) | No | `1` | Show phone in contact? |
| `contact_show_email` | TINYINT(1) | No | `1` | Show email in contact? |
| `contact_show_form` | TINYINT(1) | No | `1` | Show form in contact? |
| `contact_button_text` | VARCHAR(100) | Yes | NULL | Contact button text |
| `contact_button_url` | VARCHAR(300) | Yes | NULL | Contact button URL |
| `seo_title` | VARCHAR(200) | Yes | NULL | SEO meta title |
| `meta_description` | VARCHAR(320) | Yes | NULL | SEO meta description |
| `focus_keyword` | VARCHAR(200) | Yes | NULL | Target keyword |
| `canonical_url` | VARCHAR(500) | Yes | NULL | Canonical URL |
| `og_image` | VARCHAR(500) | Yes | NULL | Open Graph image |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Primary Key:** `id`
**Foreign Keys:** None (referenced by junction tables)
**Indexes:** `idx_slug` (unique), `idx_status`, `idx_category`, `idx_sort`

**Relationships (1:N child tables):**
- Has many `service_features` (service_id) -- ON DELETE CASCADE
- Has many `service_benefits` (service_id) -- ON DELETE CASCADE
- Has many `service_process_steps` (service_id) -- ON DELETE CASCADE
- Has many `service_faqs` (service_id) -- ON DELETE CASCADE
- Has many `service_portfolio` (service_id) -- ON DELETE CASCADE
- Has many `service_testimonials` (service_id) -- ON DELETE CASCADE
- Has many `service_related_blocks` (service_id) -- ON DELETE CASCADE
- Referenced by `portfolio_related_services` (service_id)

---

### 26. service_features

**Purpose:** Feature cards for a service (icon, title, description).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `service_id` | INT UNSIGNED | No | | FK to services |
| `icon` | VARCHAR(100) | Yes | NULL | Icon identifier |
| `title` | VARCHAR(200) | Yes | NULL | Feature title |
| `description` | TEXT | Yes | NULL | Feature description |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:** `service_id` -> `services(id)` ON DELETE CASCADE
**Indexes:** None (inline FK)

---

### 27. service_benefits

**Purpose:** Benefit cards for a service (icon, title, description).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `service_id` | INT UNSIGNED | No | | FK to services |
| `icon` | VARCHAR(100) | Yes | NULL | Icon identifier |
| `title` | VARCHAR(200) | Yes | NULL | Benefit title |
| `description` | TEXT | Yes | NULL | Benefit description |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:** `service_id` -> `services(id)` ON DELETE CASCADE
**Indexes:** None (inline FK)

---

### 28. service_process_steps

**Purpose:** Process/workflow steps for a service.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `service_id` | INT UNSIGNED | No | | FK to services |
| `icon` | VARCHAR(100) | Yes | NULL | Icon identifier |
| `title` | VARCHAR(200) | Yes | NULL | Step title |
| `description` | TEXT | Yes | NULL | Step description |
| `sort_order` | INT UNSIGNED | No | `0` | Step order |

**Primary Key:** `id`
**Foreign Keys:** `service_id` -> `services(id)` ON DELETE CASCADE
**Indexes:** None (inline FK)

---

### 29. service_faqs

**Purpose:** FAQ items for a service page.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `service_id` | INT UNSIGNED | No | | FK to services |
| `question` | VARCHAR(300) | Yes | NULL | FAQ question |
| `answer` | TEXT | Yes | NULL | FAQ answer |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:** `service_id` -> `services(id)` ON DELETE CASCADE
**Indexes:** None (inline FK)

---

### 30. service_portfolio

**Purpose:** Junction table linking portfolio items to a service.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `service_id` | INT UNSIGNED | No | | FK to services |
| `portfolio_item_id` | INT UNSIGNED | No | | FK to portfolio_items |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:**
- `service_id` -> `services(id)` ON DELETE CASCADE
- `portfolio_item_id` -> `portfolio_items(id)` ON DELETE CASCADE
**Indexes:** None (inline FKs)

---

### 31. service_testimonials

**Purpose:** Junction table linking testimonial entries to a service.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `service_id` | INT UNSIGNED | No | | FK to services |
| `testimonial_entry_id` | INT UNSIGNED | Yes | NULL | FK to content_entries (testimonial content type) |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:** `service_id` -> `services(id)` ON DELETE CASCADE
**Indexes:** None (inline FK)

**Note:** `testimonial_entry_id` references `content_entries(id)` for entries belonging to a content type with slug `testimonials`. This FK is not formally declared in the schema but is enforced at the application level.

---

### 32. service_related_blocks

**Purpose:** Junction table linking global blocks to a service.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `service_id` | INT UNSIGNED | No | | FK to services |
| `global_block_id` | INT UNSIGNED | No | | FK to global_blocks |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:**
- `service_id` -> `services(id)` ON DELETE CASCADE
- `global_block_id` -> `global_blocks(id)` ON DELETE CASCADE
**Indexes:** None (inline FKs)

---

### 33. portfolio_gallery

**Purpose:** Image gallery per portfolio project with type classification.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `portfolio_id` | INT UNSIGNED | No | | FK to portfolio_items |
| `image_url` | VARCHAR(500) | No | | Image URL |
| `caption` | VARCHAR(300) | Yes | NULL | Image caption |
| `image_type` | ENUM('before','after','screenshot','web_screenshot','mobile_screenshot','mockup','general') | No | `'general'` | Image classification |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:** `portfolio_id` -> `portfolio_items(id)` ON DELETE CASCADE
**Indexes:** None (inline FK)

---

### 34. portfolio_testimonials

**Purpose:** Junction table linking testimonial entries to a portfolio project.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `portfolio_id` | INT UNSIGNED | No | | FK to portfolio_items |
| `testimonial_entry_id` | INT UNSIGNED | Yes | NULL | FK to content_entries (testimonial content type) |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:** `portfolio_id` -> `portfolio_items(id)` ON DELETE CASCADE
**Indexes:** None (inline FK)

**Note:** `testimonial_entry_id` references `content_entries(id)` via a content type with slug `testimonials`. Enforced at the application level.

---

### 35. portfolio_related_services

**Purpose:** Junction table linking services to a portfolio project.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `portfolio_id` | INT UNSIGNED | No | | FK to portfolio_items |
| `service_id` | INT UNSIGNED | No | | FK to services |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:**
- `portfolio_id` -> `portfolio_items(id)` ON DELETE CASCADE
- `service_id` -> `services(id)` ON DELETE CASCADE
**Indexes:** None (inline FKs)

---

### 36. portfolio_faqs

**Purpose:** FAQ items for a portfolio project page.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `portfolio_id` | INT UNSIGNED | No | | FK to portfolio_items |
| `question` | VARCHAR(300) | Yes | NULL | FAQ question |
| `answer` | TEXT | Yes | NULL | FAQ answer |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:** `portfolio_id` -> `portfolio_items(id)` ON DELETE CASCADE
**Indexes:** None (inline FK)

---

### 37. portfolio_related_blocks

**Purpose:** Junction table linking global blocks to a portfolio project.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `portfolio_id` | INT UNSIGNED | No | | FK to portfolio_items |
| `global_block_id` | INT UNSIGNED | No | | FK to global_blocks |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:**
- `portfolio_id` -> `portfolio_items(id)` ON DELETE CASCADE
- `global_block_id` -> `global_blocks(id)` ON DELETE CASCADE
**Indexes:** None (inline FKs)

---

### 38. search_index

**Purpose:** Normalized full-text search index across all published content types. Kept in sync automatically via `SearchIndexer` hooks in the content managers.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `content_type` | VARCHAR(30) | No | | Source type: page, service, portfolio, blog_post, content_entry, global_block, faq |
| `content_id` | INT UNSIGNED | No | | ID of the source row |
| `title` | VARCHAR(255) | No | | Searchable title |
| `excerpt` | TEXT | Yes | NULL | Short description / meta description |
| `content` | MEDIUMTEXT | Yes | NULL | Full searchable body text |
| `keywords` | VARCHAR(500) | Yes | NULL | Focus keyword / extra keywords |
| `url` | VARCHAR(500) | No | | Canonical URL of the content |
| `image` | VARCHAR(500) | Yes | NULL | Featured image URL |
| `category` | VARCHAR(150) | Yes | NULL | Category label (for filtering) |
| `tags` | VARCHAR(500) | Yes | NULL | Comma-separated tags |
| `is_featured` | TINYINT(1) | No | `0` | Featured flag |
| `published_at` | DATETIME | Yes | NULL | Content publish date |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Index row creation time |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Index row update time |

**Primary Key:** `id`
**Unique Key:** `uk_search_content` (content_type, content_id)
**Indexes:**
- `idx_search_type` (content_type)
- `idx_search_category` (category)
- `idx_search_published` (published_at)
- `idx_search_featured` (is_featured)
**Full-Text Index:** `ft_search` (title, excerpt, content, keywords, tags, category)

### 39. search_logs

**Purpose:** Records every executed search query for analytics and popularity tracking.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `query` | VARCHAR(255) | No | | Original query text |
| `normalized_query` | VARCHAR(255) | No | | Lowercased/trimmed query for aggregation |
| `result_count` | INT UNSIGNED | No | `0` | Number of results returned |
| `is_zero_result` | TINYINT(1) | No | `0` | Whether the query returned no results |
| `ip_address` | VARCHAR(45) | No | `''` | Client IP (v4 or v6) |
| `user_agent` | VARCHAR(500) | No | `''` | Client user agent |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Search timestamp |

**Primary Key:** `id`
**Indexes:**
- `idx_search_query` (normalized_query)
- `idx_search_created` (created_at)
- `idx_search_zero` (is_zero_result)

### 40. search_result_clicks

**Purpose:** Tracks which results users click after a search, enabling "most viewed results" analytics.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `search_log_id` | INT UNSIGNED | Yes | NULL | FK to search_logs |
| `query` | VARCHAR(255) | No | `''` | Query at click time |
| `content_type` | VARCHAR(30) | No | | Index content type clicked |
| `content_id` | INT UNSIGNED | No | | Index content id clicked |
| `content_title` | VARCHAR(255) | No | `''` | Title at click time |
| `url` | VARCHAR(500) | No | `''` | Result URL clicked |
| `position` | INT UNSIGNED | No | `0` | Result rank (1-based) |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Click timestamp |

**Primary Key:** `id`
**Indexes:**
- `idx_click_log` (search_log_id)
- `idx_click_content` (content_type, content_id)

**Settings Keys:** `search_logging_enabled`, `search_min_query_length`, `search_results_per_page`, `search_suggestions_enabled`, `search_page_seo_title`, `search_page_meta_description`, `search_page_noindex`, `search_fuzzy_threshold`, `search_recent_limit`, `search_popular_limit`, `search_rate_limit_attempts`, `search_rate_limit_window`

---

### 41. about_sections

**Purpose:** Stores the About page section definitions and their JSON configuration. Seeded with defaults by `AboutManager::seedDefaults()`.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `section_key` | VARCHAR(30) | No | | Unique section identifier (hero, personal_intro, my_story, ...) |
| `section_label` | VARCHAR(100) | No | | Human-readable section name |
| `config` | JSON | No | | Section configuration (fields, repeater data, toggles) |
| `is_enabled` | TINYINT(1) | No | `1` | Whether the section renders on the frontend |
| `sort_order` | INT UNSIGNED | No | `0` | Display order on the About page |
| `status` | ENUM('draft','published') | No | `'draft'` | Publish state |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Row creation time |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Row update time |

**Primary Key:** `id`
**Unique Key:** `section_key`
**Foreign Keys:** None
**Indexes:** `idx_section_key`, `idx_enabled_order` (is_enabled, sort_order), `idx_status`

### 42. about_revisions

**Purpose:** Tracks About section config changes over time for rollback in the admin editor.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `section_id` | INT UNSIGNED | No | | FK to about_sections |
| `config_snapshot` | JSON | No | | Full config state at revision time |
| `revision_note` | VARCHAR(255) | Yes | NULL | Optional change description |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Revision time |

**Primary Key:** `id`
**Foreign Keys:** `fk_about_rev_section` (section_id) → `about_sections(id)` ON DELETE CASCADE
**Indexes:** `idx_section` (section_id)

### 43. about_related_portfolio

**Purpose:** Links the About page to featured portfolio items (one link per item).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `portfolio_item_id` | INT UNSIGNED | No | | FK to portfolio_items |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Unique Key:** `uq_about_portfolio` (portfolio_item_id)
**Foreign Keys:** `fk_about_portfolio_item` (portfolio_item_id) → `portfolio_items(id)` ON DELETE CASCADE

### 44. about_related_blocks

**Purpose:** Links the About page to global blocks for reusable content sections.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `global_block_id` | INT UNSIGNED | No | | FK to global_blocks |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Unique Key:** `uq_about_block` (global_block_id)
**Foreign Keys:** `fk_about_global_block` (global_block_id) → `global_blocks(id)` ON DELETE CASCADE

### 45. contact_sections

**Purpose:** Stores the Contact page section definitions and their JSON configuration (hero, contact_info, contact_form, whatsapp_cta, faq, final_cta).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `section_key` | VARCHAR(30) | No | | Unique section identifier |
| `section_label` | VARCHAR(100) | No | | Human-readable section name |
| `config` | JSON | No | | Section configuration |
| `is_enabled` | TINYINT(1) | No | `1` | Whether the section renders on the frontend |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |
| `status` | ENUM('draft','published') | No | `'draft'` | Publish state |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Row creation time |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Row update time |

**Primary Key:** `id`
**Unique Key:** `section_key`
**Foreign Keys:** None
**Indexes:** `idx_section_key`, `idx_enabled_order` (is_enabled, sort_order), `idx_status`

### 46. leads

**Purpose:** Stores qualified contact form submissions (lead pipeline) with a 7-status workflow and rich metadata for the CRM admin.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `full_name` | VARCHAR(150) | No | | Lead name |
| `email` | VARCHAR(254) | No | | Lead email |
| `phone` | VARCHAR(30) | Yes | NULL | Contact phone |
| `company` | VARCHAR(150) | Yes | NULL | Company name |
| `service_id` | INT UNSIGNED | Yes | NULL | FK to services |
| `budget` | VARCHAR(50) | Yes | NULL | Budget band |
| `timeline` | VARCHAR(50) | Yes | NULL | Desired timeline |
| `subject` | VARCHAR(255) | Yes | NULL | Message subject |
| `message` | TEXT | No | | Message body |
| `uploaded_files` | JSON | Yes | NULL | Attached file metadata |
| `source_page` | VARCHAR(500) | Yes | NULL | Page the form was submitted from |
| `referrer_url` | VARCHAR(500) | Yes | NULL | HTTP referrer |
| `ip_address` | VARCHAR(45) | Yes | NULL | Client IP |
| `user_agent` | VARCHAR(500) | Yes | NULL | Client user agent |
| `device_type` | VARCHAR(20) | Yes | NULL | mobile / tablet / desktop |
| `status` | ENUM('new','contacted','follow_up','proposal_sent','won','lost','archived') | No | `'new'` | Lead pipeline status |
| `consent_given` | TINYINT(1) | No | `0` | GDPR-style consent flag |
| `email_sent` | TINYINT(1) | No | `0` | Whether a confirmation email was sent |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Submission time |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update time |

**Primary Key:** `id`
**Foreign Keys:** `fk_lead_service` (service_id) → `services(id)` ON DELETE SET NULL
**Indexes:** `idx_status`, `idx_email`, `idx_created`, `idx_service` (service_id)

### 47. lead_notes

**Purpose:** Internal notes added by admin users against a lead (visible in the lead detail view).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `lead_id` | INT UNSIGNED | No | | FK to leads |
| `user_id` | INT UNSIGNED | Yes | NULL | FK to users (note author) |
| `note` | TEXT | No | | Note content |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Note time |

**Primary Key:** `id`
**Foreign Keys:** `fk_note_lead` (lead_id) → `leads(id)` ON DELETE CASCADE
**Indexes:** `idx_lead` (lead_id)

### 48. email_templates

**Purpose:** Editable email templates (subject + HTML + text body) with variable replacement, seeded with `admin_notification` and `user_confirmation`.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `template_key` | VARCHAR(50) | No | | Unique template identifier |
| `name` | VARCHAR(150) | No | | Display name |
| `subject` | VARCHAR(255) | No | | Subject line with `{{variables}}` |
| `body_html` | TEXT | No | | HTML body with `{{variables}}` |
| `body_text` | TEXT | No | | Plain-text fallback body |
| `variables_help` | TEXT | Yes | NULL | Docs string of available variables |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last edit time |

**Primary Key:** `id`
**Unique Key:** `template_key`
**Foreign Keys:** None
**Indexes:** `idx_key` (template_key)

### 49. email_log

**Purpose:** Audit log of every email sent (or failed) by EmailManager for admin review and debugging.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `lead_id` | INT UNSIGNED | Yes | NULL | FK to leads (nullable for non-lead emails) |
| `recipient` | VARCHAR(254) | No | | Recipient address |
| `subject` | VARCHAR(255) | No | | Sent subject |
| `template_key` | VARCHAR(50) | Yes | NULL | Template used |
| `status` | ENUM('sent','failed') | No | `'sent'` | Delivery result |
| `error_message` | TEXT | Yes | NULL | Error detail on failure |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Send time |

**Primary Key:** `id`
**Foreign Keys:** `fk_emaillog_lead` (lead_id) → `leads(id)` ON DELETE SET NULL
**Indexes:** `idx_lead` (lead_id), `idx_created` (created_at)

### 50. blog_categories

**Purpose:** Hierarchical blog taxonomy categories.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(150) | No | | Category name |
| `slug` | VARCHAR(150) | No | | URL slug (unique) |
| `description` | TEXT | Yes | NULL | Category description |
| `parent_id` | INT UNSIGNED | Yes | NULL | FK to blog_categories (self-referencing) |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation time |

**Primary Key:** `id`
**Unique Key:** `slug`
**Foreign Keys:** `fk_blogcat_parent` (parent_id) → `blog_categories(id)` ON DELETE SET NULL
**Indexes:** `idx_slug`, `idx_parent` (parent_id)

### 51. blog_tags

**Purpose:** Flat blog taxonomy tags.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(100) | No | | Tag name |
| `slug` | VARCHAR(100) | No | | URL slug (unique) |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation time |

**Primary Key:** `id`
**Unique Key:** `slug`
**Foreign Keys:** None
**Indexes:** `idx_slug`

### 52. blog_posts

**Purpose:** Blog / knowledge-base articles with a 4-status workflow, SEO fields, and engagement tracking.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `title` | VARCHAR(255) | No | | Post title |
| `slug` | VARCHAR(255) | No | | URL slug (unique) |
| `short_description` | TEXT | Yes | NULL | Card / archive excerpt |
| `content` | LONGTEXT | No | | Full article HTML |
| `featured_image` | VARCHAR(500) | Yes | NULL | Featured image URL |
| `gallery` | JSON | Yes | NULL | Additional gallery images |
| `author_id` | INT UNSIGNED | Yes | NULL | FK to users (author) |
| `published_at` | DATETIME | Yes | NULL | Publish/schedule time |
| `status` | ENUM('draft','published','scheduled','archived') | No | `'draft'` | Post state |
| `reading_time` | SMALLINT UNSIGNED | No | `1` | Estimated minutes to read |
| `is_featured` | TINYINT(1) | No | `0` | Featured flag |
| `is_sticky` | TINYINT(1) | No | `0` | Sticky at top of listing |
| `seo_title` | VARCHAR(255) | Yes | NULL | Custom SEO title |
| `meta_description` | VARCHAR(320) | Yes | NULL | Custom meta description |
| `focus_keyword` | VARCHAR(255) | Yes | NULL | Target keyword |
| `canonical_url` | VARCHAR(500) | Yes | NULL | Override canonical URL |
| `og_image` | VARCHAR(500) | Yes | NULL | Social share image |
| `twitter_card` | VARCHAR(30) | Yes | `'summary_large_image'` | Twitter card type |
| `view_count` | INT UNSIGNED | No | `0` | View counter |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Creation time |
| `updated_at` | DATETIME | No | CURRENT_TIMESTAMP ON UPDATE | Last update time |

**Primary Key:** `id`
**Unique Key:** `slug`
**Foreign Keys:** `fk_blogpost_author` (author_id) → `users(id)` ON DELETE SET NULL
**Indexes:** `idx_slug`, `idx_status`, `idx_published` (published_at), `idx_author` (author_id), `idx_featured` (is_featured), `idx_sticky` (is_sticky)

### 53. blog_post_categories

**Purpose:** Junction table linking blog posts to categories (many-to-many).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `post_id` | INT UNSIGNED | No | | FK to blog_posts |
| `category_id` | INT UNSIGNED | No | | FK to blog_categories |

**Primary Key:** (post_id, category_id)
**Foreign Keys:** `fk_bpc_post` (post_id) → `blog_posts(id)` ON DELETE CASCADE, `fk_bpc_cat` (category_id) → `blog_categories(id)` ON DELETE CASCADE

### 54. blog_post_tags

**Purpose:** Junction table linking blog posts to tags (many-to-many).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `post_id` | INT UNSIGNED | No | | FK to blog_posts |
| `tag_id` | INT UNSIGNED | No | | FK to blog_tags |

**Primary Key:** (post_id, tag_id)
**Foreign Keys:** `fk_bpt_post` (post_id) → `blog_posts(id)` ON DELETE CASCADE, `fk_bpt_tag` (tag_id) → `blog_tags(id)` ON DELETE CASCADE

### 55. blog_revisions

**Purpose:** Tracks blog post content snapshots for rollback in the editor.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `post_id` | INT UNSIGNED | No | | FK to blog_posts |
| `title` | VARCHAR(255) | No | | Title at revision time |
| `content` | LONGTEXT | No | | Content snapshot |
| `short_description` | TEXT | Yes | NULL | Excerpt snapshot |
| `revision_note` | VARCHAR(255) | Yes | NULL | Optional change description |
| `saved_by` | INT UNSIGNED | Yes | NULL | FK to users (editor) |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Revision time |

**Primary Key:** `id`
**Foreign Keys:** `fk_brev_post` (post_id) → `blog_posts(id)` ON DELETE CASCADE, `fk_brev_user` (saved_by) → `users(id)` ON DELETE SET NULL
**Indexes:** `idx_post` (post_id)

### 56. blog_relationships

**Purpose:** Generic related-content links from a blog post to other entities (portfolio, services, pages, posts).

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `post_id` | INT UNSIGNED | No | | FK to blog_posts |
| `related_type` | VARCHAR(30) | No | | Target entity type (portfolio, service, ...) |
| `related_id` | INT UNSIGNED | No | | Target entity ID |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:** `fk_brel_post` (post_id) → `blog_posts(id)` ON DELETE CASCADE
**Indexes:** `idx_post` (post_id), `idx_related` (related_type, related_id)

### 57. portfolio_revisions

**Purpose:** Tracks portfolio project data snapshots for rollback in the editor.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `portfolio_id` | INT UNSIGNED | No | | FK to portfolio_items |
| `data_snapshot` | JSON | No | | Full project data snapshot |
| `revision_note` | VARCHAR(255) | Yes | NULL | Optional change description |
| `created_at` | DATETIME | No | CURRENT_TIMESTAMP | Revision time |

**Primary Key:** `id`
**Foreign Keys:** `fk_pf_rev_portfolio` (portfolio_id) → `portfolio_items(id)` ON DELETE CASCADE
**Indexes:** `idx_portfolio` (portfolio_id)

### 58. portfolio_related_blog

**Purpose:** Links portfolio projects to related blog posts.

| Column | Type | Null | Default | Description |
|---|---|---|---|---|
| `id` | INT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `portfolio_id` | INT UNSIGNED | No | | FK to portfolio_items |
| `blog_post_id` | INT UNSIGNED | No | | FK to blog_posts |
| `sort_order` | INT UNSIGNED | No | `0` | Display order |

**Primary Key:** `id`
**Foreign Keys:** (portfolio_id) → `portfolio_items(id)` ON DELETE CASCADE, (blog_post_id) → `blog_posts(id)` ON DELETE CASCADE

---

## Naming Conventions

| Convention | Rule | Example |
|---|---|---|
| **Table names** | `snake_case`, plural or descriptive | `portfolio_items`, `service_features`, `login_attempts` |
| **Column names** | `snake_case`, descriptive | `password_hash`, `is_featured`, `sort_order` |
| **Primary keys** | `id` (INT UNSIGNED AUTO_INCREMENT) | All tables |
| **Foreign keys** | `{referenced_table}_id` or descriptive singular | `user_id`, `content_type_id`, `portfolio_id` |
| **Junction tables** | `{parent}_{related}` (alphabetical order) | `service_portfolio`, `portfolio_related_services` |
| **Timestamps** | `created_at`, `updated_at` (DATETIME) | Standard on most tables |
| **Boolean flags** | `TINYINT(1)` with `is_` or `has_` prefix | `is_enabled`, `is_trashed`, `has_webp`, `has_thumb` |
| **Status columns** | `ENUM` with lowercase values | `ENUM('draft','published')` |
| **Index names** | `idx_{purpose}` or `idx_{columns}` | `idx_status`, `idx_location_sort` |
| **Unique keys** | `uk_{columns}` | `uk_page_id`, `uk_page_section_zone` |
| **FK constraint names** | `fk_{child}_{parent}` | `fk_remember_user`, `fk_block_usage_block` |

## Database Standards

### Engine & Charset

- **Engine:** InnoDB (all tables) -- supports transactions, foreign keys, row-level locking
- **Charset:** `utf8mb4` -- full Unicode support including emoji
- **Collation:** `utf8mb4_unicode_ci` -- case-insensitive, Unicode-aware comparisons

### Data Types Used

| Data Type | Usage |
|---|---|
| `INT UNSIGNED` | Primary keys, foreign keys, counters, sort orders |
| `TINYINT(1)` | Boolean flags (0/1) |
| `VARCHAR(N)` | Short strings (names, slugs, emails, URLs) |
| `TEXT` | Medium-length content (descriptions, summaries, answers) |
| `LONGTEXT` | Large content (page bodies, full descriptions) |
| `JSON` | Flexible structured data (config, fields, zones, content) |
| `ENUM` | Fixed-set status/type values |
| `DATETIME` | Timestamps (creation, updates, scheduling, expiry) |
| `DATE` | Date-only values (completion_date) |
| `SMALLINT` | HTTP status codes for redirects |

### Key Design Patterns

- **Cascade deletes** on all dependent sub-tables (features, benefits, FAQs, gallery, junctions) ensure no orphaned records
- **Junction tables** for many-to-many relationships use `sort_order` for manual ordering control
- **JSON columns** for flexible, schema-less data (homepage section config, block content, content entry fields)
- **Self-referencing FKs** (`navigation_menu.parent_id`, `media_folders.parent_id`) support hierarchical structures
- **Soft deletes** via `is_trashed` flag on media_library rather than hard deletes
- **Schedule columns** (`schedule_start`, `schedule_end`) on global_blocks and page_sections support time-based content
- **Usage tracking** via `hit_count` on redirects, `usage_count` on global blocks, `is_read` on contact messages

## Migration Notes

### Version Tracking

The schema is designed for a single `schema.sql` migration approach suitable for shared hosting (MySQL 5.7+ / MariaDB 10.3+). Every `CREATE TABLE` uses `IF NOT EXISTS`, and table alterations use portable `MODIFY COLUMN` / `ALTER TABLE` syntax only -- no MariaDB-only `ADD COLUMN IF NOT EXISTS`. The file can be re-imported safely on an existing database; existing tables are skipped.

### Applying Changes

1. Backup current database via phpMyAdmin or mysqldump
2. Run `database/schema.sql` against the database -- existing tables are skipped
3. New columns on existing tables are handled via explicit `MODIFY COLUMN` statements that must match the running version

### Adding New Tables

When adding a module, append to `database/schema.sql` using this template:

```sql
-- -------------------------------------------------------
-- [Module Name] Table: {table_name}
-- {purpose description}
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS {table_name} (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- columns here
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (...) REFERENCES ... ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Future Tables Reserved

The following tables are reserved for planned modules and should not be created prematurely:

| Module | Tables Planned | Purpose |
|---|---|---|
| **Testimonials** | `testimonials` (dedicated), `testimonial_ratings` | Standalone testimonial management with star ratings (currently testimonial blocks reference `content_entries`) |
| **User Management** | `user_roles`, `user_permissions`, `role_permissions` | Granular role-based access control (basic `role` column on `users` + `admin/users.php` already shipped) |
| **Newsletter** | `newsletter_subscribers`, `newsletter_campaigns`, `newsletter_sends` | Email list and campaign management |
| **SEO Dashboard** | `seo_audit_results`, `broken_links`, `keyword_rankings` | SEO monitoring and reporting |
| **Analytics** | `page_views`, `visitor_sessions`, `event_logs` | Built-in analytics tracking |
| **Backups** | `backup_jobs`, `backup_files` | Backup job tracking |
| **i18n** | `languages`, `translations`, `translation_strings` | Multi-language support |
