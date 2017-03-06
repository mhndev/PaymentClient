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
        $this->client = PaymentClient::create('digizar', 'http://localhost:8001/');
        parent::setUp();
    }

    public function test_pay()
    {
        $invoice = $this->client->createInvoice(1000, 'http://localhost:8002');
        var_dump($invoice);
        echo $this->client->invoiceUrl($invoice)."\n";
        ob_flush();
        fgets(STDIN);
        $invoiceInfo = $this->client->getInvoiceInfo($invoice->id, $invoice->salt);
        var_dump($invoiceInfo);
    }
}
