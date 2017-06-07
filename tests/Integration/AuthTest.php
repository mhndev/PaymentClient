<?php

use Digipeyk\PaymentClient\Auth\TokenResolverInterface;
use Digipeyk\PaymentClient\PaymentClient;

class AuthTest extends PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_it_refresh_token_on_401()
    {
        $token = Mockery::mock(TokenResolverInterface::class);
        $token->shouldReceive('getToken')->once()->andReturn('');
        $token->shouldReceive('refreshToken')->once();
        $token->shouldReceive('getToken')->once()->andReturn(getenv('OAUTH_TOKEN'));
        $client = PaymentClient::create(getenv('SHOP_NAME'), getenv('SERVER_URL'), $token);
        $transactions = $client->queryTransactions(null, null, null, null, true);
        //echo json_encode($transactions->transactions, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    }
}
