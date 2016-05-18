<?php

namespace Framgia\Jwt;

use Lcobucci\JWT\Signer;
use Illuminate\Support\Str;
use InvalidArgumentException;

trait SigningTokens
{
    protected function createSigner($algorithm = 'sha256', $type = null)
    {
        if (is_null($type)) {
            $type = class_basename($this);
        } else {
            $type = Str::studly($type);
        }

        $algorithm = Str::studly($algorithm);

        // Check if provided class exists and extends BaseVerifier
        // to avoid possible code injections
        if (is_subclass_of($class = Signer::class.'\\'.$type.'\\'.$algorithm, Signer\BaseSigner::class)) {
            return new $class;
        }

        throw new InvalidArgumentException('['.$algorithm.'] is not supported in ['.$type.'] verifier.');
    }
}
