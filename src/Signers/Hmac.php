<?php

namespace Framgia\Jwt\Signers;

class Hmac extends BaseSigner
{
    /**
     * @param  string  $key
     * @param  string  $algorithm
     */
    public function __construct($key, $algorithm = 'sha256')
    {
        $this->key = $key;
        $this->signer = $this->createSigner($algorithm);
    }
}
