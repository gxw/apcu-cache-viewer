# APCu Cache Viewer — Full Refresh Design

**Date**: 2026-06-03
**Scope**: Full rewrite — bug fixes, security, code quality, UI overhaul, testing
**Approach**: Incremental phases, each independently deployable

---

## Phase 0 — Bug Fixes (foundation)

### 0.1 Fix Request class using `$_SERVER` directly
**Files**: `src/Http/Request.php`
- `getMethod()` reads from `$_SERVER['REQUEST_METHOD']` instead of `$this->method`
- `server()` reads from `$_SERVER` instead of `$this->server`
- `isAjax()`, `getQueryParams()`, `getPostData()`, `getHeader()` all bypass instance properties
- **Fix**: Replace all `$_SERVER` references with `$this->server`, replace `$_GET` with `$this->get`, `$_POST` with `$this->post`
- **Caveat**: `MethodSpoofingMiddleware` mutates `$_SERVER['REQUEST_METHOD']` directly. After fixing Request, it must mutate `$request->method` instead. Both changes together.

### 0.2 Remove duplicate timezone set
**File**: `public/app.php` lines 20 and 37
- **Fix**: Remove the first `date_default_timezone_set('Europe/Warsaw')` at line 20 (it runs before autoloader, but the second one at line 37 is after autoloader and is the better position).

### 0.3 Fix `CacheController::index()` wrong param order
**File**: `src/Controller/CacheController.php` line 30
- Current: `getPaginatedCacheEntries($page, $perPage, $search, $sort)`
- Correct: `getPaginatedCacheEntries($page, $search, $sort, 'asc', $perPage)`
- Note: This method is dead code in current entry point. Fix anyway for correctness.

### 0.4 Fix `error.php` calling `$this->layout()`
**File**: `templates/errors/error.php`
- `$this->layout('layout', ...)` should be `$view->extend('layout')`
- This template is only rendered by `CacheController::index()`, which is dead code.
- Fix both the template and either wire it or keep consistent for when it's used.

### 0.5 Remove `error_log()` debug calls
**Files**:
- `CacheManager.php` — ~7 debug `error_log()` calls
- `DashboardController.php` — 1 debug log in `deleteKey()`
- `public/app.php` — commented-out debug logs (clean up comments)
- **Fix**: Remove all `error_log()` calls that are debug-only (not error handling).
  Keep the ones in `public/app.php`'s catch block (they're legitimate error logging).

### 0.6 Remove dead code
**Files to remove**:
- `bootstrap/app.php` — alternative entry point, not used. Remove entirely.
- `src/Http/Middleware/MethodSpoofingMiddleware.php` — only used by dead `bootstrap/app.php`
- `src/Http/Middleware/MiddlewareInterface.php` — only used by dead middleware
- `src/Http/Middleware/CsrfMiddleware.php` — only used by dead `bootstrap/app.php` (will be recreated as active middleware in Phase 1)
- `templates/errors/error.php` — only rendered by dead `CacheController::index()`
- `templates/components/flash_messages.php` — duplicate of `_flash_messages.php`

**Files to consolidate**:
- `src/Controller/CacheController.php` — merge relevant methods into `DashboardController`, then remove
  - `clearCache()` → merge with `DashboardController::clearCache()`
  - `deleteKey()` → merge with `DashboardController::deleteKey()`
  - `getKeyValue()` → keep as is, move to `DashboardController`
  - `index()` → remove (dead code)

---

## Phase 1 — Architecture & Security

### 1.1 Consolidate to single entry point
- Keep `public/app.php` as the sole entry point
- Move all route definitions into a dedicated `routes/web.php` file (included from `app.php`)
- This keeps the entry point clean and routes discoverable

### 1.2 Wire CSRF protection
- **Server side**: Add a lightweight `CsrfMiddleware::validate()` call in `public/app.php` for POST/PUT/DELETE routes. No need for full middleware pipeline — a simple static check in the routing loop.
- **Client side**: All AJAX calls already send `X-CSRF-TOKEN` header. Verify the token is sent on every mutating request.
- **Forms**: All HTML forms must include `<?= $view->csrfField() ?>` for POST submissions.

### 1.3 Rate limiting for destructive operations
- Add simple in-memory rate limiting for `/clear-cache` and `/delete-key` endpoints
- Store request timestamps per IP in APCu itself (ironic but practical)
- Limit: 10 operations per minute per IP

### 1.4 Input validation
- **Base64 decode**: Validate `base64_decode()` returns meaningful data before using keys
- **Sort fields**: Whitelist allowed sort fields (`key`, `hits`, `size`, `created`, `modified`, `ttl`, `expires`)
- **Pagination**: Cap `$perPage` and `$page` to reasonable bounds (max 100 per page, max 10000 pages)
- **Cache keys**: Validate key exists before attempting delete

### 1.5 Remove htpasswd from repo
- `.htpasswd` contains a real password hash — either remove from repo or add to `.gitignore`

---

## Phase 2 — Code Quality & Dependencies

### 2.1 PHP type declarations
- Add `declare(strict_types=1)` to all files (currently only `public/app.php` has it)
- Add proper return types to all methods (many are missing)
- Fix `CacheEntry::getValue()` — returns `mixed` with no type hint

### 2.2 Update composer dependencies
- `symfony/var-dumper`: `^6.0` → `^7.0` (PHP 8.3 compatible)
- Add `phpunit/phpunit` `^11.0` as dev dependency
- Add `ext-json` to `require` (currently implicit)

### 2.3 Consistent error handling
All controllers should handle errors consistently:
- `CacheController` wraps everything in try/catch
- `DashboardController` does NOT wrap in try/catch
- Unify pattern: let exceptions bubble up to `public/app.php`'s catch block, log there, return 500

### 2.4 Remove redundant files
- `apcu/package-lock.json` — not a Node project, artifact
- `apcu/public/error.php` — replaced by template-based error pages
- `apcu/test.php` — replaced by proper test suite (Phase 4)

---

## Phase 3 — UI Overhaul

### 3.1 Dark mode
- CSS custom properties for theming
- Bootstrap 5.3+ has built-in dark mode support — upgrade CDN link
- Add theme toggle in navbar with localStorage persistence
- All cards, tables, modals, and charts must respect the theme

### 3.2 Live auto-refresh
- Add a toggleable auto-refresh that polls stats and entries every N seconds
- Use Turbo's built-in periodical refresh or a simple `setInterval` + `fetch`
- Don't auto-refresh when a modal is open or user is interacting

### 3.3 Cache entry editing
- Inline value editing for cache entries
- Modal with a textarea pre-filled with current value
- Save button that sends PUT request to update the key
- Requires `CacheManager::updateEntry()` method — wraps `apcu_store()`

### 3.4 Bulk operations
- Checkbox column in entries table
- "Select All" / "Deselect All" toggle
- "Delete Selected" button that sends batch delete
- Confirm dialog before executing

### 3.5 Export functionality
- JSON export of all cache entries
- Chart export (PNG download of hit/miss chart)
- "Export" button in the header area

### 3.6 Mobile UI improvements
- Stats cards stack better on mobile (already use `col-md-6`, but some are cramped)
- Entries table: collapse columns on small screens (hide TTL, Created, Modified on <768px)
- Action buttons: use icon-only on mobile with tooltips
- Search bar: full-width on mobile

### 3.7 Loading states and UX polish
- Skeleton loading for initial dashboard load (not just AJAX operations)
- Better empty states (illustrations or icons)
- Confirmation dialogs use Bootstrap modals instead of `confirm()`
- Toast notifications positioned properly (already done, but duplicate toast containers in layout.php and dashboard.php — consolidate)

### 3.8 Remove duplicate toast containers
- `templates/layout.php` has a `#toast-container` div
- `templates/dashboard.php` also has a toast div
- Consolidate to a single toast container in `layout.php`

---

## Phase 4 — Testing

### 4.1 PHPUnit setup
- `phpunit.xml` with PSR-4 autoloading
- Test directory: `apcu/tests/`
- Bootstrap: `vendor/autoload.php`

### 4.2 Unit tests
- `CacheEntryTest` — construction, getters, `isExpired()`
- `CacheManagerTest` — mock APCu functions (using `apcu_*` function mock or a test helper)
- `CacheServiceTest` — delegates to mock `CacheManager`
- `RouterTest` — route matching, parameter extraction, 404 handling
- `RequestTest` — get/post/server access after fixing
- `ViewTest` — rendering, sections, extends, partials

### 4.3 Integration test
- `test_router.php` to be converted to proper PHPUnit test
- Test that `public/app.php` returns 200 for `/` with mocked `$_SERVER`

### 4.4 Remove ad-hoc test files
- Remove `apcu/test.php` and `apcu/test_router.php` after proper tests cover their scenarios

---

## Phase 5 — Polish & Cleanup

### 5.1 AGENTS.md update
- Update with any new patterns, commands, or architecture changes from this refresh

### 5.2 `.env.example` cleanup
- Remove unused keys (`SESSION_DRIVER`, `CACHE_DRIVER`, `APCU_ENABLED`, `APP_KEY`)
- Keep only what's actually used: `APP_ENV`, `APP_DEBUG`, `APP_URL`, `TIMEZONE`

### 5.3 HTML/CSS cleanup
- Remove unused `templates/_flash_messages.php` if consolidated to `components/flash_messages.php`
- Either use `templates/components/` consistently or inline flash messages in layout.php — pick one
- Minify CSS in `app.min.css` to match `app.css` after changes

---

## Implementation order

```
Phase 0: Bug fixes          → independently testable after each fix
Phase 1: Architecture       → depends on Phase 0 Request fix
Phase 2: Code quality       → can run in parallel with Phase 3
Phase 3: UI overhaul        → can start after Phase 1 entry point consolidation
Phase 4: Testing            → can start after any phase, build incrementally
Phase 5: Polish             → last
```

Phases 2, 3, and 4 can be worked in parallel. Phases 0 and 1 must be sequential.

## Risk notes

- **Request fix + MethodSpoofingMiddleware**: These two are coupled. If both aren't fixed together, method spoofing breaks. Since `bootstrap/app.php` is being removed (Phase 0.6), `MethodSpoofingMiddleware` is dead code anyway. **Fix Request first, remove MethodSpoofingMiddleware, done.**
- **CSRF after removing middleware**: The existing `CsrfMiddleware` class can be kept but refactored from middleware-chain pattern to a static utility. The view helper `FormHelper::csrfField()` already calls it statically — that pattern works.
- **APCu availability**: All APCu-dependent code needs to handle APCu being disabled gracefully (existing pattern is good, just replicate it consistently).
