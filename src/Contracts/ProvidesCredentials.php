<?php

namespace Framgia\Jwt\Contracts;

interface ProvidesCredentials
{
    /**
     * Get credentials for JWT.
     *
     * @return array
     */
    public function getCredentials();
}
