<?php

class SigningTokensTest extends PHPUnit_Framework_TestCase
{
    public function testBaseSignerGetter()
    {
        $signer = new BaseSignerStub();

        $this->assertEquals('foo', $signer->signer());
    }

    public function testCreateSigner()
    {
        $stub = new SigningStub();
        $signer = $stub->signer('sha256', 'hmac');

        $this->assertInstanceOf(\Lcobucci\JWT\Signer\Hmac\Sha256::class, $signer);
    }

    public function testCreateUnexistingSigner()
    {
        $this->setExpectedException(InvalidArgumentException::class, '[Sha256] is not supported in [Unsupported] verifier.');
        $stub = new SigningStub();
        $signer = $stub->signer('sha256', 'unsupported');
    }

    public function testCreateSignerWithAutomaticType()
    {
        $stub = new Rsa();
        $signer = $stub->signer();

        $this->assertInstanceOf(\Lcobucci\JWT\Signer\Rsa\Sha256::class, $signer);
    }
}

class SigningStub
{
    use \Framgia\Jwt\SigningTokens;

    public function signer($algorithm = 'sha256', $type = null)
    {
        // Pass call to protected method
        return $this->createSigner($algorithm, $type);
    }
}

class Rsa extends SigningStub
{

}

class BaseSignerStub extends \Framgia\Jwt\Signers\BaseSigner
{
    protected $signer = 'foo';
}
