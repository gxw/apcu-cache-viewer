<?php

declare(strict_types=1);

namespace App\Services;

use App\Cache\CacheManager;
use App\Cache\CacheEntry;

class CacheService
{
    private CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    public function getPaginatedCacheEntries(
        int $page = 1,
        string $search = '',
        string $sortField = 'key',
        string $sortOrder = 'asc',
        int $perPage = 20
    ): array {
        return $this->cacheManager->getEntries($page, $search, $sortField, $sortOrder, $perPage);
    }

    public function getCacheStats(): array
    {
        return $this->cacheManager->getDetailedStats();
    }

    public function getDetailedStats(): array
    {
        return $this->cacheManager->getDetailedStats();
    }

    public function deleteCacheEntry(string $key): bool
    {
        return $this->cacheManager->deleteKey($key);
    }

    public function clearCache(bool $skipPinned = false, array $pinnedKeys = []): bool
    {
        return $this->cacheManager->clearCache($skipPinned, $pinnedKeys);
    }

    public function getPinnedKeys(): array
    {
        return $this->cacheManager->getPinnedKeys();
    }

    public function isKeyPinned(string $key): bool
    {
        return $this->cacheManager->isKeyPinned($key);
    }

    public function pinKey(string $key): bool
    {
        return $this->cacheManager->pinKey($key);
    }

    public function unpinKey(string $key): bool
    {
        return $this->cacheManager->unpinKey($key);
    }

    public function storeEntry(string $key, mixed $value, int $ttl = 0): bool
    {
        return $this->cacheManager->storeEntry($key, $value, $ttl);
    }

    public function getCacheEntry(string $key): ?CacheEntry
    {
        return $this->cacheManager->getEntry($key);
    }

    public function updateEntryTtl(string $key, mixed $value, int $ttl): bool
    {
        return $this->cacheManager->storeEntry($key, $value, $ttl);
    }
}
