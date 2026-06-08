<?php

declare(strict_types=1);

namespace App\Services;

class OpcacheService
{
    public function isEnabled(): bool
    {
        return function_exists('opcache_get_status') && opcache_get_status() !== false;
    }

    public function getStatus(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }
        return opcache_get_status();
    }

    public function getConfiguration(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }
        return opcache_get_configuration();
    }

    public function reset(): bool
    {
        if (!function_exists('opcache_reset')) {
            return false;
        }
        return opcache_reset();
    }

    public function getHumanReadableMemorySize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
