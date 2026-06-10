<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Redis;

use Redis;

class RedisCache
{
    public function __construct(private readonly Redis $redis)
    {
    }

    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);

        if ($value === false) {
            return null;
        }

        return \unserialize($value);
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $serialized = \serialize($value);

        if ($ttl > 0) {
            return $this->redis->setex($key, $ttl, $serialized);
        }

        return $this->redis->set($key, $serialized);
    }

    public function has(string $key): bool
    {
        return $this->redis->exists($key) > 0;
    }

    public function delete(string $key): bool
    {
        return $this->redis->del($key) > 0;
    }

    public function expire(string $key, int $ttl): bool
    {
        return $this->redis->expire($key, $ttl);
    }

    public function ttl(string $key): int
    {
        $ttl = $this->redis->ttl($key);

        return $ttl !== false ? $ttl : -2;
    }

    public function flush(): bool
    {
        return $this->redis->flushDB();
    }

    public function incr(string $key): int
    {
        return $this->redis->incr($key);
    }

    public function getRaw(string $key): mixed
    {
        return $this->redis->get($key);
    }

    public function setRaw(string $key, string $value, int $ttl = 0): bool
    {
        if ($ttl > 0) {
            return $this->redis->setex($key, $ttl, $value);
        }

        return $this->redis->set($key, $value);
    }

    /**
     * Add one or more members to a set.
     */
    public function sAdd(string $key, mixed ...$members): int
    {
        return $this->redis->sAdd($key, ...$members);
    }

    /**
     * Get all the members in a set.
     */
    public function sMembers(string $key): array
    {
        return $this->redis->sMembers($key);
    }

    /**
     * Remove one or more members from a set.
     */
    public function sRem(string $key, mixed ...$members): int
    {
        return $this->redis->sRem($key, ...$members);
    }

    /**
     * Deletes all keys matching a given pattern.
     * WARNING: Using KEYS can be slow on large databases, consider alternative strategies
     * like tracking keys in a SET or using SCAN in production environments.
     */
    public function deleteByPattern(string $pattern): int
    {
        $keys = $this->redis->keys($pattern);
        if (empty($keys)) {
            return 0;
        }

        return $this->redis->del($keys);
    }
}
