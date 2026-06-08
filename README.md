# APCu & OPcache Viewer

A modern, full-featured web interface for monitoring and managing **APCu** (user cache) and **OPcache** (opcode cache) in PHP 8.3+. Built with a hand-rolled MVC framework — no heavy dependencies, lightweight and fast.

![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)
![APCu](https://img.shields.io/badge/APCu-required-blue)
![Bootstrap](https://img.shields.io/badge/UI-Bootstrap_5.3-7952B3?logo=bootstrap)

---

## Features

### APCu Cache Management
- **Dashboard** — memory usage, hit/miss rates, entry count, uptime at a glance
- **Entries table** — paginated, searchable, sortable by key/size/hits/created/TTL
- **View values** — JSON syntax-highlighted, serialized, numeric/bool/null detection
- **Inline TTL edit** — change any entry's TTL without recreating it
- **Delete / bulk delete** — single or multi-select with CSRF + rate limiting
- **Clear cache** — respects pinned keys (skips them)
- **Key pinning** — pin important entries so they survive clears and deletes

### Monitoring & Trends
- **Memory trend chart** — 60-point rolling window, updates on every page load
- **Hit rate trend chart** — same 60-point rolling window
- **Key breakdown stats** — active vs expired counts, TTL bucket distribution
- **Auto-refresh** — dropdown (Off / 15s / 30s / 60s), persists in localStorage

### OPcache Viewer
- **Status** — memory usage, hits/misses, key statistics, JIT info
- **Scripts** — full list of cached scripts with memory/size per file
- **Reset** — one-click `opcache_reset()` with CSRF + rate limiting (6/hr)

### Advanced Features
- **Cache warmup** — paste JSON key-value pairs and store them in APCu at once
- **Export CSV** — download all entries as a comma-separated file
- **Audit log** — 100-entry circular buffer tracking all cache operations
- **Server info** — PHP version, loaded extensions, APCu & OPcache ini values
- **Rate limiting** — APCu-backed rate limiter on destructive actions
- **CSRF protection** — every POST/DELETE route validates a session-bound token
- **Dark mode** — Bootstrap 5 dark theme toggle

---

## Requirements

- PHP **8.3 or higher**
- [`ext-apcu`](https://www.php.net/manual/en/book.apcu.php) enabled
- Web server with URL rewriting (Apache `mod_rewrite` or Nginx)
- [Composer](https://getcomposer.org/)

### Optional
- OPcache extension (for OPcache viewer features)
- `ext-intl` (for locale-aware formatting, not required)

---

## Installation

### 1. Clone

```bash
git clone https://github.com/gxw/apcu-cache-viewer.git
cd php-apcu-opcache-viewer
```

### 2. Install PHP dependencies

```bash
composer install --no-dev --optimize-autoloader
```

> The `--optimize-autoloader` flag generates a classmap for faster autoloading in production.

### 3. Web server setup

#### Apache

The repository includes two `.htaccess` files:

- **Root `.htaccess`** — rewrites all requests to `public/index.php`, sets security headers
- **`public/.htaccess`** — serves static assets directly, routes everything else to `index.php`

Make sure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

If deploying under a subdirectory (e.g. `/apcu`), the root `.htaccess` uses `RewriteBase /apcu/`. The entry point detects the base path automatically — `php -S` uses empty prefix, Apache behind `/apcu` uses `/apcu`.

#### Nginx

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/php-apcu-opcache-viewer/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }
}
```

### 4. Development server (no Apache needed)

```bash
php -S localhost:8080 -t public
```

Then open `http://localhost:8080` in your browser.

---

## Usage

| Route | Description |
|-------|-------------|
| `/` | Dashboard — stats, charts, entries table |
| `/entries` | Turbo Frame — entries table partial (used by auto-refresh) |
| `/export` | Download all entries as CSV |
| `/opcache` | OPcache status overview |
| `/opcache/scripts` | OPcache cached scripts |
| `/audit` | Audit log of cache operations |
| `/warmup` | Cache warmup form |
| `/info` | Server & PHP configuration |

### Cache management buttons

| Button | Action |
|--------|--------|
| 🗑️ (trash) | Delete a single entry |
| 📌 (thumbtack) | Toggle pin (protects from clear/delete) |
| ✏️ (pencil) | Edit TTL inline via modal |
| 🗂️ (bulk checkboxes) | Select multiple → Delete selected |
| 🧹 Clear Cache | Clears all unpinned entries |
| 🔄 Auto-refresh | Dropdown to set polling interval |

---

## Security

- **CSRF tokens** — generated from `random_bytes(32)`, stored in session, validated on all POST/DELETE
- **Rate limiting** — APCu-backed: clear cache (6/hr), delete key (30/min), delete multiple (10/min), OPcache reset (6/hr)
- **IP whitelist** — optional, commented out in `public/index.php`, configured via `.htaccess` `Require ip`
- **HTTP Basic Auth** — configurable via `.htaccess` + `.htpasswd`
- **Output escaping** — all templates use `htmlspecialchars()` via `$view->e()` helper
- **Security headers** — `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, HSTS

---

## Running Tests

```bash
vendor/bin/phpunit
```

Tests cover the custom MVC components: Router, Request, CacheEntry value object.

---

## Project Structure

```
apcu/
├── config/app.php           # Application configuration
├── public/
│   ├── index.php            # Front controller (entry point)
│   ├── .htaccess            # Apache rewrite rules
│   └── assets/css/          # Custom CSS
├── routes/web.php           # Route definitions
├── src/
│   ├── Cache/               # CacheManager, CacheEntry
│   ├── Controller/          # DashboardController, OpcacheController
│   ├── Http/                # Request, Response, Session
│   ├── Routing/             # Router (regex-based)
│   ├── Security/            # CsrfProtection, RateLimiter
│   ├── Services/            # CacheService, OpcacheService, AuditService
│   └── View/                # View engine, FormHelper
├── templates/               # PHP templates (layout, dashboard, partials)
│   ├── layout.php           # Bootstrap 5.3 + CDN assets
│   ├── dashboard.php        # Main dashboard
│   ├── _entries.php         # Entries table partial
│   ├── audit.php            # Audit log
│   ├── warmup.php           # Cache warmup form
│   ├── info.php             # Server info
│   ├── errors/              # Error pages (404, 500)
│   └── opcache/             # OPcache templates
├── tests/                   # PHPUnit tests
├── composer.json            # Dependencies
└── .htaccess                # Root Apache rewrite
```

---

## License

MIT
