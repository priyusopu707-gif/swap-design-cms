<?php
/**
 * Swap Design - Cache Helper Functions
 *
 * Global helper functions for cache operations.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

/**
 * Get the CacheManager instance.
 *
 * @return CacheManager|null
 */
function cache(): ?CacheManager
{
    if (!class_exists('CacheManager')) {
        return null;
    }
    return CacheManager::getInstance();
}

/**
 * Get a value from cache.
 *
 * @param string $key     Cache key
 * @param mixed  $default Default value
 * @param string $group   Cache group
 * @return mixed
 */
function cacheGet(string $key, $default = null, string $group = 'default')
{
    $cache = cache();
    return $cache ? $cache->get($key, $default, $group) : $default;
}

/**
 * Store a value in cache.
 *
 * @param string $key   Cache key
 * @param mixed  $value Value to cache
 * @param int    $ttl   Time-to-live in seconds
 * @param string $group Cache group
 * @return bool
 */
function cacheSet(string $key, $value, int $ttl = 3600, string $group = 'default'): bool
{
    $cache = cache();
    return $cache ? $cache->set($key, $value, $ttl, $group) : false;
}

/**
 * Delete a cache entry.
 *
 * @param string $key   Cache key
 * @param string $group Cache group
 * @return bool
 */
function cacheDelete(string $key, string $group = 'default'): bool
{
    $cache = cache();
    return $cache ? $cache->delete($key, $group) : false;
}

/**
 * Flush cache group or all cache.
 *
 * @param string|null $group Cache group (null = flush all)
 * @return int Number of files deleted
 */
function cacheFlush(?string $group = null): int
{
    $cache = cache();
    return $cache ? $cache->flush($group) : 0;
}

/**
 * Remember: get from cache or execute callback and store.
 *
 * @param string   $key      Cache key
 * @param callable $callback Function to execute if cache miss
 * @param int      $ttl      Time-to-live in seconds
 * @param string   $group    Cache group
 * @return mixed
 */
function cacheRemember(string $key, callable $callback, int $ttl = 3600, string $group = 'default')
{
    $cache = cache();
    return $cache ? $cache->remember($key, $callback, $ttl, $group) : $callback();
}

/**
 * Invalidate cache for a content type.
 *
 * @param string $type Content type
 * @param int    $id   Content ID
 */
function invalidateCache(string $type, int $id = 0): void
{
    if (class_exists('CacheInvalidator')) {
        CacheInvalidator::invalidateByType($type, $id);
    }
}
