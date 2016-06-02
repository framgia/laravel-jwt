<?php

namespace Framgia\Jwt;

use Illuminate\Support\Manager;
use Framgia\Jwt\Storage\CacheStorage;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class StorageManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['jwt.storage.driver'];
    }

    /**
     * Create Cache storage driver.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function createCacheDriver()
    {
        return new CacheStorage($this->app[CacheRepository::class], $this->app['config']['jwt.storage.cache.tag']);
    }
}
