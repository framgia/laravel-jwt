<?php

namespace Framgia\Jwt;

use Carbon\Carbon;
use Lcobucci\JWT\Token;
use Framgia\Jwt\Contracts\Storage;

class Blacklist
{
    /**
     * @var \Framgia\Jwt\Contracts\Storage
     */
    protected $storage;

    /**
     * The grace period when a token is blacklisted. In seconds.
     *
     * @var int
     */
    protected $gracePeriod = 0;

    /**
     * Number of minutes from issue date in which a JWT can be refreshed.
     *
     * @var int
     */
    protected $refreshTTL = 20160;

    /**
     * The unique key held within the blacklist.
     *
     * @var string
     */
    protected $key = 'jti';


    /**
     * @param \Framgia\Jwt\Contracts\Storage $storage
     */
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }


    /**
     * Add the token (jti claim) to the blacklist.
     *
     * @param  \Lcobucci\JWT\Token  $token
     *
     * @return bool
     */
    public function add(Token $token)
    {
        // if there is no exp claim then add the jwt to
        // the blacklist indefinitely
        if (! $token->hasClaim('exp')) {
            return $this->addForever($token);
        }

        $this->storage->add(
            $this->getKey($token),
            ['valid_until' => $this->getGraceTimestamp()],
            $this->getMinutesUntilExpired($token)
        );
        return true;
    }

    /**
     * Get the number of minutes until the token expiry.
     *
     * @param  \Lcobucci\JWT\Token  $token
     *
     * @return int
     */
    protected function getMinutesUntilExpired(Token $token)
    {
        $exp = Carbon::createFromTimestamp($token->getClaim('exp'));
        $iat = Carbon::createFromTimestamp($token->hasClaim('iat') ? $token->getClaim('iat') : 0);
        // get the latter of the two expiration dates and find
        // the number of minutes until the expiration date,
        // plus 1 minute to avoid overlap
        return $exp->max($iat->addMinutes($this->refreshTTL))->addMinute()->diffInMinutes();
    }

    /**
     * Add the token (jti claim) to the blacklist indefinitely.
     *
     * @param  \Lcobucci\JWT\Token  $token
     *
     * @return bool
     */
    public function addForever(Token $token)
    {
        $this->storage->forever($this->getKey($token), 'forever');
        return true;
    }

    /**
     * Determine whether the token has been blacklisted.
     *
     * @param  \Lcobucci\JWT\Token  $token
     *
     * @return bool
     */
    public function has(Token $token)
    {
        $val = $this->storage->get($this->getKey($token));

        // exit early if the token was blacklisted forever
        if ($val === 'forever') {
            return true;
        }

        // check whether the expiry + grace has past
        return $val !== null && ! Carbon::createFromTimestamp($val['valid_until'])->isFuture();
    }

    /**
     * Remove the token (jti claim) from the blacklist.
     *
     * @param  \Lcobucci\JWT\Token  $token
     *
     * @return bool
     */
    public function remove(Token $token)
    {
        return $this->storage->destroy($this->getKey($token));
    }

    /**
     * Remove all tokens from the blacklist.
     *
     * @return bool
     */
    public function clear()
    {
        $this->storage->flush();
        return true;
    }

    /**
     * Get the timestamp when the blacklist comes into effect
     * This defaults to immediate (0 seconds).
     *
     * @return int
     */
    protected function getGraceTimestamp()
    {
        return Carbon::now()->addSeconds($this->gracePeriod)->getTimestamp();
    }

    /**
     * Set the grace period.
     *
     * @param  int  $gracePeriod
     *
     * @return $this
     */
    public function setGracePeriod($gracePeriod)
    {
        $this->gracePeriod = (int) $gracePeriod;
        return $this;
    }

    /**
     * Get the unique key held within the blacklist.
     *
     * @param  \Lcobucci\JWT\Token  $token
     *
     * @return mixed
     */
    public function getKey(Token $token)
    {
        return $token->getClaim($this->key);
    }

    /**
     * Set the unique key held within the blacklist.
     *
     * @param  string  $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = value($key);
        return $this;
    }

    /**
     * Set the refresh time limit.
     *
     * @param  int  $ttl
     *
     * @return $this
     */
    public function setRefreshTTL($ttl)
    {
        $this->refreshTTL = (int) $ttl;
        return $this;
    }
}
