<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuditService;
use App\Services\CacheService;
use App\View\View;

class DashboardController
{
    private View $view;
    private CacheService $cacheService;
    private AuditService $auditService;

    public function __construct(View $view, CacheService $cacheService, ?AuditService $auditService = null)
    {
        $this->view = $view;
        $this->cacheService = $cacheService;
        $this->auditService = $auditService ?? new AuditService();
    }

    public function index(Request $request): Response
    {
        $detailedStats = $this->cacheService->getDetailedStats();

        $this->view->setData([
            'stats' => $detailedStats,
        ]);

        $content = $this->view->render('dashboard', [
            'title' => 'APCu Cache Viewer'
        ]);

        return new Response($content);
    }

    public function entries(Request $request): Response
    {
        $page = (int) $request->get('page', 1);
        $search = $request->get('search', '');
        $sortField = $request->get('sort', 'key');
        $sortOrder = $request->get('order', 'asc');

        $cacheData = $this->cacheService->getPaginatedCacheEntries($page, $search, $sortField, $sortOrder);

        $pinnedKeys = $this->cacheService->getPinnedKeys();

        $this->view->setData([
            'entries' => $cacheData['entries'],
            'pagination' => [
                'current' => $cacheData['current_page'],
                'total' => $cacheData['total_pages'],
                'search' => $search,
                'sort' => $sortField,
                'order' => $sortOrder
            ],
            'search' => $search,
            'sortField' => $sortField,
            'sortOrder' => $sortOrder,
            'pinnedKeys' => $pinnedKeys,
        ]);

        // Turbo Frame request (sort, search, paginate) → partial content only
        if ($request->getHeader('Turbo-Frame') !== null) {
            $content = $this->view->render('_entries');
            return new Response($content);
        }

        // Direct navigation → render full dashboard layout with entries
        $detailedStats = $this->cacheService->getDetailedStats();
        $pinnedKeys = $this->cacheService->getPinnedKeys();
        $this->view->setData(['stats' => $detailedStats, 'pinnedKeys' => $pinnedKeys]);

        $content = $this->view->render('dashboard', [
            'title' => 'APCu Cache Entries'
        ]);

        return new Response($content);
    }

    public function togglePin(Request $request): Response
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $encodedKey = $input['key'] ?? '';
        if ($encodedKey === '') {
            return new Response(json_encode(['success' => false, 'message' => 'No key provided']), 400, ['Content-Type' => 'application/json']);
        }
        $key = base64_decode($encodedKey);
        if ($this->cacheService->isKeyPinned($key)) {
            $this->cacheService->unpinKey($key);
            $this->auditService->log('unpin_key', $key);
            return new Response(json_encode(['success' => true, 'pinned' => false, 'message' => "Unpinned '{$key}'"]), 200, ['Content-Type' => 'application/json']);
        } else {
            $this->cacheService->pinKey($key);
            $this->auditService->log('pin_key', $key);
            return new Response(json_encode(['success' => true, 'pinned' => true, 'message' => "Pinned '{$key}'"]), 200, ['Content-Type' => 'application/json']);
        }
    }

    public function clearCache(Request $request): Response
    {
        $pinned = $this->cacheService->getPinnedKeys();
        $result = $this->cacheService->clearCache(true, $pinned);
        $this->auditService->log('clear_cache', '', $result ? 'Success' : 'Failed' . (!empty($pinned) ? ' — ' . count($pinned) . ' pinned keys preserved' : ''));

        if ($request->isAjax()) {
            return new Response(json_encode([
                'success' => $result,
                'message' => $result ? 'Cache cleared successfully' : 'Failed to clear cache'
            ]), 200, ['Content-Type' => 'application/json']);
        }

        // Redirect back to the dashboard
        return new Response('', 302, ['Location' => '/']);
    }

    public function deleteKey(Request $request): Response
    {
        $key = base64_decode($request->post('key'));

        if ($this->cacheService->isKeyPinned($key)) {
            return new Response(json_encode([
                'success' => false,
                'message' => "Cannot delete pinned key '{$key}'"
            ]), 403, ['Content-Type' => 'application/json']);
        }

        $result = $this->cacheService->deleteKey($key);
        $this->auditService->log('delete_key', $key, $result ? 'Deleted' : 'Failed');

        if ($request->isAjax()) {
            return new Response(json_encode([
                'success' => $result,
                'message' => $result ? 'Key deleted successfully' : 'Failed to delete key'
            ]), 200, ['Content-Type' => 'application/json']);
        }

        // Redirect back to the dashboard
        return new Response('', 302, ['Location' => '/']);
    }

    public function getCache(Request $request, string $key): Response
    {
        $decodedKey = base64_decode($key);
        $entry = $this->cacheService->getCacheEntry($decodedKey);

        if ($entry) {
            return new Response(json_encode([
                'success' => true,
                'value' => $entry->getValue()
            ]), 200, ['Content-Type' => 'application/json']);
        } else {
            return new Response(json_encode([
                'success' => false,
                'message' => "Cache entry '{$decodedKey}' not found"
            ]), 404, ['Content-Type' => 'application/json']);
        }
    }

    public function getKeyValue(string $key): Response
    {
        // Keys are base64-encoded in URLs (matching app convention for checkboxes, delete forms, etc.)
        // Try to decode; fall back to raw key if not valid base64
        $decoded = base64_decode($key, true);
        $lookupKey = $decoded !== false ? $decoded : $key;

        try {
            $entry = $this->cacheService->getCacheEntry($lookupKey);

            if ($entry === null) {
                return new Response(json_encode([
                    'success' => false,
                    'message' => 'Key not found'
                ]), 404, ['Content-Type' => 'application/json']);
            }

            $value = $entry->getValue();

            // Detect value type for frontend formatting
            $valueType = 'plain';
            if (is_string($value)) {
                // Check if it's valid JSON
                json_decode($value);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $valueType = 'json';
                } elseif (preg_match('/^[OaCsdibN]\s*:\s*(?:\d+|true|false|null)/', $value)) {
                    $valueType = 'serialized';
                }
            } elseif (is_array($value) || is_object($value)) {
                $valueType = 'structured';
            } elseif (is_int($value) || is_float($value)) {
                $valueType = 'numeric';
            } elseif (is_bool($value)) {
                $valueType = 'bool';
            } elseif ($value === null) {
                $valueType = 'null';
            }

            return new Response(json_encode([
                'success' => true,
                'value' => $value,
                'value_type' => $valueType,
                'ttl' => $entry->getTtl(),
                'expires_at' => $entry->getExpires(),
            ]), 200, ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'Failed to get key value: ' . $e->getMessage()
            ]), 500, ['Content-Type' => 'application/json']);
        }
    }

    public function deleteMultiple(Request $request): Response
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $keys = $input['keys'] ?? [];

        if (empty($keys) || !is_array($keys)) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'No keys provided'
            ]), 400, ['Content-Type' => 'application/json']);
        }

        $deleted = 0;
        $skipped = 0;
        foreach ($keys as $encodedKey) {
            $key = base64_decode($encodedKey);
            if ($this->cacheService->isKeyPinned($key)) {
                $skipped++;
                continue;
            }
            if ($this->cacheService->deleteCacheEntry($key)) {
                $deleted++;
            }
        }

        $this->auditService->log('delete_multiple', '', "{$deleted} deleted, {$skipped} skipped (pinned)");

        $message = "Deleted {$deleted} of " . count($keys) . " entries";
        if ($skipped > 0) {
            $message .= " ({$skipped} pinned keys skipped)";
        }

        return new Response(json_encode([
            'success' => true,
            'message' => $message
        ]), 200, ['Content-Type' => 'application/json']);
    }

    public function updateKey(Request $request): Response
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $encodedKey = $input['key'] ?? '';
        $newTtl = (int) ($input['ttl'] ?? -1);

        if ($encodedKey === '') {
            return new Response(json_encode([
                'success' => false,
                'message' => 'No key provided'
            ]), 400, ['Content-Type' => 'application/json']);
        }

        if ($newTtl < 0) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'Invalid TTL value'
            ]), 400, ['Content-Type' => 'application/json']);
        }

        $key = base64_decode($encodedKey);
        $entry = $this->cacheService->getCacheEntry($key);

        if ($entry === null) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'Key not found'
            ]), 404, ['Content-Type' => 'application/json']);
        }

        $result = $this->cacheService->updateEntryTtl($key, $entry->getValue(), $newTtl);

        if ($result) {
            return new Response(json_encode([
                'success' => true,
                'message' => "TTL updated for '{$key}'"
            ]), 200, ['Content-Type' => 'application/json']);
        }

        return new Response(json_encode([
            'success' => false,
            'message' => 'Failed to update TTL'
        ]), 500, ['Content-Type' => 'application/json']);
    }

    public function warmupPage(Request $request): Response
    {
        $content = $this->view->render('warmup', [
            'title' => 'Cache Warmup'
        ]);
        return new Response($content);
    }

    public function warmup(Request $request): Response
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input === null) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'Invalid JSON body'
            ]), 400, ['Content-Type' => 'application/json']);
        }

        $entries = $input['entries'] ?? [];
        $ttl = (int) ($input['ttl'] ?? 300);
        $format = $input['format'] ?? 'auto';

        if (!is_array($entries) || empty($entries)) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'No entries provided. Expected {"entries": {"key": "value", ...}}'
            ]), 400, ['Content-Type' => 'application/json']);
        }

        $stored = 0;
        $errors = [];
        foreach ($entries as $key => $value) {
            if (!is_string($key) || $key === '') {
                $errors[] = 'Invalid key at index ' . $stored;
                continue;
            }
            // If format is 'json_string' and value is a string that's JSON, decode it
            $finalValue = $value;
            if ($format === 'json_encoded' && is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $finalValue = $decoded;
                }
            }
            if ($this->cacheService->storeEntry($key, $finalValue, $ttl)) {
                $stored++;
            } else {
                $errors[] = "Failed to store '{$key}'";
            }
        }

        $this->auditService->log('warmup', '', "{$stored} keys stored with TTL={$ttl}");

        return new Response(json_encode([
            'success' => $stored > 0,
            'stored' => $stored,
            'total' => count($entries),
            'errors' => $errors,
            'message' => "Stored {$stored} of " . count($entries) . " entries (TTL: {$ttl}s)"
        ]), 200, ['Content-Type' => 'application/json']);
    }

    public function audit(Request $request): Response
    {
        $logs = $this->auditService->getLogs();
        $this->view->setData(['logs' => $logs]);

        $content = $this->view->render('audit', [
            'title' => 'Audit Log'
        ]);

        return new Response($content);
    }

    public function export(Request $request): Response
    {
        $search = $request->get('search', '');

        // Fetch ALL entries (no pagination)
        $cacheData = $this->cacheService->getPaginatedCacheEntries(1, $search, 'key', 'asc', 999999);

        $csv = fopen('php://temp', 'r+');

        // CSV header row
        fputcsv($csv, ['Key', 'Size (bytes)', 'Hits', 'Created', 'Modified', 'TTL (seconds)', 'Expires', 'Value Type']);

        foreach ($cacheData['entries'] as $entry) {
            fputcsv($csv, [
                $entry->getKey(),
                $entry->getSize(),
                $entry->getHits(),
                date('Y-m-d H:i:s', $entry->getCreated()),
                date('Y-m-d H:i:s', $entry->getModified()),
                $entry->getTtl(),
                $entry->getTtl() > 0 ? date('Y-m-d H:i:s', $entry->getExpires()) : 'Never',
                get_debug_type($entry->getValue()),
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return new Response($content, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="apcu-export-' . date('Y-m-d-His') . '.csv"',
        ]);
    }
}
