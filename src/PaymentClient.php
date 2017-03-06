<?php

namespace Digipeyk\PaymentClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PaymentClient
{
    /**
     * @var string
     */
    protected $shopName;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param string $shopName
     * @param Client $client
     */
    public function __construct($shopName, Client $client)
    {
        $this->shopName = $shopName;
        $this->client = $client;
    }

    public static function create($shopName, $serverUrl, $guzzleConfig = [])
    {
        return new static($shopName, new Client(array_merge([
            'base_uri' => $serverUrl
        ], $guzzleConfig)));
    }

    public function createInvoice($amount, $redirectUrl, $webhook = null)
    {
        try {
            $params = [
                'shop_name' => $this->shopName,
                'redirect_url' => $redirectUrl,
                'amount' => $amount,
            ];

            if ($webhook) {
                $params['webhook'] = $webhook;
            }

            $response = $this->client->request('POST', '/api/invoice', [
                'json' => $params
            ]);
            $result = $this->getResult($response->getBody()->getContents());
        } catch (RequestException $e) {
            throw new PaymentException($e->getMessage(), $e->getCode(), $e);
        }
        return new Invoice($result['invoice']['id'], $result['invoice']['salt'], false, null, null);
    }

    /**
     * @param  int     $id
     * @param  string  $salt
     *
     * @throws PaymentException
     *
     * @return Invoice
     */
    public function getInvoiceInfo($id, $salt)
    {
        try {
            $response = $this->client->request('GET', '/api/invoice/info', [
                'query' => [
                    'shop_name'      => $this->shopName,
                    'invoice_id'     => $id,
                    'invoice_salt'   => $salt,
                ]
            ]);
            $result = $this->getResult($response->getBody()->getContents());
        } catch (RequestException $e) {
            throw new PaymentException($e->getMessage(), $e->getCode(), $e);
        }

        $done = isset($result['transaction']['payment_done']) ? $result['transaction']['payment_done'] : null;
        $verified = isset($result['transaction']['verified']) ? $result['transaction']['verified'] : null;

        return new Invoice($result['id'], $result['salt'], $done, $verified, $result);
    }

    /**
     * @param  Invoice $invoice
     * @return string
     */
    public function invoiceUrl(Invoice $invoice)
    {
        return $this->client->getConfig('base_uri').'v/'.$invoice->id.'/'.$invoice->salt;
    }

    /**
     * @param string $body
     *
     * @throws PaymentException
     *
     * @return array
     */
    private function getResult($body)
    {
        $decoded = json_decode($body, true);

        if (! isset($decoded['success']) || ! $decoded['success'] || ! isset($decoded['result'])) {
            throw new PaymentException($body);
        }

        return $decoded['result'];
    }
}
