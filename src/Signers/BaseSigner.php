<?php

namespace Framgia\Jwt\Signers;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Token;
use Framgia\Jwt\SigningTokens;
use Framgia\Jwt\Contracts\Signer;

abstract class BaseSigner implements Signer
{
    use SigningTokens;
    /**
     * @var \Lcobucci\JWT\Signer\BaseSigner
     */
    protected $signer;

    /**
     * @var mixed
     */
    protected $key;

    /**
     * @return \Lcobucci\JWT\Signer
     */
    public function signer()
    {
        return $this->signer;
    }

    /**
     * @param  \Lcobucci\JWT\Token  $token
     * @return bool
     */
    public function verify(Token $token)
    {
        return $token->verify($this->signer, $this->key);
    }

    /**
     * @param  \Lcobucci\JWT\Builder  $builder
     * @return \Lcobucci\JWT\Builder
     */
    public function sign(Builder $builder)
    {
        return $builder->sign($this->signer, $this->key);
    }
}
