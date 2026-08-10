# Swap Design CMS -- Architecture Document

## System Overview

Swap Design CMS is a custom-built content management system for a freelance design studio. It targets Hostinger Premium Shared Hosting (Apache 2.4, PHP 8+, MySQL 5.7+/MariaDB 10.3+) and uses zero external dependencies.

---

## 1. Request Lifecycle

```
HTTP Request
  |
  v
.htaccess rewrites -> index.php?url={path} (frontend)
.htaccess passes -> /admin/*.php (admin -- direct file access)
  |
  v
Frontend: index.php
  1. define('SWAP_ROOT', true)
  2. require config/site.php (returns $site object)
  3. require config/database.php (PDO singleton via getDBConnection())
  4. require functions/helpers.php, sanitize.php, seo.php
  5. require config/environment.php (APP_ENV, APP_DEBUG)
  6. require config/error-handler.php (custom error/exception handlers)
  7. require functions/security.php (secure headers)
  8. require loader.php -> resolvePage() -> $context array
  9. http_response_code($context['statusCode'])
  10. require layout.php -> renderLayout($context) -> HTML output

Admin: admin/*.php
  1. require admin/includes/init.php
     - define('SWAP_ROOT', true)
     - require config files
     - Session::start()
     - Database::getInstance()
     - Auth::require() -> redirect to login if not authenticated
     - setSecureHeaders()
  2. Page-specific logic
  3. require admin/includes/header.php (renders sidebar + topbar)
  4. Page content
  5. require admin/includes/footer.php (closes layout + scripts)
```

---

## 2. Core Services (OOP Layer)

### Session (`includes/Session.php`)

Wraps PHP's native session functions with secure defaults.

```
Session::start($options)      -- session_start with secure params
Session::get($key, $default)  -- read from $_SESSION
Session::set($key, $value)    -- write to $_SESSION
Session::has($key)            -- isset check
Session::remove($key)         -- unset
Session::regenerate()         -- session_regenerate_id(true)
Session::destroy()            -- session_destroy + clear cookie
Session::flash($key, $value)  -- one-request persistent message
```

Session configuration:
- `cookie_httponly = true`
- `cookie_samesite = 'Lax'`
- `cookie_secure = true` (production only, via APP_ENV check)
- `use_strict_mode = true`
- Session save path: system default (Hostinger handles this)

### Database (`includes/Database.php`)

PDO singleton wrapper with prepared-statement query builder.

```
Database::getInstance()             -- get singleton
Database::getConnection()           -- get PDO instance
Database::query($sql, $params)      -- run statement, return statement
Database::fetch($sql, $params)      -- single row as assoc array
Database::fetchAll($sql, $params)   -- all rows as assoc array
Database::fetchColumn($sql, $params)-- single scalar value
Database::insert($table, $data)     -- returns lastInsertId
Database::update($table, $data, $where, $params) -- returns affected rows
Database::delete($table, $where, $params)         -- returns affected rows
Database::beginTransaction()        -- PDO::beginTransaction
Database::commit()                  -- PDO::commit
Database::rollback()                -- PDO::rollback
```

All methods use prepared statements internally. Connection charset is utf8mb4. Error mode is ERRMODE_EXCEPTION.

### Authentication (`includes/auth/Auth.php`)

Static class for user authentication.

```
Auth::login($email, $password, $remember)  -- verify credentials, set session
Auth::logout()                              -- destroy session, clear remember token
Auth::check()                               -- is user logged in?
Auth::user()                                -- get current user array (id, email, name, role)
Auth::require($redirectUrl)                 -- redirect if not logged in
Auth::attempts($email)                      -- get remaining attempts before lockout
```

Login flow:
1. Check rate limit (`login_attempts` table, 5 failures per email/IP within 15 minutes)
2. Look up user by email (`SELECT * FROM users WHERE email = ? AND status = 'active'`)
3. Verify password with `password_verify($password, $user['password_hash'])`
4. Regenerate session ID on successful login
5. Set `$_SESSION['user_id']`, `$_SESSION['user_email']`, `$_SESSION['user_name']`, `$_SESSION['user_role']`
6. If `$remember` is true: generate split-token (32-byte selector + 32-byte token), store `token_hash` in `remember_tokens`, set cookie with `selector:token` (30-day expiry)
7. Log successful attempt in `login_attempts`

Remember-me flow:
1. If no active session, check for `remember_token` cookie
2. Parse `selector:token` from cookie
3. Look up `selector` in `remember_tokens` table
4. Verify `hash_equals(token_hash, hash('sha256', $token))`
5. If valid: log user in, rotate token (new token, update hash in DB, update cookie)
6. If invalid: delete all tokens for that user (potential theft detection)

Rate limiting:
- Max 5 failed attempts per (email, IP) within 15 minutes
- After lockout: return false with "Too many attempts" message
- Successful login resets counter for that email

## 3. Security Architecture

### CSRF Protection
- `csrfToken()` generates 32-byte random token, stores in `$_SESSION['csrf_token']`
- `verifyCsrfToken($token)` uses `hash_equals()` for timing-safe comparison
- All admin forms include hidden CSRF input
- Frontend contact form includes CSRF input

### Input Sanitization
- `sanitizeString()` -- HTML entity encoding
- `sanitizeEmail()` -- FILTER_SANITIZE_EMAIL + FILTER_VALIDATE_EMAIL
- `sanitizeInt()` -- FILTER_SANITIZE_NUMBER_INT + (int) cast
- `sanitizeUrl()` -- FILTER_SANITIZE_URL + FILTER_VALIDATE_URL
- `validateRequired()` -- checks non-empty fields
- `validateLength()` -- mb_strlen range check

### Output Escaping
- `esc()` -- htmlspecialchars with ENT_QUOTES | ENT_HTML5, UTF-8
- Used in ALL template output of user-supplied or DB-sourced data

### Secure Headers
- X-Content-Type-Options: nosniff
- X-Frame-Options: SAMEORIGIN
- X-XSS-Protection: 1; mode=block (legacy)
- Referrer-Policy: strict-origin-when-cross-origin
- Admin area: no caching headers

### Password Policy
- bcrypt via `password_hash(PASSWORD_BCRYPT, ['cost' => 12])`
- Min 8 characters (enforced at registration)
- `password_verify()` for comparison

### Rate Limiting
- File-based (no Redis): `login_attempts` table tracks failures
- Keyed by email + IP
- 5 attempts / 15 minutes per key

## 4. Admin Panel Architecture

### Layout
```
+----------------------------------------------------------+
| Topbar (56px): [hamburger] [logo] [breadcrumb] [user ▼]  |
+------+---------------------------------------------------+
|      |                                                    |
| Side |  Main Content Area                                 |
| bar  |                                                    |
| 240px|                                                    |
|      |                                                    |
| (can |                                                    |
|  col-|                                                    |
| lapse|                                                    |
|  to  |                                                    |
|  64px|                                                    |
| )    |                                                    |
+------+---------------------------------------------------+
```

### Sidebar Menu (26 items + 3 separators + View Site)

1. Dashboard
2. Homepage
3. About Page
4. Contact Page
5. Leads
6. Email Settings
7. Email Templates
8. Blog
9. Search Dashboard
10. *(separator — Content)*
11. Pages
12. Services
13. Portfolio
14. Content Types
15. Entries
16. Sections
17. Layouts
18. *(separator — Media & Design)*
19. Media Library
20. Global Blocks
21. Navigation
22. Footer
23. *(separator — Settings)*
24. Theme Settings
25. Site Settings
26. WhatsApp
27. Users
28. View Site (external link)

Collapsible on click. On mobile (<768px): hidden by default, toggled via hamburger. Active item gets `aria-current="page"` + `admin-sidebar__link--active` class.

### Dashboard Widgets
1. Total Pages (stat card)
2. Published Pages (stat card)
3. Portfolio Items (stat card)
4. Contact Messages (stat card -- with unread count badge)
5. Recent Messages (data table, last 5)
6. Quick Actions (Create Page, Upload Media, View Site)

---

## 5. Frontend Rendering Pipeline

```
resolvePage() from loader.php
  -> parses $_GET['url']
  -> matches against route table (static + nav-derived)
  -> returns $context array

renderLayout($context) from layout.php
  -> sets SEO globals
  -> header.php    -> <!DOCTYPE><html><head>[head.php]
  -> navigation.php
  -> <main id="main-content">
  -> breadcrumb.php (if crumbs > 1)
  -> page-header (if inner page)
  -> pages/{template}.php (page content)
  -> </main>
  -> cta.php (conditional)
  -> footer.php
  -> scripts.php
  -> </body></html>
```

---

## 6. Database Schema

### Auth Tables (Sprint 1)

**users**
- id (INT PK AUTO_INCREMENT)
- email (VARCHAR(254) UNIQUE NOT NULL)
- password_hash (VARCHAR(255) NOT NULL)
- name (VARCHAR(100) NOT NULL)
- role (ENUM: 'admin', 'editor') DEFAULT 'admin'
- status (ENUM: 'active', 'inactive') DEFAULT 'active'
- last_login_at (DATETIME NULL)
- created_at (DATETIME DEFAULT CURRENT_TIMESTAMP)
- updated_at (DATETIME ON UPDATE CURRENT_TIMESTAMP)

**login_attempts**
- id (INT PK AUTO_INCREMENT)
- email (VARCHAR(254) NOT NULL)
- ip_address (VARCHAR(45) NOT NULL)
- attempted_at (DATETIME DEFAULT CURRENT_TIMESTAMP)
- success (TINYINT(1) DEFAULT 0)

**remember_tokens**
- id (INT PK AUTO_INCREMENT)
- user_id (INT FK -> users.id ON DELETE CASCADE)
- selector (VARCHAR(64) NOT NULL UNIQUE)
- token_hash (VARCHAR(128) NOT NULL)
- expires_at (DATETIME NOT NULL)
- created_at (DATETIME DEFAULT CURRENT_TIMESTAMP)

### Content Tables (pre-existing)

**contact_messages** -- Contact form submissions
**pages** -- Static/dynamic page content
**portfolio_items** -- Portfolio entries
**site_settings** -- Key-value config store

---

## 7. Error Handling & Logging

### Error Handler (`error-handler.php`)
- Converts PHP errors to ErrorException
- Custom exception handler logs to file + displays friendly page
- Fatal error handler (register_shutdown_function) catches parse errors
- In APP_DEBUG=false: hide details, show generic message
- In APP_DEBUG=true: show error details

### Logger (`logger.php`)
- File-based: writes to `/logs/app-YYYY-MM-DD.log`
- Log levels: DEBUG, INFO, WARNING, ERROR
- Daily rotation (filename includes date)
- Format: `[YYYY-MM-DD HH:MM:SS] LEVEL: message {context}`
- Auto-creates logs directory if missing

---

## 8. Environment Configuration

Constants defined in `environment.php`:

| Constant | Default | Purpose |
|----------|---------|---------|
| APP_ENV | 'production' | Environment (development/production) |
| APP_DEBUG | false | Enable detailed error output |
| APP_URL | auto-detected | Base URL for canonical links |
| LOG_PATH | '/logs/' | Log file directory |

Database constants (see `database.php`): DB_HOST, DB_NAME, DB_USER, DB_PASS

---

## 9. Performance Considerations

- Single PDO connection per request (singleton pattern)
- Custom query builder avoids N+1 queries
- Prepared statements with native types for MySQL optimization
- Session auto-starts only on pages that need it
- CSS delivered as individual files for HTTP/2 multiplexing (no bundling)
- JS loaded with `defer` attribute (non-blocking)
- Google Fonts loaded with `media="print" onload="this.media='all'"`
- No jQuery or heavy libraries

---

## 10. Deployment (Hostinger)

- Upload all files to `public_html/`
- Set DB credentials via `.env` or direct inline (NOT committed to git)
- Set `APP_ENV=production` and `APP_DEBUG=false`
- Ensure `logs/` and `uploads/` directories are writable (755)
- Verify `.htaccess` mod_rewrite is enabled
- Create database tables via phpMyAdmin import or CLI
- Create initial admin user via SQL INSERT or setup script
