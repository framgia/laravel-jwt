<?php

use Mockery as m;

if (!function_exists('env')) {
    function env($name, $default = null) {
        return $default;
    }
}

if (!function_exists('config_path')) {
    function config_path($path) {
        return $path;
    }
}

class ServiceProviderTokensTest extends PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $app = $this->mockApplication();

        $provider = new \Framgia\Jwt\JwtServiceProvider($app);
        $provider->register();

        $this->assertInstanceOf(\Framgia\Jwt\Storage\CacheStorage::class, $app->make(\Framgia\Jwt\Storage\CacheStorage::class));
        $this->assertInstanceOf(\Framgia\Jwt\Contracts\Storage::class, $app->make(\Framgia\Jwt\Contracts\Storage::class));
        $this->assertInstanceOf(\Framgia\Jwt\Blacklist::class, $app->make(\Framgia\Jwt\Blacklist::class));
        $this->assertInstanceOf(\Framgia\Jwt\Signers\Factory::class, $app->make(\Framgia\Jwt\Signers\Factory::class));
        $this->assertInstanceOf(\Framgia\Jwt\Contracts\Signer::class, $app->make(\Framgia\Jwt\Contracts\Signer::class));
    }

    public function testBoot()
    {
        $app = $this->mockApplication();

        $provider = new \Framgia\Jwt\JwtServiceProvider($app);
        $provider->register();

        $auth = m::mock(\Illuminate\Auth\AuthManager::class);
        $auth->shouldReceive('extend')->once();

        $app->singleton(\Illuminate\Auth\AuthManager::class, function () use ($auth) {
            return $auth;
        });

        $provider->boot();
    }

    protected function mockApplication()
    {
        $app = m::mock(ApplicationStub::class)->makePartial();

        $app->singleton('config', function() {
            $repo = new \Illuminate\Config\Repository();
            $repo->set('jwt', include __DIR__.'/../config/jwt.php');
            return $repo;
        });

        $app->singleton(\Illuminate\Contracts\Cache\Repository::class, function() {
            return m::mock(\Illuminate\Contracts\Cache\Repository::class);
        });

        return $app;
    }
}

abstract class ApplicationStub extends \Illuminate\Container\Container implements \Illuminate\Contracts\Foundation\Application
{
}
