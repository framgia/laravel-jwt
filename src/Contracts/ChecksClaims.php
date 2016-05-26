<?php

namespace Framgia\Jwt\Contracts;

interface ChecksClaims
{
    public function retrieveByClaims(array $claims);
}
