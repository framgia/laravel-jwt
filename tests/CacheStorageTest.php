<?php

use Mockery as m;

class CacheStorageTest extends PHPUnit_Framework_TestCase
{
    public function testBasicStorageUsage()
    {
        $cache = m::mock(\Illuminate\Contracts\Cache\Repository::class);
        $cache->shouldReceive('put')->once()->with('foo', 'bar', 1);
        $cache->shouldReceive('forever')->once()->with('foo', 'bar');
        $cache->shouldReceive('has')->once()->andReturn(true);
        $cache->shouldReceive('forget')->once()->with('foo')->andReturn(true);
        $cache->shouldReceive('flush')->once();

        $storage = new \Framgia\Jwt\Storage\CacheStorage($cache);

        $storage->add('foo', 'bar', 1);
        $storage->forever('foo', 'bar');
        $this->assertTrue($storage->has('foo'));
        $this->assertTrue($storage->destroy('foo'));
        $storage->flush();
    }

    public function testTaggedStorageUsage()
    {
        $tagged = m::mock(Illuminate\Cache\TaggedCache::class);

        $tagged->shouldReceive('setDefaultCacheTime')->once();
        $tagged->shouldReceive('put')->once()->with('foo', 'bar', 1);
        $tagged->shouldReceive('forever')->once()->with('foo', 'bar');
        $tagged->shouldReceive('has')->once()->andReturn(true);
        $tagged->shouldReceive('forget')->once()->with('foo')->andReturn(true);
        $tagged->shouldReceive('flush')->once();

        $store = m::mock(TaggedStoreStub::class);

        $store->shouldReceive('tags')->once()->with('jwt')->andReturn($tagged);

        $cache = new \Illuminate\Cache\Repository($store);

        $storage = new \Framgia\Jwt\Storage\CacheStorage($cache, 'jwt');

        $storage->add('foo', 'bar', 1);
        $storage->forever('foo', 'bar');
        $this->assertTrue($storage->has('foo'));
        $this->assertTrue($storage->destroy('foo'));
        $storage->flush();
    }
}

abstract class TaggedStoreStub extends \Illuminate\Cache\TaggableStore implements \Illuminate\Contracts\Cache\Store {}
