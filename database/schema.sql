-- ============================================================
-- Swap Design - Database Schema
-- MySQL 5.7+ / MariaDB 10.3+ (Hostinger compatible)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------
-- Table: contact_messages
-- Stores contact form submissions
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)    NOT NULL,
    email       VARCHAR(254)    NOT NULL,
    subject     VARCHAR(200)    NOT NULL DEFAULT 'General Inquiry',
    message     TEXT            NOT NULL,
    ip_address  VARCHAR(45)     NOT NULL DEFAULT '',
    is_read     TINYINT(1)      NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_created_at (created_at),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: pages
-- Stores CMS page content (for future dynamic pages)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS pages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(100)    NOT NULL UNIQUE,
    title       VARCHAR(200)    NOT NULL,
    meta_desc   VARCHAR(320)    NOT NULL DEFAULT '',
    content     LONGTEXT        NOT NULL,
    status      ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: portfolio_items (for future portfolio section)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS portfolio_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200)    NOT NULL,
    slug        VARCHAR(200)    NOT NULL UNIQUE,
    description TEXT            NOT NULL,
    image_url   VARCHAR(500)    NOT NULL DEFAULT '',
    category    VARCHAR(100)    NOT NULL DEFAULT '',
    sort_order  INT UNSIGNED    NOT NULL DEFAULT 0,
    is_featured TINYINT(1)      NOT NULL DEFAULT 0,
    status      ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: site_settings
-- Key-value store for site-wide configuration
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key   VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: users
-- Admin user accounts for the CMS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(254)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    name          VARCHAR(100)  NOT NULL,
    role          ENUM('admin', 'editor') NOT NULL DEFAULT 'admin',
    status        ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_login_at DATETIME      NULL DEFAULT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: login_attempts
-- Tracks login attempts for rate limiting
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(254)  NOT NULL,
    ip_address   VARCHAR(45)   NOT NULL,
    attempted_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    success      TINYINT(1)    NOT NULL DEFAULT 0,

    INDEX idx_lookup (email, ip_address, attempted_at),
    INDEX idx_cleanup (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: remember_tokens
-- Split-token remember-me authentication
-- selector: stored in cookie and DB (plaintext for lookup)
-- token_hash: SHA-256 of the actual token (only hash in DB)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS remember_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED  NOT NULL,
    selector    VARCHAR(64)   NOT NULL UNIQUE,
    token_hash  VARCHAR(128)  NOT NULL,
    expires_at  DATETIME      NOT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_selector (selector),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at),

    CONSTRAINT fk_remember_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Seed: Default admin user
-- Password: admin123 (CHANGE IMMEDIATELY after install)
-- Hash generated with: password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12])
-- -------------------------------------------------------
-- INSERT INTO users (email, password_hash, name, role, status)
-- VALUES ('admin@swapdesign.com', '$2y$12$changeme', 'Admin', 'admin', 'active');

-- ============================================================
-- Sprint 2 Tables
-- ============================================================

-- -------------------------------------------------------
-- Table: navigation_menu
-- Editable navigation menu items with drag-drop ordering
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS navigation_menu (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id   INT UNSIGNED  NULL DEFAULT NULL,
    label       VARCHAR(100)  NOT NULL,
    url         VARCHAR(255)  NOT NULL DEFAULT '#',
    slug        VARCHAR(100)  NOT NULL DEFAULT '',
    location    VARCHAR(30)   NOT NULL DEFAULT 'primary',
    sort_order  INT UNSIGNED  NOT NULL DEFAULT 0,
    is_visible  TINYINT(1)    NOT NULL DEFAULT 1,
    open_new_tab TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_location_sort (location, sort_order),
    INDEX idx_parent (parent_id),
    CONSTRAINT fk_nav_parent
        FOREIGN KEY (parent_id) REFERENCES navigation_menu(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: footer_links
-- Editable footer link groups
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS footer_links (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_name  VARCHAR(50)   NOT NULL,
    label       VARCHAR(100)  NOT NULL,
    url         VARCHAR(255)  NOT NULL DEFAULT '#',
    sort_order  INT UNSIGNED  NOT NULL DEFAULT 0,
    is_visible  TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_group_sort (group_name, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: footer_settings
-- Footer-wide configuration (copyright, social links)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS footer_settings (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    copyright_text VARCHAR(500) NOT NULL DEFAULT '&copy; {year} Swap Design. All rights reserved.',
    updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: global_blocks
-- Reusable content blocks (CTA, FAQ, testimonials, etc.)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS global_blocks (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(200)  NOT NULL,
    slug            VARCHAR(200)  NOT NULL UNIQUE,
    block_type      VARCHAR(50)   NOT NULL DEFAULT 'custom_html',
    description     TEXT          NULL,
    content         JSON          NOT NULL,
    status          ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    category        VARCHAR(50)   NULL,
    full_width      TINYINT(1)    NOT NULL DEFAULT 0,
    background_color VARCHAR(20)  NULL,
    custom_css      TEXT          NULL,
    device_visibility ENUM('all','desktop','tablet','mobile') NOT NULL DEFAULT 'all',
    schedule_start  DATETIME      NULL,
    schedule_end    DATETIME      NULL,
    usage_count     INT UNSIGNED  NOT NULL DEFAULT 0,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_type (block_type),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: global_block_usage
-- Tracks where each global block is used
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS global_block_usage (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    block_id        INT UNSIGNED  NOT NULL,
    page_id         INT UNSIGNED  NULL,
    page_slug       VARCHAR(200)  NOT NULL,
    section_key     VARCHAR(100)  NOT NULL DEFAULT 'content',
    sort_order      INT UNSIGNED  NOT NULL DEFAULT 0,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_block (block_id),
    INDEX idx_page (page_slug),
    CONSTRAINT fk_block_usage_block
        FOREIGN KEY (block_id) REFERENCES global_blocks(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: media_library
-- Centralized media asset storage
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS media_library (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename        VARCHAR(255)  NOT NULL,
    original_name   VARCHAR(255)  NOT NULL,
    mime_type       VARCHAR(100)  NOT NULL,
    file_size       INT UNSIGNED  NOT NULL DEFAULT 0,
    file_hash       VARCHAR(64)   NOT NULL,
    alt_text        VARCHAR(255)  NOT NULL DEFAULT '',
    title           VARCHAR(255)  NOT NULL DEFAULT '',
    caption         TEXT          NULL,
    folder_id       INT UNSIGNED  NULL,
    width           INT UNSIGNED  NULL,
    height          INT UNSIGNED  NULL,
    dominant_color  VARCHAR(7)    NULL,
    has_webp        TINYINT(1)    NOT NULL DEFAULT 0,
    has_thumb       TINYINT(1)    NOT NULL DEFAULT 0,
    is_trashed      TINYINT(1)    NOT NULL DEFAULT 0,
    trashed_at      DATETIME      NULL,
    uploaded_by     INT UNSIGNED  NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_hash (file_hash),
    INDEX idx_type (mime_type),
    INDEX idx_trashed (is_trashed),
    INDEX idx_folder (folder_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: media_folders
-- Virtual folder structure for media organization
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS media_folders (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id   INT UNSIGNED  NULL,
    name        VARCHAR(100)  NOT NULL,
    slug        VARCHAR(100)  NOT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_parent (parent_id),
    UNIQUE KEY idx_parent_slug (parent_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: global_components
-- Registry of reusable frontend components
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS global_components (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)  NOT NULL UNIQUE,
    slug            VARCHAR(100)  NOT NULL UNIQUE,
    category        VARCHAR(50)   NOT NULL DEFAULT 'general',
    description     TEXT          NULL,
    template_path   VARCHAR(255)  NOT NULL,
    css_path        VARCHAR(255)  NULL,
    js_path         VARCHAR(255)  NULL,
    default_config  JSON          NULL,
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Sprint 3] Table: content_types
-- Custom content type definitions with JSON field schemas
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS content_types (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)    NOT NULL,
    slug            VARCHAR(100)    NOT NULL UNIQUE,
    description     TEXT            NULL,
    fields_schema   JSON            NULL COMMENT 'JSON array of field definitions [{name, label, type, required, options}]',
    icon            VARCHAR(50)     NOT NULL DEFAULT 'file',
    is_system       TINYINT(1)      NOT NULL DEFAULT 0,
    has_entries     TINYINT(1)      NOT NULL DEFAULT 1,
    list_template   VARCHAR(255)    NULL COMMENT 'Path to list/archive template',
    single_template VARCHAR(255)    NULL COMMENT 'Path to single entry template',
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Sprint 3] Table: content_entries
-- Content records for each content type
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS content_entries (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_type_id INT UNSIGNED    NOT NULL,
    title           VARCHAR(255)    NOT NULL,
    slug            VARCHAR(255)    NOT NULL,
    fields          JSON            NULL COMMENT 'Field values as JSON key-value pairs',
    excerpt         TEXT            NULL,
    featured_image  VARCHAR(500)    NULL,
    status          ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,
    created_by      INT UNSIGNED    NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_type (content_type_id),
    INDEX idx_status (status),
    INDEX idx_slug (slug),
    INDEX idx_sort (sort_order),
    FOREIGN KEY (content_type_id) REFERENCES content_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Sprint 3] Table: layouts
-- Page structural templates with named zone definitions
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS layouts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)    NOT NULL,
    slug            VARCHAR(100)    NOT NULL UNIQUE,
    description     TEXT            NULL,
    zones           JSON            NOT NULL COMMENT 'JSON array of zones [{key, label, allowed_sections}]',
    template_path   VARCHAR(255)    NOT NULL DEFAULT '',
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    is_default      TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Sprint 3] Table: sections
-- Reusable page content sections
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS sections (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)    NOT NULL,
    slug            VARCHAR(150)    NOT NULL UNIQUE,
    section_type    ENUM('custom_html','global_block','component','content_entries','dynamic_list','shortcode') NOT NULL DEFAULT 'custom_html',
    config          JSON            NOT NULL COMMENT 'Type-specific configuration (html, block_id, component_name, content_type_id, query, etc.)',
    status          ENUM('draft','published') NOT NULL DEFAULT 'draft',
    category        VARCHAR(50)     NULL,
    description     TEXT            NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_type (section_type),
    INDEX idx_status (status),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Sprint 3] Table: page_sections
-- Junction: which sections appear on which pages, in which zone, ordered
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS page_sections (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id         INT UNSIGNED    NOT NULL,
    section_id      INT UNSIGNED    NOT NULL,
    zone_key        VARCHAR(50)     NOT NULL DEFAULT 'content',
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,
    is_enabled      TINYINT(1)      NOT NULL DEFAULT 1,
    custom_config   JSON            NULL COMMENT 'Per-page overrides for section config',
    schedule_start  DATETIME        NULL,
    schedule_end    DATETIME        NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_page_zone (page_id, zone_key),
    INDEX idx_page_sort (page_id, sort_order),
    UNIQUE KEY uk_page_section_zone (page_id, section_id, zone_key),
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Sprint 3] Table: url_redirects
-- Track slug changes for 301 redirects
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS url_redirects (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    old_slug        VARCHAR(255)    NOT NULL,
    new_slug        VARCHAR(255)    NOT NULL,
    redirect_type   ENUM('page','entry','custom') NOT NULL DEFAULT 'page',
    target_id       INT UNSIGNED    NULL,
    status_code     SMALLINT        NOT NULL DEFAULT 301,
    hit_count       INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_old_slug_type (old_slug, redirect_type),
    INDEX idx_new_slug (new_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [WhatsApp Integration] Table: whatsapp_page_overrides
-- Per-page WhatsApp button settings
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS whatsapp_page_overrides (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id             INT UNSIGNED    NOT NULL,
    is_enabled          TINYINT(1)      NOT NULL DEFAULT 1,
    custom_number       VARCHAR(30)     NULL,
    custom_message      VARCHAR(500)    NULL,
    position_override   ENUM('global','left','right') NOT NULL DEFAULT 'global',
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_page_id (page_id),
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [WhatsApp Integration] Table: whatsapp_clicks
-- Analytics: track every WhatsApp button click
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS whatsapp_clicks (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id             INT UNSIGNED    NULL,
    page_title          VARCHAR(200)    NULL,
    source              VARCHAR(100)    NULL COMMENT 'floating_button, hero_cta, service_card, portfolio_item, global_block, contact_page',
    source_label        VARCHAR(255)    NULL COMMENT 'e.g. service name, portfolio title',
    device_type         ENUM('desktop','tablet','mobile','unknown') NOT NULL DEFAULT 'unknown',
    visitor_ip          VARCHAR(45)     NULL,
    clicked_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_page (page_id),
    INDEX idx_source (source),
    INDEX idx_device (device_type),
    INDEX idx_clicked (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Sprint 3] Alter: pages table -- add layout_id, template, is_homepage flags
-- -------------------------------------------------------
ALTER TABLE pages
    ADD COLUMN layout_id      INT UNSIGNED NULL AFTER meta_desc,
    ADD COLUMN template       VARCHAR(100) NULL AFTER layout_id,
    ADD COLUMN is_homepage    TINYINT(1) NOT NULL DEFAULT 0 AFTER template,
    ADD COLUMN show_in_nav    TINYINT(1) NOT NULL DEFAULT 0 AFTER is_homepage,
    ADD COLUMN nav_label      VARCHAR(100) NULL AFTER show_in_nav,
    ADD INDEX idx_layout (layout_id),
    ADD INDEX idx_homepage (is_homepage);

-- -------------------------------------------------------
-- [Homepage] Table: homepage_sections
-- Ordered, editable sections that compose the homepage.
-- Each section stores its type-specific fields as JSON config.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS homepage_sections (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key     VARCHAR(50)     NOT NULL UNIQUE COMMENT 'hero, about, experience, services, why_choose, process, portfolio, testimonials, faq, cta, contact',
    label           VARCHAR(100)    NOT NULL,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,
    is_enabled      TINYINT(1)      NOT NULL DEFAULT 1,
    config          JSON            NOT NULL COMMENT 'All editable fields for this section',
    status          ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_order (sort_order),
    INDEX idx_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Services Module] Table: services
-- Each service is a dedicated page with sub-sections.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title                   VARCHAR(200)    NOT NULL,
    slug                    VARCHAR(200)    NOT NULL UNIQUE,
    short_description       TEXT            NULL,
    full_description        LONGTEXT        NULL,
    featured_image          VARCHAR(500)    NULL,
    icon                    VARCHAR(100)    NULL,
    category                VARCHAR(100)    NULL,
    sort_order              INT UNSIGNED    NOT NULL DEFAULT 0,
    status                  ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',

    -- Hero
    hero_title              VARCHAR(200)    NULL,
    hero_description        TEXT            NULL,
    hero_image              VARCHAR(500)    NULL,
    hero_bg_image           VARCHAR(500)    NULL,
    hero_cta_primary_text   VARCHAR(100)    NULL,
    hero_cta_primary_url    VARCHAR(300)    NULL,
    hero_cta_secondary_text VARCHAR(100)    NULL,
    hero_cta_secondary_url  VARCHAR(300)    NULL,

    -- Overview
    overview_intro          TEXT            NULL,
    overview_benefits       TEXT            NULL,
    overview_why            TEXT            NULL,

    -- CTA
    cta_heading             VARCHAR(200)    NULL,
    cta_description         TEXT            NULL,
    cta_button_text         VARCHAR(100)    NULL,
    cta_button_url          VARCHAR(300)    NULL,
    cta_show_whatsapp       TINYINT(1)      NOT NULL DEFAULT 0,
    cta_whatsapp_label      VARCHAR(100)    NULL,
    cta_bg_image            VARCHAR(500)    NULL,

    -- Contact
    contact_show_whatsapp   TINYINT(1)      NOT NULL DEFAULT 1,
    contact_show_phone      TINYINT(1)      NOT NULL DEFAULT 1,
    contact_show_email      TINYINT(1)      NOT NULL DEFAULT 1,
    contact_show_form       TINYINT(1)      NOT NULL DEFAULT 1,
    contact_button_text     VARCHAR(100)    NULL,
    contact_button_url      VARCHAR(300)    NULL,

    -- SEO
    seo_title               VARCHAR(200)    NULL,
    meta_description        VARCHAR(320)    NULL,
    focus_keyword           VARCHAR(200)    NULL,
    canonical_url           VARCHAR(500)    NULL,
    og_image                VARCHAR(500)    NULL,

    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Services Module] Table: service_features
-- Unlimited feature cards per service.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_features (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id  INT UNSIGNED    NOT NULL,
    icon        VARCHAR(100)    NULL,
    title       VARCHAR(200)    NULL,
    description TEXT            NULL,
    sort_order  INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Services Module] Table: service_benefits
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_benefits (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id  INT UNSIGNED    NOT NULL,
    icon        VARCHAR(100)    NULL,
    title       VARCHAR(200)    NULL,
    description TEXT            NULL,
    sort_order  INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Services Module] Table: service_process_steps
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_process_steps (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id  INT UNSIGNED    NOT NULL,
    icon        VARCHAR(100)    NULL,
    title       VARCHAR(200)    NULL,
    description TEXT            NULL,
    sort_order  INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Services Module] Table: service_faqs
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_faqs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id  INT UNSIGNED    NOT NULL,
    question    VARCHAR(300)    NULL,
    answer      TEXT            NULL,
    sort_order  INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Services Module] Table: service_portfolio
-- Related portfolio projects per service.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_portfolio (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id        INT UNSIGNED    NOT NULL,
    portfolio_item_id INT UNSIGNED    NOT NULL,
    sort_order        INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (portfolio_item_id) REFERENCES portfolio_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Services Module] Table: service_testimonials
-- Related testimonials per service.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_testimonials (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id            INT UNSIGNED    NOT NULL,
    testimonial_entry_id  INT UNSIGNED    NULL,
    sort_order            INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Services Module] Table: service_related_blocks
-- Related global blocks per service.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_related_blocks (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id      INT UNSIGNED    NOT NULL,
    global_block_id INT UNSIGNED    NOT NULL,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (global_block_id) REFERENCES global_blocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Portfolio Module] Alter: portfolio_items -- add new columns
-- Extends the existing portfolio_items table with hero,
-- overview, solution, results, SEO, and meta fields.
-- -------------------------------------------------------
ALTER TABLE portfolio_items
    ADD COLUMN client_name         VARCHAR(200)    NULL AFTER category,
    ADD COLUMN industry            VARCHAR(100)    NULL AFTER client_name,
    ADD COLUMN completion_date     DATE            NULL AFTER industry,
    ADD COLUMN project_url         VARCHAR(500)    NULL AFTER completion_date,
    ADD COLUMN full_description    LONGTEXT        NULL AFTER description,
    ADD COLUMN gallery_images      JSON            NULL COMMENT 'Legacy; prefer portfolio_gallery table' AFTER image_url,

    -- Hero
    ADD COLUMN hero_title          VARCHAR(200)    NULL AFTER project_url,
    ADD COLUMN hero_description    TEXT            NULL AFTER hero_title,
    ADD COLUMN hero_image          VARCHAR(500)    NULL AFTER hero_description,
    ADD COLUMN hero_bg_image       VARCHAR(500)    NULL AFTER hero_image,
    ADD COLUMN hero_cta_text       VARCHAR(100)    NULL AFTER hero_bg_image,
    ADD COLUMN hero_cta_url        VARCHAR(300)    NULL AFTER hero_cta_text,

    -- Overview
    ADD COLUMN overview_summary    TEXT            NULL AFTER hero_cta_url,
    ADD COLUMN overview_requirements TEXT         NULL AFTER overview_summary,
    ADD COLUMN overview_problem    TEXT            NULL AFTER overview_requirements,
    ADD COLUMN overview_objectives TEXT            NULL AFTER overview_problem,

    -- Solution
    ADD COLUMN solution_strategy   TEXT            NULL AFTER overview_objectives,
    ADD COLUMN solution_branding   TEXT            NULL AFTER solution_strategy,
    ADD COLUMN solution_process    TEXT            NULL AFTER solution_branding,
    ADD COLUMN solution_tech       TEXT            NULL AFTER solution_process,

    -- Results
    ADD COLUMN results_summary     TEXT            NULL AFTER solution_tech,
    ADD COLUMN results_achievements TEXT           NULL AFTER results_summary,
    ADD COLUMN results_feedback    TEXT            NULL AFTER results_achievements,

    -- Project Details
    ADD COLUMN project_duration    VARCHAR(100)    NULL AFTER results_feedback,
    ADD COLUMN project_deliverables TEXT           NULL AFTER project_duration,
    ADD COLUMN project_services_used VARCHAR(500)  NULL AFTER project_deliverables,

    -- CTA
    ADD COLUMN cta_heading         VARCHAR(200)    NULL AFTER project_services_used,
    ADD COLUMN cta_description     TEXT            NULL AFTER cta_heading,
    ADD COLUMN cta_button_text     VARCHAR(100)    NULL AFTER cta_description,
    ADD COLUMN cta_button_url      VARCHAR(300)    NULL AFTER cta_button_text,
    ADD COLUMN cta_show_whatsapp   TINYINT(1)      NOT NULL DEFAULT 0 AFTER cta_button_url,
    ADD COLUMN cta_whatsapp_label  VARCHAR(100)    NULL AFTER cta_show_whatsapp,
    ADD COLUMN cta_bg_image        VARCHAR(500)    NULL AFTER cta_whatsapp_label,

    -- SEO
    ADD COLUMN seo_title           VARCHAR(200)    NULL AFTER cta_bg_image,
    ADD COLUMN meta_description    VARCHAR(320)    NULL AFTER seo_title,
    ADD COLUMN focus_keyword       VARCHAR(200)    NULL AFTER meta_description,
    ADD COLUMN canonical_url       VARCHAR(500)    NULL AFTER focus_keyword,
    ADD COLUMN og_image            VARCHAR(500)    NULL AFTER canonical_url,

    -- Timestamps
    ADD COLUMN updated_at          DATETIME        NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- -------------------------------------------------------
-- [Portfolio Module] Table: portfolio_gallery
-- Image gallery per project with type classification.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS portfolio_gallery (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portfolio_id    INT UNSIGNED    NOT NULL,
    image_url       VARCHAR(500)    NOT NULL,
    caption         VARCHAR(300)    NULL,
    image_type      ENUM('before','after','screenshot','web_screenshot','mobile_screenshot','mockup','general') NOT NULL DEFAULT 'general',
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (portfolio_id) REFERENCES portfolio_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Portfolio Module] Table: portfolio_testimonials
-- Related testimonials per project.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS portfolio_testimonials (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portfolio_id          INT UNSIGNED    NOT NULL,
    testimonial_entry_id  INT UNSIGNED    NULL,
    sort_order            INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (portfolio_id) REFERENCES portfolio_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Portfolio Module] Table: portfolio_related_services
-- Related services per project.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS portfolio_related_services (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portfolio_id    INT UNSIGNED    NOT NULL,
    service_id      INT UNSIGNED    NOT NULL,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (portfolio_id) REFERENCES portfolio_items(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Portfolio Module] Table: portfolio_faqs
-- Project-specific FAQs.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS portfolio_faqs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portfolio_id    INT UNSIGNED    NOT NULL,
    question        VARCHAR(300)    NULL,
    answer          TEXT            NULL,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (portfolio_id) REFERENCES portfolio_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Portfolio Module] Table: portfolio_related_blocks
-- Related global blocks per project.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS portfolio_related_blocks (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portfolio_id    INT UNSIGNED    NOT NULL,
    global_block_id INT UNSIGNED    NOT NULL,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (portfolio_id) REFERENCES portfolio_items(id) ON DELETE CASCADE,
    FOREIGN KEY (global_block_id) REFERENCES global_blocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Portfolio Module] Table: portfolio_revisions
-- Tracks portfolio project changes for rollback.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS portfolio_revisions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portfolio_id    INT UNSIGNED    NOT NULL,
    data_snapshot   JSON            NOT NULL COMMENT 'Full project data snapshot',
    revision_note   VARCHAR(255)    NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_portfolio (portfolio_id),
    CONSTRAINT fk_pf_rev_portfolio
        FOREIGN KEY (portfolio_id) REFERENCES portfolio_items(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Portfolio Module] Table: portfolio_related_blog
-- Related blog posts per project.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS portfolio_related_blog (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portfolio_id    INT UNSIGNED    NOT NULL,
    blog_post_id    INT UNSIGNED    NOT NULL,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (portfolio_id) REFERENCES portfolio_items(id) ON DELETE CASCADE,
    FOREIGN KEY (blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- [Portfolio Module] Alter: portfolio_items status
-- Add 'archived' status for soft-deletion.
-- -------------------------------------------------------
ALTER TABLE portfolio_items
    MODIFY COLUMN status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft';

-- ============================================================
-- [About Module] Tables
-- ============================================================

-- -------------------------------------------------------
-- Table: about_sections
-- Stores about page section definitions and config.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS about_sections (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key     VARCHAR(30)     NOT NULL UNIQUE,
    section_label   VARCHAR(100)    NOT NULL,
    config          JSON            NOT NULL,
    is_enabled      TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,
    status          ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_section_key (section_key),
    INDEX idx_enabled_order (is_enabled, sort_order),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: about_revisions
-- Tracks about section changes for rollback.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS about_revisions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id      INT UNSIGNED    NOT NULL,
    config_snapshot JSON            NOT NULL,
    revision_note   VARCHAR(255)    NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_section (section_id),
    CONSTRAINT fk_about_rev_section
        FOREIGN KEY (section_id) REFERENCES about_sections(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: about_related_portfolio
-- Links the About page to published portfolio items.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS about_related_portfolio (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portfolio_item_id INT UNSIGNED    NOT NULL,
    sort_order        INT UNSIGNED    NOT NULL DEFAULT 0,

    UNIQUE KEY uq_about_portfolio (portfolio_item_id),
    CONSTRAINT fk_about_portfolio_item
        FOREIGN KEY (portfolio_item_id) REFERENCES portfolio_items(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: about_related_blocks
-- Links the About page to global blocks.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS about_related_blocks (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    global_block_id INT UNSIGNED    NOT NULL,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,

    UNIQUE KEY uq_about_block (global_block_id),
    CONSTRAINT fk_about_global_block
        FOREIGN KEY (global_block_id) REFERENCES global_blocks(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- Contact & Lead Management Module
-- ================================================================

CREATE TABLE IF NOT EXISTS contact_sections (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key     VARCHAR(30)     NOT NULL UNIQUE,
    section_label   VARCHAR(100)    NOT NULL,
    config          JSON            NOT NULL,
    is_enabled      TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,
    status          ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section_key (section_key),
    INDEX idx_enabled_order (is_enabled, sort_order),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(150)    NOT NULL,
    email           VARCHAR(254)    NOT NULL,
    phone           VARCHAR(30)     NULL,
    company         VARCHAR(150)    NULL,
    service_id      INT UNSIGNED    NULL,
    budget          VARCHAR(50)     NULL,
    timeline        VARCHAR(50)     NULL,
    subject         VARCHAR(255)    NULL,
    message         TEXT            NOT NULL,
    uploaded_files  JSON            NULL,
    source_page     VARCHAR(500)    NULL,
    referrer_url    VARCHAR(500)    NULL,
    ip_address      VARCHAR(45)     NULL,
    user_agent      VARCHAR(500)    NULL,
    device_type     VARCHAR(20)     NULL,
    status          ENUM('new','contacted','follow_up','proposal_sent','won','lost','archived') NOT NULL DEFAULT 'new',
    consent_given   TINYINT(1)      NOT NULL DEFAULT 0,
    email_sent      TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_created (created_at),
    INDEX idx_service (service_id),
    CONSTRAINT fk_lead_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lead_notes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id         INT UNSIGNED    NOT NULL,
    user_id         INT UNSIGNED    NULL,
    note            TEXT            NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lead (lead_id),
    CONSTRAINT fk_note_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_templates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key    VARCHAR(50)     NOT NULL UNIQUE,
    name            VARCHAR(150)    NOT NULL,
    subject         VARCHAR(255)    NOT NULL,
    body_html       TEXT            NOT NULL,
    body_text       TEXT            NOT NULL,
    variables_help  TEXT            NULL,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (template_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_log (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id         INT UNSIGNED    NULL,
    recipient       VARCHAR(254)    NOT NULL,
    subject         VARCHAR(255)    NOT NULL,
    template_key    VARCHAR(50)     NULL,
    status          ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    error_message   TEXT            NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lead (lead_id),
    INDEX idx_created (created_at),
    CONSTRAINT fk_emaillog_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default email templates
INSERT INTO email_templates (template_key, name, subject, body_html, body_text, variables_help) VALUES
('admin_notification', 'Admin Notification', 'New Lead: {{full_name}} - {{subject}}',
 '<h2>New Lead Received</h2>
 <table style="border-collapse:collapse;width:100%;max-width:600px">
 <tr><td style="padding:8px;border:1px solid #ddd;font-weight:bold;width:140px">Name</td><td style="padding:8px;border:1px solid #ddd">{{full_name}}</td></tr>
 <tr><td style="padding:8px;border:1px solid #ddd;font-weight:bold">Email</td><td style="padding:8px;border:1px solid #ddd">{{email}}</td></tr>
 <tr><td style="padding:8px;border:1px solid #ddd;font-weight:bold">Phone</td><td style="padding:8px;border:1px solid #ddd">{{phone}}</td></tr>
 <tr><td style="padding:8px;border:1px solid #ddd;font-weight:bold">Company</td><td style="padding:8px;border:1px solid #ddd">{{company}}</td></tr>
 <tr><td style="padding:8px;border:1px solid #ddd;font-weight:bold">Service</td><td style="padding:8px;border:1px solid #ddd">{{service}}</td></tr>
 <tr><td style="padding:8px;border:1px solid #ddd;font-weight:bold">Budget</td><td style="padding:8px;border:1px solid #ddd">{{budget}}</td></tr>
 <tr><td style="padding:8px;border:1px solid #ddd;font-weight:bold">Timeline</td><td style="padding:8px;border:1px solid #ddd">{{timeline}}</td></tr>
 <tr><td style="padding:8px;border:1px solid #ddd;font-weight:bold">Subject</td><td style="padding:8px;border:1px solid #ddd">{{subject}}</td></tr>
 <tr><td style="padding:8px;border:1px solid #ddd;font-weight:bold">Message</td><td style="padding:8px;border:1px solid #ddd">{{message}}</td></tr>
 <tr><td style="padding:8px;border:1px solid #ddd;font-weight:bold">Source</td><td style="padding:8px;border:1px solid #ddd">{{source_page}}</td></tr>
 <tr><td style="padding:8px;border:1px solid #ddd;font-weight:bold">IP Address</td><td style="padding:8px;border:1px solid #ddd">{{ip_address}}</td></tr>
 </table>
 <p style="margin-top:16px"><a href="{{admin_url}}" style="background:#2563eb;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">View in Admin Panel</a></p>',
 'New Lead: {{full_name}} - {{subject}}\n\nName: {{full_name}}\nEmail: {{email}}\nPhone: {{phone}}\nCompany: {{company}}\nService: {{service}}\nBudget: {{budget}}\nTimeline: {{timeline}}\nSubject: {{subject}}\n\nMessage:\n{{message}}\n\nSource: {{source_page}}\nIP: {{ip_address}}\n\nView: {{admin_url}}',
 '{{full_name}} {{email}} {{phone}} {{company}} {{service}} {{budget}} {{timeline}} {{subject}} {{message}} {{source_page}} {{ip_address}} {{admin_url}}'),

('user_confirmation', 'User Confirmation', 'Thank you for your message, {{full_name}}',
 '<h2>Thank You, {{full_name}}!</h2>
 <p>We have received your message and will get back to you within 24 hours.</p>
 <p><strong>Your Message:</strong></p>
 <blockquote style="border-left:3px solid #2563eb;margin:12px 0;padding:8px 16px;background:#f8fafc">{{message}}</blockquote>
 <p>If you have any urgent questions, feel free to reach out:</p>
 <ul>
   <li>Phone: {{contact_phone}}</li>
   <li>Email: {{contact_email}}</li>
   <li>WhatsApp: {{whatsapp_link}}</li>
 </ul>
 <p>Best regards,<br>{{site_name}} Team</p>',
 'Thank you for your message, {{full_name}}!\n\nWe have received your message and will get back to you within 24 hours.\n\nYour Message:\n{{message}}\n\nContact:\nPhone: {{contact_phone}}\nEmail: {{contact_email}}\nWhatsApp: {{whatsapp_link}}\n\nBest regards,\n{{site_name}} Team',
 '{{full_name}} {{message}} {{contact_phone}} {{contact_email}} {{whatsapp_link}} {{site_name}}');

-- ================================================================
-- Blog & Knowledge Base Module
-- ================================================================

CREATE TABLE IF NOT EXISTS blog_categories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)    NOT NULL,
    slug            VARCHAR(150)    NOT NULL UNIQUE,
    description     TEXT            NULL,
    parent_id       INT UNSIGNED    NULL,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id),
    CONSTRAINT fk_blogcat_parent FOREIGN KEY (parent_id) REFERENCES blog_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_tags (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)    NOT NULL,
    slug            VARCHAR(100)    NOT NULL UNIQUE,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_posts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255)    NOT NULL,
    slug            VARCHAR(255)    NOT NULL UNIQUE,
    short_description TEXT         NULL,
    content         LONGTEXT        NOT NULL,
    featured_image  VARCHAR(500)    NULL,
    gallery         JSON            NULL,
    author_id       INT UNSIGNED    NULL,
    published_at    DATETIME        NULL,
    status          ENUM('draft','published','scheduled','archived') NOT NULL DEFAULT 'draft',
    reading_time    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    is_featured     TINYINT(1)      NOT NULL DEFAULT 0,
    is_sticky       TINYINT(1)      NOT NULL DEFAULT 0,
    seo_title       VARCHAR(255)    NULL,
    meta_description VARCHAR(320)   NULL,
    focus_keyword   VARCHAR(255)    NULL,
    canonical_url   VARCHAR(500)    NULL,
    og_image        VARCHAR(500)    NULL,
    twitter_card    VARCHAR(30)     NULL DEFAULT 'summary_large_image',
    view_count      INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_published (published_at),
    INDEX idx_author (author_id),
    INDEX idx_featured (is_featured),
    INDEX idx_sticky (is_sticky),
    CONSTRAINT fk_blogpost_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_post_categories (
    post_id         INT UNSIGNED    NOT NULL,
    category_id     INT UNSIGNED    NOT NULL,
    PRIMARY KEY (post_id, category_id),
    CONSTRAINT fk_bpc_post FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_bpc_cat FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_post_tags (
    post_id         INT UNSIGNED    NOT NULL,
    tag_id          INT UNSIGNED    NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    CONSTRAINT fk_bpt_post FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_bpt_tag FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_revisions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id         INT UNSIGNED    NOT NULL,
    title           VARCHAR(255)    NOT NULL,
    content         LONGTEXT        NOT NULL,
    short_description TEXT         NULL,
    revision_note   VARCHAR(255)    NULL,
    saved_by        INT UNSIGNED    NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post (post_id),
    CONSTRAINT fk_brev_post FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_brev_user FOREIGN KEY (saved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_relationships (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id         INT UNSIGNED    NOT NULL,
    related_type    VARCHAR(30)     NOT NULL,
    related_id      INT UNSIGNED    NOT NULL,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,
    INDEX idx_post (post_id),
    INDEX idx_related (related_type, related_id),
    CONSTRAINT fk_brel_post FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Advanced Search Module
-- ================================================================

-- -------------------------------------------------------
-- Table: search_index
-- Normalized search index over all content types.
-- content_type in ('page','service','portfolio','blog_post',
--                  'content_entry','global_block','faq')
-- Fulltext index drives relevance-ranked search. A LIKE fallback
-- provides partial-keyword matching for short terms.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS search_index (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_type  VARCHAR(30)     NOT NULL,
    content_id    INT UNSIGNED    NOT NULL,
    title         VARCHAR(255)    NOT NULL,
    excerpt       TEXT            NULL,
    content       MEDIUMTEXT      NULL,
    keywords      VARCHAR(500)    NULL COMMENT 'focus keywords / meta keywords',
    url           VARCHAR(500)    NOT NULL,
    image         VARCHAR(500)    NULL,
    category      VARCHAR(150)    NULL,
    tags          VARCHAR(500)    NULL COMMENT 'comma-separated tag names',
    is_featured   TINYINT(1)      NOT NULL DEFAULT 0,
    published_at  DATETIME        NULL,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_search_content (content_type, content_id),
    INDEX idx_search_type (content_type),
    INDEX idx_search_category (category),
    INDEX idx_search_published (published_at),
    INDEX idx_search_featured (is_featured),
    FULLTEXT KEY ft_search (title, excerpt, content, keywords, tags, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: search_logs
-- One row per executed search query (admin analytics + history).
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS search_logs (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    query            VARCHAR(255)  NOT NULL,
    normalized_query VARCHAR(255)  NOT NULL COMMENT 'lowercased, trimmed, collapsed',
    result_count     INT UNSIGNED  NOT NULL DEFAULT 0,
    is_zero_result   TINYINT(1)    NOT NULL DEFAULT 0,
    ip_address       VARCHAR(45)   NOT NULL DEFAULT '',
    user_agent       VARCHAR(500)  NOT NULL DEFAULT '',
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_search_query (normalized_query),
    INDEX idx_search_created (created_at),
    INDEX idx_search_zero (is_zero_result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Table: search_result_clicks
-- Tracks which search results are clicked (most-viewed results).
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS search_result_clicks (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    search_log_id INT UNSIGNED    NULL,
    query         VARCHAR(255)  NOT NULL DEFAULT '',
    content_type  VARCHAR(30)     NOT NULL,
    content_id    INT UNSIGNED    NOT NULL,
    content_title VARCHAR(255)    NOT NULL DEFAULT '',
    url           VARCHAR(500)    NOT NULL DEFAULT '',
    position      INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_click_content (content_type, content_id),
    INDEX idx_click_created (created_at),
    INDEX idx_click_log (search_log_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Seed: default Advanced Search settings
-- -------------------------------------------------------
INSERT INTO site_settings (setting_key, setting_value) VALUES
('search_logging_enabled', '1'),
('search_min_query_length', '2'),
('search_results_per_page', '10'),
('search_suggestions_enabled', '1'),
('search_page_seo_title', 'Search Results'),
('search_page_meta_description', 'Search our services, portfolio, blog posts, and more.'),
('search_page_noindex', '1'),
('search_fuzzy_threshold', '2'),
('search_recent_limit', '10'),
('search_popular_limit', '10'),
('search_rate_limit_attempts', '30'),
('search_rate_limit_window', '1')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
