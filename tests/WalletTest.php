<?php

use Digipeyk\PaymentClient\PaymentClient;
use Digipeyk\PaymentClient\PaymentException;

class WalletTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentClient
     */
    private $client;

    public function setUp()
    {
        $this->client = PaymentClient::create('digizar', 'http://localhost:8001/');
        parent::setUp();
    }

    public function test_create_a_wallet()
    {
        $userId = 'rand:' . uniqid();
        $wallet = $this->client->createWallet($userId);
        $this->assertEquals(0, $wallet->credit);
        try {
            $this->client->createWallet($userId);
            $this->assertFalse(ture);
        } catch (PaymentException $e) {
            $this->assertEquals(400, $e->getCode());
        }
    }
}
