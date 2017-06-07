<?php

use Digipeyk\PaymentClient\Exceptions\PaymentException;
use Digipeyk\PaymentClient\Objects\TransferAndPayDescriptions;
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
        $this->client->createWallet($userId);

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

    public function test_transfer_and_pay_accept_negative_credit()
    {
        $user1Id = 'rand:'.uniqid('', true);
        $user2Id = 'rand:'.uniqid('', true);
        $wallet1 = $this->client->createWallet($user1Id);
        $wallet2 = $this->client->createWallet($user2Id);
        $transaction = $this->client->transferAndPay($wallet1->id, $wallet2->id, 1000, 'test:123:'.$user1Id,
            new TransferAndPayDescriptions('1', '2', '3'), null);
        $this->assertEquals(1000, $transaction->credit_before);
        $this->assertEquals(-1000, $transaction->amount);
        $wallet1 = $this->client->getWallet($wallet1->id);
        $this->assertEquals(-1000, $wallet1->credit);
        $transaction = $this->client->chargeWallet($wallet1->id, 3000, 'test:234'.$user1Id, 'ندارد');
        $this->assertEquals(-1000, $transaction->credit_before);
        $wallet1 = $this->client->getWallet($wallet1->id);
        $this->assertEquals(2000, $wallet1->credit);
        $this->assertEquals(3000, $wallet1->sum_charges);
    }
}
