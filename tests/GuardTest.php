<?php

use Mockery as m;

class GuardTest extends PHPUnit_Framework_TestCase
{
    public function testSetRequest()
    {
        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = m::mock(Framgia\Jwt\Contracts\Signer::class);

        $firstRequest = Illuminate\Http\Request::create('/');

        $guard = new Framgia\Jwt\Guard($provider, $firstRequest, $blacklist, $signer);

        $reflection = new ReflectionClass($guard);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);

        $this->assertEquals($firstRequest, $property->getValue($guard));

        $newRequest = Illuminate\Http\Request::create('/foo');

        $guard->setRequest($newRequest);

        $this->assertEquals($newRequest, $property->getValue($guard));
    }

    public function testUserWithRawToken()
    {
        $user = m::mock(\Illuminate\Contracts\Auth\Authenticatable::class);

        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $provider->shouldReceive('retrieveById')->once()->andReturn($user);

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = m::mock(Framgia\Jwt\Contracts\Signer::class);

        $request = Illuminate\Http\Request::create('/');

        $token = (new \Lcobucci\JWT\Builder())->setSubject(1)->setExpiration(time() + 3600)->getToken();

        $signer->shouldReceive('verify')->once()->andReturn(true);

        $request->headers->set('Authorization', 'Bearer '.$token);

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $this->assertEquals($user, $guard->user());
    }

    public function testPassingClaimsToProvider()
    {
        $user = m::mock(\Illuminate\Contracts\Auth\Authenticatable::class);

        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class, Framgia\Jwt\Contracts\ChecksClaims::class);
        $provider->shouldNotReceive('retrieveById');

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = m::mock(Framgia\Jwt\Contracts\Signer::class);

        $request = Illuminate\Http\Request::create('/');

        $token = (new \Lcobucci\JWT\Builder())
            ->setSubject(1)
            ->setExpiration(time() + 3600)
            ->getToken();

        $provider->shouldReceive('retrieveByClaims')->once()->with($token->getClaims())->andReturn($user);

        $signer->shouldReceive('verify')->once()->andReturn(true);

        $request->headers->set('Authorization', 'Bearer '.$token);

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $this->assertEquals($user, $guard->user());
    }

    public function testUserWithSignedToken()
    {
        $user = m::mock(\Illuminate\Contracts\Auth\Authenticatable::class);

        $signer = new Framgia\Jwt\Signers\Hmac('test');

        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $provider->shouldReceive('retrieveById')->once()->andReturn($user);

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);

        $request = Illuminate\Http\Request::create('/');

        $token = $signer->sign((new \Lcobucci\JWT\Builder())->setSubject(1)->setExpiration(time() + 3600))->getToken();

        $request->headers->set('Authorization', 'Bearer '.$token);

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $this->assertEquals($user, $guard->user());

        // Additional check to test if user provider is called once
        $this->assertEquals($user, $guard->user());
    }

    public function testValidate()
    {
        $credentials = ['foo' => 'bar'];

        $user = m::mock(\Illuminate\Contracts\Auth\Authenticatable::class);
        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with($credentials)->andReturn($user);
        $provider->shouldReceive('validateCredentials')->once()->with($user, $credentials)->andReturn(true);

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = m::mock(Framgia\Jwt\Contracts\Signer::class);

        $request = Illuminate\Http\Request::create('/');

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $this->assertTrue($guard->validate($credentials));
    }

    public function testValidateFailsWithFakeCredentials()
    {
        $credentials = ['foo' => 'bar'];

        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with($credentials)->andReturnNull();

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = m::mock(Framgia\Jwt\Contracts\Signer::class);

        $request = Illuminate\Http\Request::create('/');

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $this->assertFalse($guard->validate($credentials));
    }

    public function testAttempt()
    {
        $credentials = ['foo' => 'bar'];

        $user = m::mock(\Illuminate\Contracts\Auth\Authenticatable::class);
        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with($credentials)->andReturn($user);
        $provider->shouldReceive('validateCredentials')->once()->with($user, $credentials)->andReturn(true);

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = m::mock(Framgia\Jwt\Contracts\Signer::class);

        $request = Illuminate\Http\Request::create('/');

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $this->assertTrue($guard->validate($credentials));
    }

    public function testUserReturnsNullWithoutToken()
    {
        $signer = m::mock(Framgia\Jwt\Signers\Hmac::class);

        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $provider->shouldNotReceive('retrieveById');

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);

        $request = Illuminate\Http\Request::create('/');

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $this->assertNull($guard->user());
    }

    public function testUserReturnsNullWithInvalidToken()
    {
        $credentials = ['foo' => 'bar'];

        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $provider->shouldNotReceive('retrieveById');

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = m::mock(Framgia\Jwt\Contracts\Signer::class);

        $request = Illuminate\Http\Request::create('/');

        $request->headers->set('Authorization', 'Bearer BAD_TOKEN');

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $this->assertNull($guard->user());
    }

    public function testAttemptReturnsToken()
    {
        $credentials = ['foo' => 'bar'];

        $user = m::mock(\Illuminate\Contracts\Auth\Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->once()->andReturn(1);

        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with($credentials)->andReturn($user);
        $provider->shouldReceive('validateCredentials')->once()->with($user, $credentials)->andReturn(true);

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = new Framgia\Jwt\Signers\Hmac('test');

        $request = Illuminate\Http\Request::create('/');

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        /**
         * @var \Lcobucci\JWT\Token
         */
        $token = $guard->attempt($credentials);

        $this->assertInstanceOf(\Lcobucci\JWT\Token::class, $token);
        $this->assertEquals(1, $token->getClaim('sub'));
        $this->assertLessThan($token->getClaim('exp'), time());
        $this->assertNotNull($token->getClaim('jti'));

        $this->assertTrue($guard->validate($credentials));
    }

    public function testRefresh()
    {
        $token = (new \Lcobucci\JWT\Builder())
            ->setId(123)
            ->setSubject(1)
            ->setExpiration($time = time() + 3600)
            ->set('foo', 'bar')
            ->getToken();

        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = new Framgia\Jwt\Signers\Hmac('test');

        $request = Illuminate\Http\Request::create('/');

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $newToken = $guard->refresh($token);

        $this->assertEquals(123, $newToken->getClaim('jti'));
        $this->assertEquals(1, $newToken->getClaim('sub'));
        $this->assertEquals('bar', $newToken->getClaim('foo'));
        $this->assertNotEquals($time, $newToken->getClaim('exp'));
    }

    public function testAttemptWithAdditionalCredentials()
    {
        $credentials = ['foo' => 'bar'];

        $user = m::mock(UserWithCredentials::class);
        $user->shouldReceive('getAuthIdentifier')->once()->andReturn(1);
        $user->shouldReceive('getCredentials')->once()->andReturn([
            'admin' => true,
            'foo' => 'bar',
            'sub' => 10,
        ]);

        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with($credentials)->andReturn($user);
        $provider->shouldReceive('validateCredentials')->once()->with($user, $credentials)->andReturn(true);

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = new Framgia\Jwt\Signers\Hmac('test');

        $request = Illuminate\Http\Request::create('/');

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $token = $guard->attempt($credentials);

        $this->assertInstanceOf(\Lcobucci\JWT\Token::class, $token);
        $this->assertEquals(true, $token->getClaim('admin'));
        $this->assertEquals('bar', $token->getClaim('foo'));
        // Subject must be protected.
        $this->assertEquals(1, $token->getClaim('sub'));

        $this->assertTrue($guard->validate($credentials));
    }

    public function testAttemptNullWithInvalidCredentials()
    {
        $credentials = ['foo' => 'bar'];

        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $provider->shouldReceive('retrieveByCredentials')->once()->with($credentials)->andReturn(null);

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = new Framgia\Jwt\Signers\Hmac('test');

        $request = Illuminate\Http\Request::create('/');

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $this->assertNull($guard->attempt($credentials));
    }

    public function testLogout()
    {
        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $token = (new \Lcobucci\JWT\Builder())->setId('foo')->setSubject(1)->setExpiration(time() + 3600)->getToken();
        $blacklist->shouldReceive('add')->once()->andReturn(true);

        $signer = m::mock(Framgia\Jwt\Contracts\Signer::class);

        $request = Illuminate\Http\Request::create('/');
        $signer->shouldReceive('verify')->once()->andReturn(true);

        $request->headers->set('Authorization', 'Bearer '.$token);

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $this->assertTrue($guard->logout());
    }

    public function testLogoutWithoutToken()
    {
        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $blacklist->shouldNotReceive('add');

        $signer = m::mock(Framgia\Jwt\Contracts\Signer::class);

        $request = Illuminate\Http\Request::create('/');
        $signer->shouldReceive('verify')->once()->andReturn(true);

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $this->assertTrue($guard->logout());
    }

    public function testLogoutWithInvalidToken()
    {
        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $blacklist->shouldNotReceive('add');

        $signer = m::mock(Framgia\Jwt\Contracts\Signer::class);

        $request = Illuminate\Http\Request::create('/');
        $request->headers->set('Authorization', 'Bearer BAD_TOKEN');
        $signer->shouldReceive('verify')->once()->andReturn(true);

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $this->assertTrue($guard->logout());
    }

    public function testSetToken()
    {
        $user = m::mock(\Illuminate\Contracts\Auth\Authenticatable::class);

        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $provider->shouldReceive('retrieveById')->once()->andReturn($user);

        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = m::mock(Framgia\Jwt\Contracts\Signer::class);

        $request = Illuminate\Http\Request::create('/');

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $reflection = new ReflectionClass($guard);
        $property = $reflection->getProperty('token');
        $property->setAccessible(true);

        $token = (new \Lcobucci\JWT\Builder())->setSubject(1)->setExpiration(time() + 3600)->getToken();

        $this->assertInstanceOf(\Framgia\Jwt\Guard::class, $guard->setToken($token));
        $this->assertEquals($token, $property->getValue($guard));

        $guard->user();
    }

    public function testSetUser()
    {
        $user = m::mock(\Illuminate\Contracts\Auth\Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->once()->andReturn(1);

        $provider = m::mock(Illuminate\Contracts\Auth\UserProvider::class);
        $blacklist = m::mock(Framgia\Jwt\Blacklist::class);
        $signer = new Framgia\Jwt\Signers\Hmac('test');

        $request = Illuminate\Http\Request::create('/');

        $guard = new Framgia\Jwt\Guard($provider, $request, $blacklist, $signer);

        $reflection = new ReflectionClass($guard);
        $property = $reflection->getProperty('user');
        $property->setAccessible(true);

        $this->assertInstanceOf(\Framgia\Jwt\Guard::class, $guard->setUser($user));
        $token = $guard->token();
        $this->assertInstanceOf(\Lcobucci\JWT\Token::class, $token);
        $this->assertEquals(1, $token->getClaim('sub'));
    }
}

abstract class UserWithCredentials implements \Illuminate\Contracts\Auth\Authenticatable, \Framgia\Jwt\Contracts\ProvidesCredentials {}
