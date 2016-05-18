<?php

namespace Framgia\Jwt\Signers;

use Illuminate\Support\Manager;

class Factory extends Manager
{
    public function getDefaultDriver()
    {
        return $this->app['config']['jwt.signer.default'];
    }

    protected function createHmacDriver()
    {
        $key = $this->app['config']['jwt.secret'];
        $algorithm = $this->app['config']['jwt.signer.hmac.algorithm'];

        return new Hmac($key, $algorithm);
    }
}
