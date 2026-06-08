# APCu Cache Viewer — Full Refresh Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix all bugs, harden security, overhaul UI, add tests, and modernize the APCu Cache Viewer.

**Architecture:** Incremental phases — bugs first, then architecture/security, then code quality, UI, and testing. Each phase is independently deployable. Phases 2/3/4 can parallel after Phase 0/1.

**Tech Stack:** PHP 8.3, APCu, Bootstrap 5.3, Chart.js, Hotwire Turbo, PHPUnit 11

---

## File Structure

### Files to modify
| File | Change |
|------|--------|
| `public/app.php` | Remove dead code, include routes, wire CSRF, cleanup |
| `src/Http/Request.php` | Fix `$_SERVER` → `$this->server`/`$this->method`/`$this->get` |
| `src/Controller/DashboardController.php` | Merge CacheController methods, add updateEntry, add try/catch |
| `src/Cache/CacheManager.php` | Remove debug logs, add `updateEntry()`, add `deleteMultiple()` |
| `src/Cache/CacheEntry.php` | Add `getterExists()` for safety |
| `src/Services/CacheService.php` | Add `updateCacheEntry()`, `deleteMultipleEntries()` |
| `composer.json` | Bump deps, add phpunit |
| `templates/layout.php` | Dark mode, consolidate toast, add theme toggle JS |
| `templates/dashboard.php` | Remove duplicate toast, add auto-refresh toggle, export button |
| `templates/_entries.php` | Add checkbox column, bulk delete UI |
| `public/assets/css/app.css` | Dark mode CSS variables |
| `.env.example` | Remove unused keys |

### Files to create
| File | Purpose |
|------|---------|
| `routes/web.php` | Route definitions (included from `app.php`) |
| `src/Security/RateLimiter.php` | Simple IP-based rate limiter using APCu |
| `src/Security/CsrfProtection.php` | CSRF utility (refactored from middleware) |
| `src/View/Helper/ThemeHelper.php` | Dark mode toggle helper |
| `phpunit.xml` | PHPUnit config |
| `tests/Unit/CacheEntryTest.php` | Unit tests for CacheEntry |
| `tests/Unit/RouterTest.php` | Unit tests for Router |
| `tests/Unit/RequestTest.php` | Unit tests for Request |

### Files to delete
| File | Reason |
|------|--------|
| `bootstrap/app.php` | Dead alternative entry point |
| `src/Http/Middleware/MiddlewareInterface.php` | Dead code (unused without middleware pipeline) |
| `src/Http/Middleware/MethodSpoofingMiddleware.php` | Dead code |
| `src/Http/Middleware/CsrfMiddleware.php` | Replaced by `src/Security/CsrfProtection.php` |
| `src/Controller/CacheController.php` | Methods merged into DashboardController |
| `templates/errors/error.php` | Dead code, uses `$this->layout()` which doesn't exist |
| `templates/components/flash_messages.php` | Duplicate of `_flash_messages.php` |
| `apcu/test.php` | Replaced by PHPUnit |
| `apcu/test_router.php` | Replaced by PHPUnit |
| `apcu/package-lock.json` | Not a Node project |
| `public/error.php` | Replaced by template errors |

---

## Phase 0 — Bug Fixes

### Task 0.1: Fix Request class using globals instead of properties

**Files:** `src/Http/Request.php`

- [ ] **Step 1: Replace all `$_SERVER`/`$_GET`/`$_POST` references with instance properties**

```php
// In src/Http/Request.php, change getMethod():
public function getMethod(): string
{
    return $this->method ?? 'GET';
}

// Change server():
public function server(string $key, $default = null)
{
    return $this->server[$key] ?? $default;
}

// Change isAjax():
public function isAjax(): bool
{
    return !empty($this->server['HTTP_X_REQUESTED_WITH']) && 
           strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Change getQueryParams():
public function getQueryParams(): array
{
    return $this->get;
}

// Change getPostData():
public function getPostData(): array
{
    return $this->post;
}

// Change getHeader():
public function getHeader(string $name, $default = null): ?string
{
    $name = 'HTTP_' . strtoupper(str_replace(['-', '.'], '_', $name));
    return $this->server[$name] ?? $default;
}
```

- [ ] **Step 2: Verify the constructor uses `$this->` for the conditional**

```php
// In constructor, change line 27-28:
if ($this->method === 'POST' && 
    (isset($this->server['CONTENT_TYPE']) && str_contains($this->server['CONTENT_TYPE'], 'application/json'))) {
```

- [ ] **Step 3: Add missing property declarations**

```php
// Add these properties at the top of the class:
private array $get = [];
private array $post = [];
private array $cookies = [];
private array $files = [];
private array $server = [];
private string $requestUri = '/';
private string $method = 'GET';
```

### Task 0.2: Remove duplicate timezone set

**Files:** `public/app.php`

- [ ] **Step 1: Remove the first `date_default_timezone_set` call**

Remove line 20: `date_default_timezone_set('Europe/Warsaw');` — keep the second one at line 37.

### Task 0.3: Fix CacheController::index() param order

**Files:** `src/Controller/CacheController.php`

- [ ] **Step 1: Fix the argument order**

Change line 30 from:
```php
$cacheData = $this->cacheService->getPaginatedCacheEntries($page, $perPage, $search, $sort);
```
to:
```php
$cacheData = $this->cacheService->getPaginatedCacheEntries($page, $search, $sort, 'asc', $perPage);
```

### Task 0.4: Remove debug error_log() calls

**Files:** `src/Cache/CacheManager.php`, `src/Controller/DashboardController.php`, `public/app.php`

- [ ] **Step 1: Remove all debug `error_log()` from CacheManager.php**

Remove lines 14, 15, 36, 56 (the `error_log()` calls), leave the rest of the logic intact.

- [ ] **Step 2: Remove debug log from DashboardController.php**

Remove line 82: `error_log("DEBUG DashboardController: deleteKey received key: " . $key);`

- [ ] **Step 3: Remove commented-out debug logs from public/app.php**

Remove lines 78-81 (commented error_log block) and lines 95-97 (same).

### Task 0.5: Remove dead code — standalone files

**Files to delete:** `bootstrap/app.php`, `src/Http/Middleware/MiddlewareInterface.php`, `src/Http/Middleware/MethodSpoofingMiddleware.php`, `templates/errors/error.php`, `templates/components/flash_messages.php`, `apcu/test.php`, `apcu/test_router.php`, `apcu/package-lock.json`, `apcu/public/error.php`

- [ ] **Step 1: Delete each dead file**

```bash
Remove-Item -LiteralPath "apcu/bootstrap/app.php" -Force
Remove-Item -LiteralPath "apcu/src/Http/Middleware/MiddlewareInterface.php" -Force
Remove-Item -LiteralPath "apcu/src/Http/Middleware/MethodSpoofingMiddleware.php" -Force
Remove-Item -LiteralPath "apcu/templates/errors/error.php" -Force
Remove-Item -LiteralPath "apcu/templates/components/flash_messages.php" -Force
Remove-Item -LiteralPath "apcu/test.php" -Force
Remove-Item -LiteralPath "apcu/test_router.php" -Force
Remove-Item -LiteralPath "apcu/package-lock.json" -Force
Remove-Item -LiteralPath "apcu/public/error.php" -Force
```

### Task 0.6: Merge CacheController into DashboardController

**Files:** `src/Controller/CacheController.php`, `src/Controller/DashboardController.php`

- [ ] **Step 1: Move `getKeyValue()` from CacheController to DashboardController**

Add to `DashboardController.php`:
```php
public function getKeyValue(string $key): Response
{
    try {
        $entry = $this->cacheService->getCacheEntry($key);
        if ($entry === null) {
            return (new Response())->json([
                'success' => false,
                'message' => 'Key not found'
            ], 404);
        }
        return (new Response())->json([
            'success' => true,
            'value' => $entry
        ], 200);
    } catch (\Throwable $e) {
        error_log('Error getting cache key: ' . $e->getMessage());
        return (new Response())->json([
            'success' => false,
            'message' => 'Failed to get key value: ' . $e->getMessage()
        ], 500);
    }
}
```

- [ ] **Step 2: Merge `deleteKey()` and `clearCache()` into DashboardController**

`DashboardController` already has `deleteKey()` and `clearCache()` methods. The `CacheController` versions handle errors with try/catch while DashboardController versions don't. Wrap the DashboardController versions in try/catch for consistency.

```php
public function clearCache(Request $request): Response
{
    try {
        $result = $this->cacheService->clearCache();
        if ($request->isAjax()) {
            return (new Response())->json([
                'success' => $result,
                'message' => $result ? 'Cache cleared successfully' : 'Failed to clear cache'
            ]);
        }
        return new Response('', 302, ['Location' => '/']);
    } catch (\Throwable $e) {
        error_log('Error clearing cache: ' . $e->getMessage());
        if ($request->isAjax()) {
            return (new Response())->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
        return new Response('Error clearing cache', 500);
    }
}

public function deleteKey(Request $request): Response
{
    try {
        $key = base64_decode($request->post('key'));
        if ($key === false || $key === '') {
            return (new Response())->json([
                'success' => false,
                'message' => 'Invalid key'
            ], 400);
        }
        $result = $this->cacheService->deleteCacheEntry($key);
        $message = $result ? 'Key deleted successfully' : 'Key not found';
        return (new Response())->json([
            'success' => $result,
            'message' => $message
        ], $result ? 200 : 404);
    } catch (\Throwable $e) {
        error_log('Error deleting key: ' . $e->getMessage());
        return (new Response())->json([
            'success' => false,
            'message' => 'Failed to delete key: ' . $e->getMessage()
        ], 500);
    }
}
```

- [ ] **Step 3: Update routes in public/app.php to use DashboardController**

The DELETE route currently points to `$cacheController->deleteKey($request, $key)`. Change to `$dashboardController->deleteKey($request)` — the route parameter `{key}` will not be used (DashboardController reads key from POST body).

Actually simpler: keep using `$cacheController` until CacheController is deleted, but CacheController's `deleteKey` takes `(Request $request, string $key)` from route param while DashboardController's takes `(Request $request)` and reads from POST. 

The current active route `delete('/delete-key/{key}')` calls `$cacheController->deleteKey($request, $key)` which gets key from route param. The DashboardController::deleteKey reads from `$request->post('key')`. These are incompatible.

Since the JS code sends the key as JSON body `{ key: keyToDelete }`, the controller needs to read from request body. Keep this for now — will be resolved in Phase 1 when routes are moved.

- [ ] **Step 4: Delete CacheController.php**

```bash
Remove-Item -LiteralPath "apcu/src/Controller/CacheController.php" -Force
```

---

## Phase 1 — Architecture & Security

### Task 1.1: Create routes/web.php

**Files:** Create `routes/web.php`, modify `public/app.php`

- [ ] **Step 1: Create routes/web.php**

```php
<?php

declare(strict_types=1);

use App\Http\Request;
use App\Http\Response;

/** @var Router $router */
/** @var DashboardController $dashboardController */
/** @var OpcacheController $opcacheController */

$router->get('/', fn(Request $request) => $dashboardController->index($request));
$router->get('/opcache', fn(Request $request) => $opcacheController->index($request));
$router->get('/opcache/scripts', fn(Request $request) => $opcacheController->scripts($request));
$router->get('/entries', fn(Request $request) => $dashboardController->entries($request));
$router->post('/clear-cache', fn(Request $request) => $dashboardController->clearCache($request));
$router->delete('/delete-key/{key}', fn(Request $request) => $dashboardController->deleteKey($request));
$router->get('/get-cache/{key}', fn(Request $request, string $key) => $dashboardController->getKeyValue($key));
```

- [ ] **Step 2: Update public/app.php to include routes** instead of defining them inline

Replace the route definition block (lines 99-127) with:
```php
require __DIR__ . '/../routes/web.php';
```

Remove `use App\Controller\CacheController;` since CacheController is gone. Remove `$cacheController` instantiation.

### Task 1.2: Create CSRF protection utility

**Files:** Create `src/Security/CsrfProtection.php`, delete `src/Http/Middleware/CsrfMiddleware.php`

- [ ] **Step 1: Create src/Security/CsrfProtection.php**

```php
<?php

declare(strict_types=1);

namespace App\Security;

class CsrfProtection
{
    private const TOKEN_KEY = 'csrf_token';

    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    public static function getTokenField(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::TOKEN_KEY,
            self::generateToken()
        );
    }

    public static function validateToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($token) || empty($_SESSION[self::TOKEN_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::TOKEN_KEY], $token);
    }

    public static function isReadMethod(string $method): bool
    {
        return in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS']);
    }
}
```

- [ ] **Step 2: Delete CsrfMiddleware.php**

```bash
Remove-Item -LiteralPath "apcu/src/Http/Middleware/CsrfMiddleware.php" -Force
```

### Task 1.3: Wire CSRF validation in public/app.php

**Files:** `public/app.php`

- [ ] **Step 1: Add CSRF check in the dispatch loop**

After creating the request and before dispatching, add:
```php
// CSRF protection for mutating requests
if (!\App\Security\CsrfProtection::isReadMethod($request->getMethod())) {
    $token = $request->post('csrf_token') ?: $request->getHeader('X-CSRF-TOKEN');
    if (!\App\Security\CsrfProtection::validateToken($token)) {
        $response = $request->isAjax()
            ? (new Response())->json(['success' => false, 'message' => 'Invalid CSRF token'], 403)
            : new Response('Invalid CSRF token', 403);
        $response->send();
        exit;
    }
}
```

### Task 1.4: Create RateLimiter

**Files:** Create `src/Security/RateLimiter.php`

- [ ] **Step 1: Create src/Security/RateLimiter.php**

```php
<?php

declare(strict_types=1);

namespace App\Security;

class RateLimiter
{
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(int $maxRequests = 10, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function isAllowed(string $ip): bool
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return true; // Allow if APCu is off
        }

        $key = 'rate_limit:' . $ip;
        $data = apcu_fetch($key);

        if ($data === false) {
            apcu_store($key, json_encode([time()]), $this->windowSeconds);
            return true;
        }

        $times = json_decode($data, true);
        // Remove entries outside the window
        $cutoff = time() - $this->windowSeconds;
        $times = array_values(array_filter($times, fn($t) => $t > $cutoff));

        if (count($times) >= $this->maxRequests) {
            return false;
        }

        $times[] = time();
        apcu_store($key, json_encode($times), $this->windowSeconds);
        return true;
    }
}
```

- [ ] **Step 2: Wire rate limiter in public/app.php for destructive routes**

After creating the request, before dispatching:
```php
// Rate limiting for destructive operations
$destructivePaths = ['/clear-cache', '/delete-key'];
$requestPath = parse_url($request->server('REQUEST_URI'), PHP_URL_PATH);
// Strip base path
$basePath = parse_url(BASE_URL, PHP_URL_PATH) ?? '';
if ($basePath && str_starts_with($requestPath, $basePath)) {
    $requestPath = substr($requestPath, strlen($basePath));
}
$requestPath = '/' . ltrim($requestPath, '/');

if (!\App\Security\CsrfProtection::isReadMethod($request->getMethod())) {
    foreach ($destructivePaths as $destructivePath) {
        if (str_starts_with($requestPath, $destructivePath)) {
            $rateLimiter = new \App\Security\RateLimiter();
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (!$rateLimiter->isAllowed($clientIp)) {
                $response = (new Response())->json([
                    'success' => false,
                    'message' => 'Rate limit exceeded. Try again later.'
                ], 429);
                $response->send();
                exit;
            }
            break;
        }
    }
}
```

### Task 1.5: Update FormHelper to use new CSRF utility

**Files:** `src/View/Helper/FormHelper.php`

- [ ] **Step 1: Update FormHelper.php**

```php
<?php

declare(strict_types=1);

namespace App\View\Helper;

use App\Security\CsrfProtection;

class FormHelper
{
    public static function csrfField(): string
    {
        return CsrfProtection::getTokenField();
    }

    public static function method(string $method): string
    {
        $method = strtoupper($method);
        $validMethods = ['PUT', 'PATCH', 'DELETE'];
        if (in_array($method, $validMethods, true)) {
            return sprintf(
                '<input type="hidden" name="_method" value="%s">',
                htmlspecialchars($method, ENT_QUOTES, 'UTF-8')
            );
        }
        return '';
    }
}
```

### Task 1.6: Add input validation

**Files:** `src/Controller/DashboardController.php`

- [ ] **Step 1: Add whitelist validation for sort fields and pagination caps**

In `entries()`, add before the `getPaginatedCacheEntries` call:
```php
$allowedSortFields = ['key', 'size', 'hits', 'created', 'modified', 'ttl', 'expires'];
if (!in_array($sortField, $allowedSortFields, true)) {
    $sortField = 'key';
}
$page = max(1, min($page, 10000));
```

In `deleteKey()`, add validation:
```php
$key = base64_decode($request->post('key'));
if ($key === false || trim($key) === '') {
    return (new Response())->json(['success' => false, 'message' => 'Invalid key'], 400);
}
```

### Task 1.7: Remove .htpasswd from repo

**Files:** `apcu/.htpasswd`

- [ ] **Step 1: Add .htpasswd to .gitignore**

Create or append to `apcu/.gitignore`:
```
.htpasswd
.env
```

---

## Phase 2 — Code Quality & Dependencies

### Task 2.1: Add strict_types and property types

**Files:** `src/Cache/CacheEntry.php`, `src/Cache/CacheManager.php`, `src/Controller/DashboardController.php`, `src/Controller/OpcacheController.php`, `src/Http/Request.php`, `src/Http/Response.php`, `src/Http/Session.php`, `src/Routing/Router.php`, `src/Services/CacheService.php`, `src/Services/OpcacheService.php`, `src/View/View.php`, `src/View/Helper/FormHelper.php`

- [ ] **Step 1: Add `declare(strict_types=1)` to every src/ PHP file** (it's currently only in `public/app.php`)

Insert at the top of each file (after `<?php`):
```php
declare(strict_types=1);
```

- [ ] **Step 2: Add missing return types** — `Response` methods return `self` but are not typed, `CsrfProtection::validateToken` returns `bool`, `getEntries` return `array`, etc.

### Task 2.2: Update composer.json

**Files:** `composer.json`

- [ ] **Step 1: Update dependencies**

```json
{
    "name": "apcu/cache-viewer",
    "description": "APCu Cache Viewer",
    "type": "project",
    "require": {
        "php": ">=8.3",
        "ext-apcu": "*",
        "ext-json": "*",
        "symfony/var-dumper": "^7.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

- [ ] **Step 2: Run composer update**

```bash
cd apcu
composer update --no-dev --optimize-autoloader
```

---

## Phase 3 — UI Overhaul

### Task 3.1: Upgrade Bootstrap to 5.3 with dark mode support

**Files:** `templates/layout.php`

- [ ] **Step 1: Update CDN links in layout.php**

Change Bootstrap CSS from 5.1.3 to 5.3.x:
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
```

Change Bootstrap JS bundle:
```html
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
```

- [ ] **Step 2: Add dark mode CSS variables**

Above the closing `</head>` tag, add a `data-bs-theme` attribute on `<html>` and inline theme toggle CSS:
```html
<script>
    // Apply saved theme immediately to prevent flash
    (function() {
        const theme = localStorage.getItem('apcu-theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', theme);
    })();
</script>
```

### Task 3.2: Add theme toggle to navbar

**Files:** `templates/layout.php`

- [ ] **Step 1: Add theme toggle button in navbar**

In the `navbar-nav me-auto` list, after the OPcache link:
```html
<li class="nav-item">
    <button id="theme-toggle" class="nav-link btn btn-link" onclick="toggleTheme()">
        <i class="fas fa-moon"></i>
    </button>
</li>
```

- [ ] **Step 2: Add theme toggle JavaScript**

In the main script block, add:
```javascript
function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-bs-theme') || 'light';
    const next = current === 'light' ? 'dark' : 'light';
    html.setAttribute('data-bs-theme', next);
    localStorage.setItem('apcu-theme', next);
    // Update icon
    document.querySelector('#theme-toggle i').className = next === 'light' ? 'fas fa-moon' : 'fas fa-sun';
}
```

### Task 3.3: Consolidate duplicate toast containers

**Files:** `templates/layout.php`, `templates/dashboard.php`

- [ ] **Step 1: Remove duplicate toast container from dashboard.php**

Delete lines 301-309 from `dashboard.php` (the second toast container div).

- [ ] **Step 2: Keep only the toast container in layout.php** (lines 136 already has it)

### Task 3.4: Add auto-refresh toggle

**Files:** `templates/layout.php`

- [ ] **Step 1: Add auto-refresh button in navbar**

After the theme toggle, add:
```html
<li class="nav-item">
    <button id="refresh-toggle" class="nav-link btn btn-link" onclick="toggleAutoRefresh()">
        <i class="fas fa-sync-alt"></i> <span id="refresh-status">Auto</span>
    </button>
</li>
```

- [ ] **Step 2: Add auto-refresh JavaScript**

```javascript
let autoRefreshInterval = null;

function toggleAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        document.getElementById('refresh-status').textContent = 'Off';
        return;
    }
    autoRefreshInterval = setInterval(() => {
        // Don't refresh if a modal is open
        if (document.querySelector('.modal.show')) return;
        // Reload the main Turbo frame
        const frame = document.querySelector('turbo-frame#main-content');
        if (frame) {
            Turbo.visit(window.location.href, { action: 'replace' });
        } else {
            window.location.reload();
        }
    }, 15000); // Every 15 seconds
    document.getElementById('refresh-status').textContent = '15s';
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
});
```

### Task 3.5: Add bulk delete checkboxes to entries table

**Files:** `templates/_entries.php`

- [ ] **Step 1: Add "Select All" checkbox in thead**

Add before the Key `<th>`:
```html
<th class="text-center" style="width: 40px;">
    <input type="checkbox" id="select-all" onchange="toggleSelectAll(this)">
</th>
```

- [ ] **Step 2: Add checkbox to each row**

Add after the opening `<tr>` and before the key `<td>`:
```html
<td class="text-center align-middle">
    <input type="checkbox" class="entry-checkbox" value="<?= base64_encode($this->e($entry->getKey())) ?>">
</td>
```

- [ ] **Step 3: Update colspan for empty state**

Change `colspan="7"` to `colspan="8"`.

- [ ] **Step 4: Add bulk delete button above the table**

After the search form div and before the first pagination:
```html
<div class="mb-3">
    <button id="delete-selected" class="btn btn-sm btn-danger" onclick="deleteSelected()" disabled>
        <i class="fas fa-trash-alt"></i> Delete Selected (<span id="selected-count">0</span>)
    </button>
</div>
```

- [ ] **Step 5: Add JavaScript for bulk operations**

In layout.php's script section, add:
```javascript
function toggleSelectAll(master) {
    document.querySelectorAll('.entry-checkbox').forEach(cb => cb.checked = master.checked);
    updateDeleteButton();
}

function updateDeleteButton() {
    const checked = document.querySelectorAll('.entry-checkbox:checked').length;
    const btn = document.getElementById('delete-selected');
    if (btn) {
        btn.disabled = checked === 0;
        document.getElementById('selected-count').textContent = checked;
    }
}

function deleteSelected() {
    const checked = document.querySelectorAll('.entry-checkbox:checked');
    if (checked.length === 0) return;
    if (!confirm(`Delete ${checked.length} selected cache entries?`)) return;
    
    const keys = Array.from(checked).map(cb => cb.value);
    
    // Delete each sequentially (simpler than batch endpoint)
    let completed = 0;
    let failed = 0;
    
    keys.forEach(key => {
        const form = document.querySelector(`.delete-key-form[data-key="${key}"]`);
        if (form) {
            // Trigger the existing delete flow
            const event = new Event('submit');
            form.dispatchEvent(event);
            completed++;
        } else {
            // Fallback: direct fetch
            fetch(`${BASE_URL}delete-key/${encodeURIComponent(key)}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            }).then(r => {
                if (r.ok) completed++; else failed++;
            });
        }
    });
}

// Listen for checkbox changes
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('entry-checkbox')) {
        updateDeleteButton();
    }
});
```

### Task 3.6: Add export button

**Files:** `templates/dashboard.php`

- [ ] **Step 1: Add export button in the header**

Next to the Clear Cache button:
```php
<button type="button" class="btn btn-outline-secondary me-2" onclick="exportCacheData()">
    <i class="fas fa-download me-1"></i> Export JSON
</button>
```

- [ ] **Step 2: Add export JavaScript**

```javascript
function exportCacheData() {
    fetch(`${BASE_URL}entries?per_page=10000`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'apcu-cache-export-' + new Date().toISOString().slice(0, 10) + '.json';
        a.click();
        URL.revokeObjectURL(url);
        showToast('Export downloaded', 'success');
    })
    .catch(err => {
        showToast('Export failed: ' + err.message, 'danger');
    });
}
```

### Task 3.8: Add updateEntry to CacheManager and inline editing

**Files:** `src/Cache/CacheManager.php`, `src/Services/CacheService.php`, `src/Controller/DashboardController.php`

- [ ] **Step 1: Add updateEntry() to CacheManager**

```php
public function updateEntry(string $key, $value, int $ttl = 0): bool
{
    if (!function_exists('apcu_enabled') || !apcu_enabled()) {
        return false;
    }
    return apcu_store($key, $value, $ttl);
}
```

- [ ] **Step 2: Add updateCacheEntry() to CacheService**

```php
public function updateCacheEntry(string $key, $value, int $ttl = 0): bool
{
    return $this->cacheManager->updateEntry($key, $value, $ttl);
}
```

- [ ] **Step 3: Add edit endpoint to DashboardController**

```php
public function updateKey(Request $request): Response
{
    try {
        $key = $request->post('key');
        $value = $request->post('value');
        $ttl = (int)($request->post('ttl', 0));
        
        if (empty($key)) {
            return (new Response())->json(['success' => false, 'message' => 'Key is required'], 400);
        }
        
        $result = $this->cacheService->updateCacheEntry($key, $value, $ttl);
        return (new Response())->json([
            'success' => $result,
            'message' => $result ? 'Key updated successfully' : 'Failed to update key'
        ], $result ? 200 : 500);
    } catch (\Throwable $e) {
        error_log('Error updating key: ' . $e->getMessage());
        return (new Response())->json([
            'success' => false,
            'message' => 'Failed to update key: ' . $e->getMessage()
        ], 500);
    }
}
```

- [ ] **Step 4: Add PUT route in routes/web.php**

```php
$router->put('/update-key', fn(Request $request) => $dashboardController->updateKey($request));
```

- [ ] **Step 5: Add inline edit UI in the entries table** (add edit button in _entries.php actions column)

```html
<button type="button" class="btn btn-sm btn-outline-warning" onclick="editCacheKey('<?= $this->e($entry->getKey()) ?>', '<?= $this->e(json_encode($entry->getValue())) ?>')">
    <i class="fas fa-edit"></i>
</button>
```

### Task 3.9: Add deleteMultiple for bulk delete support

**Files:** `src/Cache/CacheManager.php`, `src/Services/CacheService.php`, `src/Controller/DashboardController.php`

- [ ] **Step 1: Add deleteMultiple() to CacheManager**

```php
public function deleteMultiple(array $keys): array
{
    if (!function_exists('apcu_enabled') || !apcu_enabled()) {
        return [];
    }
    $results = [];
    foreach ($keys as $key) {
        $results[$key] = apcu_delete($key);
    }
    return $results;
}
```

- [ ] **Step 2: Add deleteMultipleEntries() to CacheService**

```php
public function deleteMultipleEntries(array $keys): array
{
    return $this->cacheManager->deleteMultiple($keys);
}
```

- [ ] **Step 3: Add batch delete endpoint to DashboardController**

```php
public function deleteMultiple(Request $request): Response
{
    try {
        $keys = $request->post('keys', []);
        if (!is_array($keys) || empty($keys)) {
            return (new Response())->json(['success' => false, 'message' => 'No keys provided'], 400);
        }
        
        $results = $this->cacheService->deleteMultipleEntries($keys);
        $successCount = count(array_filter($results));
        
        return (new Response())->json([
            'success' => $successCount > 0,
            'deleted' => $successCount,
            'total' => count($keys),
            'message' => "$successCount of {$successCount} keys deleted"
        ]);
    } catch (\Throwable $e) {
        error_log('Error deleting multiple keys: ' . $e->getMessage());
        return (new Response())->json([
            'success' => false,
            'message' => 'Failed to delete keys: ' . $e->getMessage()
        ], 500);
    }
}
```

- [ ] **Step 4: Add batch delete route**

```php
$router->post('/delete-multiple', fn(Request $request) => $dashboardController->deleteMultiple($request));
```

- [ ] **Step 5: Update bulk delete JavaScript** to call the batch endpoint instead of individual deletes

```javascript
function deleteSelected() {
    const checked = document.querySelectorAll('.entry-checkbox:checked');
    if (checked.length === 0) return;
    if (!confirm(`Delete ${checked.length} selected cache entries?`)) return;
    
    const keys = Array.from(checked).map(cb => cb.value);
    const btn = document.getElementById('delete-selected');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
    
    fetch(`${BASE_URL}delete-multiple`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({ keys })
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            // Remove deleted rows
            checked.forEach(cb => {
                const row = cb.closest('tr');
                if (row) row.remove();
            });
        }
    })
    .catch(err => showToast('Delete failed: ' + err.message, 'danger'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete Selected';
    });
}
```

### Task 3.10: Mobile-responsive entries table

**Files:** `templates/_entries.php`

- [ ] **Step 1: Add responsive table wrapper class**

Change `<div class="table-responsive">` to keep it, and add `data-responsive` attributes:
On `<th class="text-end">Last Modified</th>` add `class="d-none d-md-table-cell text-end"` and similarly for the `<td>` in rows.

Show/Hide columns based on viewport:
```html
<!-- TTL header -->
<th class="text-end d-none d-lg-table-cell">TTL</th>
<!-- TTL cell -->
<td class="text-end align-middle d-none d-lg-table-cell">...</td>
```

---

## Phase 4 — Testing

### Task 4.1: Create phpunit.xml

**Files:** Create `phpunit.xml`

- [ ] **Step 1: Create phpunit.xml**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

### Task 4.2: CacheEntry unit tests

**Files:** Create `tests/Unit/CacheEntryTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Cache\CacheEntry;
use PHPUnit\Framework\TestCase;

class CacheEntryTest extends TestCase
{
    public function testConstructsWithRequiredFields(): void
    {
        $entry = new CacheEntry('test-key', 'test-value');
        $this->assertSame('test-key', $entry->getKey());
        $this->assertSame('test-value', $entry->getValue());
        $this->assertSame(0, $entry->getHits());
        $this->assertSame(0, $entry->getSize());
    }

    public function testConstructsWithAllFields(): void
    {
        $entry = new CacheEntry('k', 'v', 10, 256, 1000, 2000, 300, 5000);
        $this->assertSame(10, $entry->getHits());
        $this->assertSame(256, $entry->getSize());
        $this->assertSame(1000, $entry->getCreated());
        $this->assertSame(2000, $entry->getModified());
        $this->assertSame(300, $entry->getTtl());
        $this->assertSame(5000, $entry->getExpires());
    }

    public function testIsExpiredReturnsTrueWhenExpired(): void
    {
        $entry = new CacheEntry('k', 'v', 0, 0, 0, 0, 1, time() - 10);
        $this->assertTrue($entry->isExpired());
    }

    public function testIsExpiredReturnsFalseWhenNotExpired(): void
    {
        $entry = new CacheEntry('k', 'v', 0, 0, 0, 0, 0, time() + 3600);
        $this->assertFalse($entry->isExpired());
    }

    public function testIsExpiredReturnsFalseForNoTtl(): void
    {
        $entry = new CacheEntry('k', 'v', 0, 0, 0, 0, 0, 0);
        $this->assertFalse($entry->isExpired());
    }
}
```

- [ ] **Step 2: Run tests**

```bash
cd apcu
vendor/bin/phpunit tests/Unit/CacheEntryTest.php
```
Expected: All pass

### Task 4.3: Router unit tests

**Files:** Create `tests/Unit/RouterTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Request;
use App\Routing\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    public function testMatchesGetRoute(): void
    {
        $handler = fn() => 'hello';
        $this->router->get('/test', $handler);

        $request = $this->createRequest('GET', '/test');
        $route = $this->router->match($request);

        $this->assertNotNull($route);
        $this->assertSame($handler, $route['handler']);
    }

    public function testReturnsNullForUnknownRoute(): void
    {
        $request = $this->createRequest('GET', '/nonexistent');
        $this->assertNull($this->router->match($request));
    }

    public function testReturnsNullForWrongMethod(): void
    {
        $this->router->get('/test', fn() => '');
        $request = $this->createRequest('POST', '/test');
        $this->assertNull($this->router->match($request));
    }

    public function testExtractsRouteParams(): void
    {
        $this->router->get('/users/{id}', fn() => '');
        $request = $this->createRequest('GET', '/users/42');
        $route = $this->router->match($request);

        $this->assertNotNull($route);
        $this->assertSame(['id' => '42'], $route['params']);
    }

    public function testDispatchReturnsResponse(): void
    {
        $this->router->get('/test', fn() => new \App\Http\Response('ok'));
        $request = $this->createRequest('GET', '/test');
        $response = $this->router->dispatch($request);

        $this->assertInstanceOf(\App\Http\Response::class, $response);
    }

    public function testDispatchReturns404ForNoMatch(): void
    {
        $request = $this->createRequest('GET', '/nothing');
        $response = $this->router->dispatch($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    private function createRequest(string $method, string $path): Request
    {
        $server = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $path,
        ];
        return new Request([], [], [], $server, $path, $method);
    }
}
```

- [ ] **Step 2: Run tests**

```bash
cd apcu
vendor/bin/phpunit tests/Unit/RouterTest.php
```
Expected: All pass

### Task 4.4: Request unit tests

**Files:** Create `tests/Unit/RequestTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testGetReturnsQueryParam(): void
    {
        $request = new Request(['foo' => 'bar'], [], [], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'], '/', 'GET');
        $this->assertSame('bar', $request->get('foo'));
    }

    public function testGetReturnsDefault(): void
    {
        $request = new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'], '/', 'GET');
        $this->assertNull($request->get('missing'));
        $this->assertSame('default', $request->get('missing', 'default'));
    }

    public function testGetMethod(): void
    {
        $request = new Request([], [], [], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/'], '/', 'POST');
        $this->assertSame('POST', $request->getMethod());
    }

    public function testIsAjax(): void
    {
        $request = new Request([], [], [], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_X_REQUESTED_WITH' => 'xmlhttprequest',
        ], '/', 'GET');
        $this->assertTrue($request->isAjax());
    }

    public function testIsNotAjax(): void
    {
        $request = new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'], '/', 'GET');
        $this->assertFalse($request->isAjax());
    }

    public function testIsGet(): void
    {
        $request = new Request([], [], [], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'], '/', 'GET');
        $this->assertTrue($request->isGet());
        $this->assertFalse($request->isPost());
    }

    public function testIsPost(): void
    {
        $request = new Request([], [], [], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/'], '/', 'POST');
        $this->assertTrue($request->isPost());
        $this->assertFalse($request->isGet());
    }

    public function testGetHeader(): void
    {
        $request = new Request([], [], [], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_X_CSRF_TOKEN' => 'abc123',
        ], '/', 'GET');
        $this->assertSame('abc123', $request->getHeader('X-CSRF-TOKEN'));
    }
}
```

- [ ] **Step 2: Run tests**

```bash
cd apcu
vendor/bin/phpunit tests/Unit/RequestTest.php
```
Expected: All pass

---

## Phase 5 — Polish

### Task 5.1: Remove unused .env keys

**Files:** `.env.example`

- [ ] **Step 1: Clean up .env.example**

```env
# Application Environment
APP_ENV=development
APP_DEBUG=true
APP_URL=http://local.bboa22/apcu/

# Timezone
TIMEZONE=Europe/Warsaw

# Logging
LOG_CHANNEL=errorlog
LOG_LEVEL=debug
```

Remove: `SESSION_DRIVER`, `SESSION_LIFETIME`, `CACHE_DRIVER`, `CACHE_PREFIX`, `CACHE_TTL`, `APP_KEY`, `APCU_ENABLED`, `APCU_TTL`, `LOCALE`.

### Task 5.2: Update AGENTS.md

**Files:** `AGENTS.md`

- [ ] **Step 1: Remove references to deleted files**

Remove `bootstrap/app.php`, `CacheController.php`, all middleware files, `CsrfMiddleware.php` from the project structure and known bugs.

- [ ] **Step 2: Add new architecture facts**

Add: `routes/web.php` for route definitions, `src/Security/CsrfProtection.php` for CSRF, `src/Security/RateLimiter.php` for rate limiting.

- [ ] **Step 3: Update known bugs** — remove fixed bugs, keep any still relevant.

- [ ] **Step 4: Add test commands**

```
- Run tests: `vendor/bin/phpunit`
- Run single test: `vendor/bin/phpunit tests/Unit/CacheEntryTest.php`
```

### Task 5.3: Final verification

- [ ] **Step 1: Verify LSP diagnostics**

```bash
# Check PHP syntax on all modified files
php -l apcu/public/app.php
php -l apcu/src/Http/Request.php
php -l apcu/src/Controller/DashboardController.php
php -l apcu/src/Security/CsrfProtection.php
php -l apcu/src/Security/RateLimiter.php
php -l routes/web.php
```

- [ ] **Step 2: Run full test suite**

```bash
cd apcu
vendor/bin/phpunit
```

- [ ] **Step 3: Start dev server and smoke-test**

```bash
php -S localhost:8080 -t apcu/public
# Visit http://localhost:8080/apcu/ — should show dashboard
# Visit http://localhost:8080/apcu/opcache — should show OPcache page
# Test: search, sort, pagination, clear cache, delete key
```
