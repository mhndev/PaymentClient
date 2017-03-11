<?php

use Digipeyk\PaymentClient\PaymentClient;

class ManualTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentClient
     */
    private $client;

    public function setUp()
    {
        $this->client = PaymentClient::create(getenv('SHOP_NAME'), getenv('SERVER_URL'));
        parent::setUp();
    }

    public function test_pay()
    {
        //get wallet
        $wallet = $this->client->getWallet(21);
        echo(json_encode($wallet->getData(), JSON_PRETTY_PRINT));

        //create invoice
        $invoice = $this->client->createInvoice(21, 1000, 'http://localhost:8002');
        echo(json_encode($invoice->getData(), JSON_PRETTY_PRINT));
        echo $this->client->invoiceUrl($invoice)."\n";
        ob_flush();
        fgets(STDIN);

        //get invoice after payment is done
        $invoice = $this->client->getInvoiceInfo($invoice->id, $invoice->salt);
        echo(json_encode($invoice->getData(), JSON_PRETTY_PRINT));

        //get wallet
        $walletAfter = $this->client->getWallet(21);

        echo(json_encode($walletAfter->getData(), JSON_PRETTY_PRINT));
        $this->assertEquals($wallet->credit + 1000, $walletAfter->credit);

        //get the last transaction
        $transactions = $this->client->queryTransactions(true, 21, null, 1);
        $this->assertCount(1, $transactions);
        $this->assertEquals(1000, $transactions[0]->amount);

        //discharge the account
        $transaction = $this->client->chargeWallet(21, -1000, uniqid('test'), 'Pay for something');
        $this->assertEquals(-1000, $transaction->amount);
    }
}
