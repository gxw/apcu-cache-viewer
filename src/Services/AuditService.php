<?php

declare(strict_types=1);

namespace App\Services;

class AuditService
{
    private const STORAGE_KEY = 'audit_log';
    private const MAX_ENTRIES = 100;

    public function log(string $action, string $key = '', string $details = ''): void
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return;
        }

        $log = apcu_fetch(self::STORAGE_KEY);
        if ($log === false) {
            $log = [];
        }

        $log[] = [
            'time' => time(),
            'action' => $action,
            'key' => $key,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ];

        // Keep only last MAX_ENTRIES
        if (count($log) > self::MAX_ENTRIES) {
            $log = array_slice($log, -self::MAX_ENTRIES);
        }

        apcu_store(self::STORAGE_KEY, $log, 86400 * 7); // 7-day TTL on the log itself
    }

    public function getLogs(): array
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return [];
        }

        $log = apcu_fetch(self::STORAGE_KEY);
        return $log !== false ? array_reverse($log) : [];
    }

    public function clear(): void
    {
        if (function_exists('apcu_enabled') && apcu_enabled()) {
            apcu_delete(self::STORAGE_KEY);
        }
    }
}
