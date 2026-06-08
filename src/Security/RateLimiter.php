<?php

declare(strict_types=1);

namespace App\Security;

class RateLimiter
{
    private int $maxRequests;
    private int $windowSeconds;

    /**
     * @param int $maxRequests  Maximum requests allowed within the time window
     * @param int $windowSeconds Time window in seconds
     */
    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    /**
     * Check if a request from the given identifier is allowed.
     * Returns true if allowed, false if rate-limited.
     */
    public function isAllowed(string $identifier): bool
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return true;
        }

        $key = 'rate_limit:' . $identifier;
        $data = apcu_fetch($key);
        $now = time();

        if ($data === false) {
            apcu_store($key, ['count' => 1, 'start' => $now], $this->windowSeconds);
            return true;
        }

        if ($now - $data['start'] > $this->windowSeconds) {
            apcu_store($key, ['count' => 1, 'start' => $now], $this->windowSeconds);
            return true;
        }

        $data['count']++;
        apcu_store($key, $data, $this->windowSeconds - ($now - $data['start']));

        return $data['count'] <= $this->maxRequests;
    }

    /**
     * Get the number of remaining requests for the current window.
     */
    public function getRemaining(string $identifier): int
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return $this->maxRequests;
        }

        $data = apcu_fetch('rate_limit:' . $identifier);
        if ($data === false) {
            return $this->maxRequests;
        }

        $remaining = $this->maxRequests - $data['count'];
        return max(0, $remaining);
    }

    /**
     * Get the time in seconds until the rate limit window resets.
     */
    public function getResetTime(string $identifier): int
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return 0;
        }

        $data = apcu_fetch('rate_limit:' . $identifier);
        if ($data === false) {
            return 0;
        }

        $elapsed = time() - $data['start'];
        return max(0, $this->windowSeconds - $elapsed);
    }
}
