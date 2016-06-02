<?php

namespace Framgia\Jwt;

use Illuminate\Auth\AuthManager;
use Framgia\Jwt\Storage\CacheStorage;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Framgia\Jwt\Signers\Factory as SignerFactory;
use Illuminate\Contracts\Cache\Repository as Cache;
use Framgia\Jwt\Contracts\Signer as SignerContract;
use Framgia\Jwt\Contracts\Storage as StorageContract;

class JwtServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/jwt.php' => config_path('api.php')
        ]);

        $this->mergeConfigFrom(__DIR__ . '/../config/jwt.php', 'jwt');

        $manager = $this->app[AuthManager::class];

        $manager->extend('jwt', function ($app, $name, $config) use ($manager) {
            $guard = new Guard($manager->createUserProvider($config['provider']), $app[Request::class], $app[Blacklist::class], $app[SignerContract::class]);

            $app->refresh(Request::class, $guard, 'setRequest');

            return $guard;
        });
    }

    /**
     * Register services
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(StorageManager::class, function ($app) {
            return new StorageManager($app);
        });

        $this->app->singleton(Blacklist::class, function ($app) {
            return new Blacklist($app[StorageContract::class]);
        });

        $this->app->singleton(SignerFactory::class, function ($app) {
            return new SignerFactory($app);
        });

        $this->app->bind(SignerContract::class, function ($app) {
            return $app[SignerFactory::class]->driver();
        });

        $this->app->bind(StorageContract::class, function ($app) {
            return $app[StorageManager::class]->driver();
        });
    }
}
