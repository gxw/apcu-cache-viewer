<?php

declare(strict_types=1);

namespace App\Cache;

class CacheEntry
{
    private string $key;
    private $value;
    private int $hits;
    private int $size;
    private int $created;
    private int $modified;
    private int $ttl;
    private int $expires;

    public function __construct(
        string $key,
        $value,
        int $hits = 0,
        int $size = 0,
        int $created = 0,
        int $modified = 0,
        int $ttl = 0,
        int $expires = 0
    ) {
        $this->key = $key;
        $this->value = $value;
        $this->hits = $hits;
        $this->size = $size;
        $this->created = $created;
        $this->modified = $modified;
        $this->ttl = $ttl;
        $this->expires = $expires;
    }

    // Getters
    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getHits(): int
    {
        return $this->hits;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getModified(): int
    {
        return $this->modified;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getExpires(): int
    {
        return $this->expires;
    }

    public function isExpired(): bool
    {
        return $this->ttl > 0 && time() > $this->expires;
    }
}
