# Changelog

## [1.0.0] - 2026-06-08

### Added
- Dashboard with cache statistics (memory, hit rate, entry count, uptime)
- Paginated, searchable, sortable entries table
- Cache value viewer with JSON syntax highlighting, serialized detection, type badges
- Inline TTL editing via modal
- Single delete, bulk delete with checkboxes
- Key pinning — pinned entries survive cache clears and deletes
- Cache warmup — batch import key-value pairs via JSON form (POST /warmup)
- Export all entries as CSV
- Memory & hit rate trend charts (60-point rolling APCu buffer)
- Key breakdown stats (active/expired/TTL buckets)
- Auto-refresh (Off/15s/30s/60s) with localStorage persistence
- OPcache viewer (status + scripts) with reset button
- Audit log (100-entry APCu circular buffer, reverse chronological)
- Server info page (PHP version, extensions, APCu/OPcache ini values)
- Rate limiting on destructive actions (clear, delete, bulk delete, OPcache reset)
- CSRF token validation on all POST/DELETE routes
- Dark mode toggle (Bootstrap 5.3) - beta
- Hotwire Turbo Drive + Turbo Frames for SPA-like navigation
- PHPUnit test suite (42 tests, 66 assertions)
- Custom MVC framework (Router, Request, Response, View, Session)
