<?php

declare(strict_types=1);

namespace Tests\Cache;

use App\Cache\CacheEntry;
use PHPUnit\Framework\TestCase;

class CacheEntryTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $entry = new CacheEntry('test_key', 'hello', 10, 256, 100, 200, 300, 500);

        $this->assertSame('test_key', $entry->getKey());
        $this->assertSame('hello', $entry->getValue());
        $this->assertSame(10, $entry->getHits());
        $this->assertSame(256, $entry->getSize());
        $this->assertSame(100, $entry->getCreated());
        $this->assertSame(200, $entry->getModified());
        $this->assertSame(300, $entry->getTtl());
        $this->assertSame(500, $entry->getExpires());
    }

    public function testDefaultValues(): void
    {
        $entry = new CacheEntry('key', 'value');

        $this->assertSame(0, $entry->getHits());
        $this->assertSame(0, $entry->getSize());
        $this->assertSame(0, $entry->getCreated());
        $this->assertSame(0, $entry->getModified());
        $this->assertSame(0, $entry->getTtl());
        $this->assertSame(0, $entry->getExpires());
    }

    public function testArrayValue(): void
    {
        $data = ['foo' => 'bar', 'num' => 42];
        $entry = new CacheEntry('array_key', $data);

        $this->assertSame($data, $entry->getValue());
    }

    public function testNullValue(): void
    {
        $entry = new CacheEntry('null_key', null);
        $this->assertNull($entry->getValue());
    }

    public function testIsExpiredWithNoTtl(): void
    {
        $entry = new CacheEntry('key', 'val', 0, 0, 0, 0, 0, 0);
        $this->assertFalse($entry->isExpired());
    }

    public function testIsExpiredWithFutureExpiry(): void
    {
        $future = time() + 3600;
        $entry = new CacheEntry('key', 'val', 0, 0, 0, 0, 3600, $future);
        $this->assertFalse($entry->isExpired());
    }

    public function testIsExpiredWithPastExpiry(): void
    {
        $past = time() - 1;
        $entry = new CacheEntry('key', 'val', 0, 0, 0, 0, 1, $past);
        $this->assertTrue($entry->isExpired());
    }

    public function testIntegerValueZero(): void
    {
        $entry = new CacheEntry('zero', 0);
        $this->assertSame(0, $entry->getValue());
    }

    public function testBooleanValueFalse(): void
    {
        $entry = new CacheEntry('false_val', false);
        $this->assertFalse($entry->getValue());
    }
}
