<?php

declare(strict_types=1);

namespace App\Cache;

class CacheManager
{
    public function getEntries(
        int $page = 1,
        string $search = '',
        string $sortField = 'key',
        string $sortOrder = 'asc',
        int $perPage = 20
    ): array {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return [
                'entries' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => 0,
                'sort_field' => $sortField,
                'sort_order' => $sortOrder
            ];
        }

        if (class_exists('\APCIterator')) {
            $cacheIterator = new \APCIterator('user');
        } else {
            $cacheInfo = apcu_cache_info();
            $cacheIterator = $cacheInfo['cache_list'] ?? [];
        }
        $entries = [];
        
        foreach ($cacheIterator as $entry) {
            $key = is_array($entry) ? $entry['info'] : $entry->key;
            $entryData = is_array($entry) ? $entry : (array)$entry;

            $value = apcu_fetch($key);

            $entries[] = new CacheEntry(
                $key,
                $value,
                (int) ($entryData['num_hits'] ?? 0),
                (int) ($entryData['mem_size'] ?? 0),
                (int) ($entryData['creation_time'] ?? 0),
                (int) ($entryData['mtime'] ?? 0),
                (int) ($entryData['ttl'] ?? 0),
                (int) (($entryData['mtime'] ?? 0) + ($entryData['ttl'] ?? 0))
            );
        }

        // Apply search filter
        if ($search) {
            $search = strtolower($search);
            $entries = array_filter($entries, function($entry) use ($search) {
                return str_contains(strtolower($entry->getKey()), $search);
            });
        }

        // Apply sorting
        usort($entries, function($a, $b) use ($sortField, $sortOrder) {
            return $this->compareEntries($a, $b, $sortField, $sortOrder);
        });

        // Apply pagination
        $total = count($entries);
        $offset = ($page - 1) * $perPage;
        $entries = array_slice($entries, $offset, $perPage);

        return [
            'entries' => $entries,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => ceil($total / $perPage),
            'sort_field' => $sortField,
            'sort_order' => $sortOrder
        ];
    }

    private function compareEntries(CacheEntry $a, CacheEntry $b, string $field, string $order): int
    {
        $getter = 'get' . str_replace('_', '', ucwords($field, '_'));
        
        if (!method_exists($a, $getter)) {
            $getter = 'getKey';
        }
        
        $valueA = $a->$getter();
        $valueB = $b->$getter();

        $result = $valueA <=> $valueB;
        return $order === 'asc' ? $result : -$result;
    }

    public function clearCache(bool $skipPinned = false, array $pinnedKeys = []): bool
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return false;
        }
        if ($skipPinned && !empty($pinnedKeys)) {
            // Delete entries one by one, skipping pinned keys
            $cacheInfo = apcu_cache_info();
            $cacheList = $cacheInfo['cache_list'] ?? [];
            $deleted = 0;
            foreach ($cacheList as $entry) {
                $key = is_array($entry) ? $entry['info'] : $entry->key;
                if (!in_array($key, $pinnedKeys, true)) {
                    if (apcu_delete($key)) {
                        $deleted++;
                    }
                }
            }
            return $deleted > 0;
        }
        return apcu_clear_cache();
    }

    public function deleteKey(string $key): bool
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return false;
        }
        return apcu_delete($key);
    }

    // ── Pinned keys ──────────────────────────────────────────────

    private function getPinnedStorageKey(): string
    {
        return 'pinned_keys';
    }

    public function getPinnedKeys(): array
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return [];
        }
        $pinned = apcu_fetch($this->getPinnedStorageKey());
        return is_array($pinned) ? $pinned : [];
    }

    public function isKeyPinned(string $key): bool
    {
        return in_array($key, $this->getPinnedKeys(), true);
    }

    public function pinKey(string $key): bool
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return false;
        }
        $pinned = $this->getPinnedKeys();
        if (!in_array($key, $pinned, true)) {
            $pinned[] = $key;
            apcu_store($this->getPinnedStorageKey(), $pinned, 0);
        }
        return true;
    }

    public function unpinKey(string $key): bool
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return false;
        }
        $pinned = $this->getPinnedKeys();
        $filtered = array_values(array_filter($pinned, fn(string $k) => $k !== $key));
        if (count($filtered) !== count($pinned)) {
            apcu_store($this->getPinnedStorageKey(), $filtered, 0);
        }
        return true;
    }

    // ── Entry storage ───────────────────────────────────────────

    public function storeEntry(string $key, mixed $value, int $ttl = 0): bool
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return false;
        }
        return apcu_store($key, $value, $ttl);
    }

    public function getEntry(string $key): ?CacheEntry
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return null;
        }
        $entry = apcu_fetch($key);
        if ($entry === false) {
            return null;
        }

        $info = apcu_key_info($key);

        return new CacheEntry(
            $key,
            $entry,
            (int) ($info['num_hits'] ?? 0),
            (int) ($info['mem_size'] ?? 0),
            (int) ($info['creation_time'] ?? 0),
            (int) ($info['mtime'] ?? 0),
            (int) ($info['ttl'] ?? 0),
            (int) (($info['mtime'] ?? 0) + ($info['ttl'] ?? 0))
        );
    }

    public function getMemoryInfo(): array
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return [
                'total' => 0,
                'used' => 0,
                'free' => 0,
                'usage_percent' => 0,
            ];
        }
        $smaInfo = apcu_sma_info();
        $cacheInfo = apcu_cache_info();

        $totalMemory = $smaInfo['num_seg'] * $smaInfo['seg_size'];
        $usedMemory = $cacheInfo['mem_size'];
        $freeMemory = $smaInfo['avail_mem'];

        return [
            'total' => $totalMemory,
            'used' => $usedMemory,
            'free' => $freeMemory,
            'usage_percent' => $totalMemory > 0 ? ($usedMemory / $totalMemory) * 100 : 0,
        ];
    }

    public function getCacheInfo(): array
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return [
                'hits' => 0,
                'misses' => 0,
                'entries' => 0,
                'uptime' => 0,
                'memory_size' => 0,
                'start_time' => 0,
                'num_slots' => 0,
                'slot_size' => 0
            ];
        }
        $info = apcu_cache_info();

        return [
            'hits' => $info['num_hits'],
            'misses' => $info['num_misses'],
            'entries' => $info['num_entries'],
            'uptime' => time() - $info['start_time'],
            'memory_size' => $info['mem_size'],
            'start_time' => $info['start_time'],
            'num_slots' => $info['num_slots'],
            'slot_size' => $info['slot_size'] ?? 0
        ];
    }

    public function getFragmentationInfo(): array
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return [
                'fragments' => 0,
                'fragment_size' => 0,
                'fragment_percent' => 0,
            ];
        }
        $smaInfo = apcu_sma_info();
        $totalMemory = $smaInfo['num_seg'] * $smaInfo['seg_size'];
        $freeMemory = $smaInfo['avail_mem'];

        // Fragmentation is the percentage of free memory that is not in a single block
        $fragmentation = 0;
        if ($freeMemory > 0) {
            $largestBlock = 0;
            if (isset($smaInfo['block_lists'][0])) {
                foreach ($smaInfo['block_lists'][0] as $block) {
                    if ($block['size'] > $largestBlock) {
                        $largestBlock = $block['size'];
                    }
                }
            }
            $fragmentation = (1 - ($largestBlock / $freeMemory)) * 100;
        }

        return [
            'fragments' => $smaInfo['num_frees'] ?? 0,
            'fragment_size' => $freeMemory - ($largestBlock ?? 0),
            'fragment_percent' => $fragmentation,
        ];
    }

    public function getGeneralInfo(): array
    {
        return [
            'app_version' => '1.0.0',
            'host' => $_SERVER['HTTP_HOST'] ?? php_uname('n'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
        ];
    }

    public function getSlotsInfo(): array
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return [
                'num_slots' => 0,
                'slot_size' => 0,
            ];
        }
        $cacheInfo = apcu_cache_info();
        return [
            'num_slots' => $cacheInfo['num_slots'],
            'slot_size' => $cacheInfo['slot_size'] ?? 0,
        ];
    }

    public function recordSnapshot(): void
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return;
        }

        $info = apcu_cache_info();
        $smaInfo = apcu_sma_info();

        $snapshot = [
            'time' => time(),
            'memory_used' => (int) ($info['mem_size'] ?? 0),
            'memory_total' => (int) ($smaInfo['num_seg'] * $smaInfo['seg_size']),
            'entries' => (int) ($info['num_entries'] ?? 0),
            'hits' => (int) ($info['num_hits'] ?? 0),
            'misses' => (int) ($info['num_misses'] ?? 0),
            'hit_rate' => ((int) ($info['num_hits'] ?? 0) + (int) ($info['num_misses'] ?? 0)) > 0
                ? round(((int) ($info['num_hits'] ?? 0) / ((int) ($info['num_hits'] ?? 0) + (int) ($info['num_misses'] ?? 0))) * 100, 1)
                : 0,
        ];

        $trends = apcu_fetch('cache_trend_data');
        if ($trends === false) {
            $trends = [];
        }

        $trends[] = $snapshot;

        // Keep only last 60 snapshots
        if (count($trends) > 60) {
            $trends = array_slice($trends, -60);
        }

        apcu_store('cache_trend_data', $trends, 86400); // 24h TTL
    }

    public function getTrendData(): array
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return [];
        }

        $data = apcu_fetch('cache_trend_data');
        return $data !== false ? $data : [];
    }

    public function getKeyBreakdown(): array
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return [
                'total' => 0,
                'active' => 0,
                'expired' => 0,
                'no_ttl' => 0,
                'short_ttl' => 0,
                'medium_ttl' => 0,
                'long_ttl' => 0,
            ];
        }

        $cacheInfo = apcu_cache_info();
        $cacheList = $cacheInfo['cache_list'] ?? [];

        $total = count($cacheList);
        $active = 0;
        $expired = 0;
        $noTtl = 0;
        $shortTtl = 0;
        $mediumTtl = 0;
        $longTtl = 0;

        foreach ($cacheList as $entry) {
            $ttl = (int) ($entry['ttl'] ?? 0);
            $mtime = (int) ($entry['mtime'] ?? 0);
            $expiresAt = $mtime + $ttl;

            if ($ttl === 0) {
                $noTtl++;
            } elseif ($ttl <= 60) {
                $shortTtl++;
            } elseif ($ttl <= 3600) {
                $mediumTtl++;
            } else {
                $longTtl++;
            }

            if ($ttl === 0 || $expiresAt > time()) {
                $active++;
            } else {
                $expired++;
            }
        }

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'no_ttl' => $noTtl,
            'short_ttl' => $shortTtl,
            'medium_ttl' => $mediumTtl,
            'long_ttl' => $longTtl,
        ];
    }

    public function getDetailedStats(): array
    {
        $this->recordSnapshot();
        return [
            'memory' => $this->getMemoryInfo(),
            'cache' => $this->getCacheInfo(),
            'fragmentation' => $this->getFragmentationInfo(),
            'general' => $this->getGeneralInfo(),
            'slots' => $this->getSlotsInfo(),
            'key_breakdown' => $this->getKeyBreakdown(),
            'trends' => $this->getTrendData(),
        ];
    }
}
