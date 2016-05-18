<?php

use Mockery as m;

class BlacklistTest extends PHPUnit_Framework_TestCase
{
    public function testGetKey()
    {
        $id = uniqid();
        $exp = time() + 3600;
        $claims = [
            'foo' => 'bar',
        ];

        $storage = m::mock(\Framgia\Jwt\Contracts\Storage::class);

        $list = new \Framgia\Jwt\Blacklist($storage);

        $token = $this->tokenStub($id, $exp, $claims);

        $this->assertEquals($id, $list->getKey($token));

        $list->setKey('foo');

        $this->assertEquals('bar', $list->getKey($token));
    }

    public function testAdd()
    {
        $id = uniqid();
        $exp = time() + 3600;

        $storage = m::mock(\Framgia\Jwt\Contracts\Storage::class);

        $list = new \Framgia\Jwt\Blacklist($storage);

        $storage->shouldReceive('add')->once()->with($id, m::any(), m::any());

        $list->add($this->tokenStub($id, $exp));
    }

    public function testAddForever()
    {
        $id = uniqid();

        $storage = m::mock(\Framgia\Jwt\Contracts\Storage::class);

        $list = new \Framgia\Jwt\Blacklist($storage);

        $storage->shouldReceive('forever')->once()->with($id, 'forever');

        $list->add($this->tokenStub($id));
    }

    public function testHas()
    {
        $id = uniqid();
        $exp = time() - 3600;

        $storage = m::mock(\Framgia\Jwt\Contracts\Storage::class);

        $list = new \Framgia\Jwt\Blacklist($storage);

        $storage->shouldReceive('get')->once()->with($id)->andReturn([
            'valid_until' => $exp,
        ]);

        $this->assertTrue($list->has($this->tokenStub($id, $exp)));
    }

    public function testHasNonExisting()
    {
        $id = uniqid();
        $exp = time() + 3600;

        $storage = m::mock(\Framgia\Jwt\Contracts\Storage::class);

        $list = new \Framgia\Jwt\Blacklist($storage);

        $storage->shouldReceive('get')->once()->with($id)->andReturn(null);

        $this->assertFalse($list->has($this->tokenStub($id, $exp)));
    }

    public function testHasFuture()
    {
        $id = uniqid();
        $exp = time() + 3600;

        $storage = m::mock(\Framgia\Jwt\Contracts\Storage::class);

        $list = new \Framgia\Jwt\Blacklist($storage);

        $storage->shouldReceive('get')->once()->with($id)->andReturn([
            'valid_until' => $exp,
        ]);

        $this->assertFalse($list->has($this->tokenStub($id, $exp)));
    }

    public function testHasForever()
    {
        $id = uniqid();

        $storage = m::mock(\Framgia\Jwt\Contracts\Storage::class);

        $list = new \Framgia\Jwt\Blacklist($storage);

        $storage->shouldReceive('get')->once()->with($id)->andReturn('forever');

        $this->assertTrue($list->has($this->tokenStub($id)));
    }

    public function testRemove()
    {
        $id = uniqid();

        $storage = m::mock(\Framgia\Jwt\Contracts\Storage::class);

        $list = new \Framgia\Jwt\Blacklist($storage);

        $storage->shouldReceive('destroy')->once()->with($id)->andReturn(true);

        $this->assertTrue($list->remove($this->tokenStub($id)));
    }

    public function testClear()
    {
        $storage = m::mock(\Framgia\Jwt\Contracts\Storage::class);

        $list = new \Framgia\Jwt\Blacklist($storage);

        $storage->shouldReceive('flush')->once()->withNoArgs();

        $this->assertTrue($list->clear());
    }

    public function testSetRefreshTTL()
    {
        $storage = m::mock(\Framgia\Jwt\Contracts\Storage::class);

        $list = new \Framgia\Jwt\Blacklist($storage);

        $reflection = new ReflectionClass($list);
        $property = $reflection->getProperty('refreshTTL');
        $property->setAccessible(true);

        $this->assertEquals($list, $list->setRefreshTTL(500));
        $this->assertEquals(500, $property->getValue($list));
    }

    public function testSetGracePeriod()
    {
        $storage = m::mock(\Framgia\Jwt\Contracts\Storage::class);

        $list = new \Framgia\Jwt\Blacklist($storage);

        $reflection = new ReflectionClass($list);
        $property = $reflection->getProperty('gracePeriod');
        $property->setAccessible(true);

        $this->assertEquals($list, $list->setGracePeriod(500));
        $this->assertEquals(500, $property->getValue($list));
    }

    protected function tokenStub($id = null, $exp = null, $claims = [])
    {
        $builder = new \Lcobucci\JWT\Builder();

        if ($id) {
            $builder->setId($id);
        }

        if ($exp) {
            $builder->setExpiration($exp);
        }

        foreach ($claims as $key => $value) {
            $builder->set($key, $value);
        }

        return $builder->getToken();
    }
}
