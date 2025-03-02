<?php

namespace Firesphere\GraphQLJWT\Tests;

use Exception;
use Firesphere\GraphQLJWT\Authentication\JWTAuthenticationHandler;
use Firesphere\GraphQLJWT\Mutations\CreateTokenMutationCreator;
use Firesphere\GraphQLJWT\Resolvers\Resolver;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;

class JWTAuthenticationHandlerTest extends SapphireTest
{
    protected static $fixture_file = '../fixtures/JWTAuthenticatorTest.yml';

    protected $member;

    protected $token;

    public function setUp()
    {
        Environment::putEnv('JWT_SIGNER_KEY=test_signer');

        parent::setUp();
        $this->member = $this->objFromFixture(Member::class, 'admin');
        $response = Resolver::resolveCreateToken(
            null,
            ['email' => 'admin@silverstripe.com', 'password' => 'error'],
        );

        $this->token = $response['token'];
    }

    /**
     * @throws Exception
     */
    public function testInvalidAuthenticateRequest()
    {
        Environment::putEnv('JWT_SIGNER_KEY=string');

        $request = clone Controller::curr()->getRequest();
        $request->addHeader('Authorization', 'Bearer ' . $this->token);

        $handler = JWTAuthenticationHandler::singleton();

        $result = $handler->authenticateRequest($request);
        Environment::putEnv('JWT_SIGNER_KEY=test_signer');

        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    public function testAuthenticateRequest()
    {
        $request = clone Controller::curr()->getRequest();
        $request->addHeader('Authorization', 'Bearer ' . $this->token);

        $handler = JWTAuthenticationHandler::singleton();

        $result = $handler->authenticateRequest($request);
        $this->assertInstanceOf(Member::class, $result);
        $this->assertTrue($result->isInDB());
    }
}
