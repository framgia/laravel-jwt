<?php

namespace Framgia\Jwt\Contracts;

interface Storage
{
    /**
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $minutes
     * @return void
     */
    public function add($key, $value, $minutes);

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function forever($key, $value);

    /**
     * @param  string  $key
     * @return bool
     */
    public function has($key);

    /**
     * @param  string  $key
     * @return bool
     */
    public function destroy($key);

    /**
     * @return void
     */
    public function flush();
}
