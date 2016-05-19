<?php

namespace Framgia\Jwt;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Builder;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Framgia\Jwt\Contracts\Signer;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\UserProvider;
use Framgia\Jwt\Contracts\ProvidesCredentials;
use Illuminate\Contracts\Auth\Guard as GuardContract;
use Lcobucci\JWT\Token;

class Guard implements GuardContract
{
    use GuardHelpers;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Framgia\Jwt\Blacklist
     */
    protected $blacklist;

    /**
     * @var \Framgia\Jwt\Contracts\Signer
     */
    protected $signer;

    /**
     * @var \Lcobucci\JWT\Token
     */
    protected $token;

    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Http\Request  $request
     * @param  \Framgia\Jwt\Blacklist  $blacklist
     * @param  \Framgia\Jwt\Contracts\Signer  $signer
     */
    public function __construct(
        UserProvider $provider,
        Request $request,
        Blacklist $blacklist,
        Signer $signer
    )
    {
        $this->request = $request;
        $this->provider = $provider;
        $this->blacklist = $blacklist;
        $this->signer = $signer;
    }

    public function token()
    {
        if (empty($this->token)) {
            $this->token = $this->getTokenForRequest();
        }

        return $this->token;
    }

    public function setToken(Token $token)
    {
        $this->token = $token;
        $this->user = null;

        return $this;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (! is_null($this->user)) {
            return $this->user;
        }

        $user = null;

        $token = $this->token();

        if (! is_null($token)) {
            $user = $this->provider->retrieveById($token->getClaim('sub'));
        }

        return $this->user = $user;
    }

    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
        $this->token = $this->createTokenForUser($this->user);

        return $this;
    }

    /**
     * Get the token for the current request.
     *
     * @return \Lcobucci\JWT\Token
     */
    protected function getTokenForRequest()
    {
        $token = $this->request->bearerToken();

        if (empty($token)) {
            return null;
        }

        try {
            $token = (new Parser())->parse($token);

            if (!$this->signer->verify($token)) {
                return null;
            }
        } catch (InvalidArgumentException $e) {
            return null;
        }

        return $token;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $user = $this->provider->retrieveByCredentials($credentials);
        if (!is_null($user) && $this->provider->validateCredentials($user, $credentials)) {
            $this->user = $user;
            return true;
        }

        return false;
    }

    /**
     * @param array $credentials
     * @return \Lcobucci\JWT\Token|null
     */
    public function attempt(array $credentials)
    {
        if (!$this->validate($credentials)) {
            return null;
        }

        return $this->token = $this->createTokenForUser($this->user);
    }

    /**
     * @param  Authenticatable  $user
     * @return Token
     */
    public function createTokenForUser(Authenticatable $user)
    {
        $builder = new Builder();

        $id = $user->getAuthIdentifier();
        $builder->setSubject($id);

        if ($user instanceof ProvidesCredentials) {
            foreach($user->getCredentials() as $key => $value) {
                $builder->set($key, $value);
            }
        }

        $builder->setExpiration(Carbon::now()->addDay()->timestamp);

        $builder->setId(Str::random());

        return $this->signer->sign($builder)->getToken();
    }

    /**
     * @return bool
     */
    public function logout()
    {
        $token = $this->getTokenForRequest();

        if (empty($token)) {
            $result = true;
        } else {
            $result = $this->blacklist->add($token);
        }

        if ($result) {
            $this->token = null;
            $this->user = null;
        }

        return $result;
    }

    /**
     * Set the current request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
