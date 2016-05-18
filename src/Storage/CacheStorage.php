<?php

namespace Framgia\Jwt\Storage;

use Framgia\Jwt\Contracts\Storage;
use Illuminate\Contracts\Cache\Repository;

class CacheStorage implements Storage
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository|\Illuminate\Contracts\Cache\Store
     */
    protected $cache;

    /**
     * @var string
     */
    protected $tag;

    /**
     * @param \Illuminate\Cache\CacheManager  $cache
     * @param string $tag
     */
    public function __construct(Repository $cache, $tag = 'jwt')
    {
        $this->cache = $cache;
        $this->tag = $tag;
    }

    /**
     * Add a new item into storage.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $minutes
     * @return void
     */
    public function add($key, $value, $minutes)
    {
        $this->cache()->put($key, $value, $minutes);
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->cache()->forever($key, $value);
    }

    /**
     * Check whether a key exists in storage.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return $this->cache()->has($key);
    }

    /**
     * Remove an item from storage.
     *
     * @param  string  $key
     * @return bool
     */
    public function destroy($key)
    {
        return $this->cache()->forget($key);
    }

    /**
     * Remove all items associated with the tag.
     *
     * @return void
     */
    public function flush()
    {
        $this->cache()->flush();
    }

    /**
     * Return the cache instance with tags attached.
     *
     * @return \Illuminate\Contracts\Cache\Repository|\Illuminate\Contracts\Cache\Store
     */
    protected function cache()
    {
        if (! method_exists($this->cache, 'tags')) {
            return $this->cache;
        }

        return $this->cache->tags($this->tag);
    }
}
