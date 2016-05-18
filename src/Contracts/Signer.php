<?php

namespace Framgia\Jwt\Contracts;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Token;

interface Signer
{
    /**
     * @param  \Lcobucci\JWT\Token  $token
     * @return bool
     */
    public function verify(Token $token);

    /**
     * @param  \Lcobucci\JWT\Builder  $builder
     * @return \Lcobucci\JWT\Builder
     */
    public function sign(Builder $builder);

    /**
     * @return \Lcobucci\JWT\Signer
     */
    public function signer();
}
