<?php

use Digipeyk\PaymentClient\Exceptions\PaymentException;
use Digipeyk\PaymentClient\PaymentClient;

class WalletTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentClient
     */
    private $client;

    public function setUp()
    {
        $this->client = PaymentClient::create(getenv('SHOP_NAME'), getenv('SERVER_URL'), getenv('OAUTH_TOKEN'));
        parent::setUp();
    }

    public function test_create_a_wallet()
    {
        $userId = 'rand:' . uniqid();
        $wallet = $this->client->createWallet($userId);
        $this->assertEquals(0, $wallet->credit);
        try {
            $this->client->createWallet($userId);
            $this->assertFalse(true);
        } catch (PaymentException $e) {
            $this->assertEquals(400, $e->getCode());
        }

        $transaction = $this->client->chargeWallet($wallet->id, 1000, 'test1:'.$userId, '...');
        $this->assertEquals($transaction->id,
            $this->client->chargeOrGetTransaction($wallet->id, 1000, 'test1:'.$userId, '...')->id
        );
        $discharge = $this->client->chargeWallet($wallet->id, -600, 'test1:2:'.$userId, null);
        $this->assertEquals(400, $this->client->getWallet($wallet->id)->credit);
        $reverse = $this->client->revertTransaction($discharge->id, 'oops');
        $this->assertEquals(600, $reverse->amount);
        $freshWallet = $this->client->getWallet($wallet->id);
        $this->assertEquals(1000, $freshWallet->credit);
        $this->assertEquals(1000, $freshWallet->sum_charges);
    }
}
