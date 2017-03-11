<?php

namespace Digipeyk\PaymentClient;

use Digipeyk\PaymentClient\Objects\Invoice;
use Digipeyk\PaymentClient\Objects\Transaction;
use Digipeyk\PaymentClient\Objects\Wallet;
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

    public function createInvoice($walletId, $amount, $redirectUrl, $webhook = null)
    {
        try {
            $params = [
                'shop_name' => $this->shopName,
                'wallet_id' => $walletId,
                'redirect_url' => $redirectUrl,
                'amount' => $amount,
            ];

            if ($webhook) {
                $params['webhook'] = $webhook;
            }

            $response = $this->client->request('POST', '/api/createInvoice', [
                'json' => $params
            ]);
            return new Invoice($this->getResult($response->getBody()->getContents()));
        } catch (RequestException $e) {
            throw new PaymentException($e->getMessage(), $e->getCode(), $e);
        }
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
            $response = $this->client->request('GET', '/api/getInvoice', [
                'query' => [
                    'shop_name'      => $this->shopName,
                    'invoice_id'     => $id,
                    'invoice_salt'   => $salt,
                ]
            ]);

            return new Invoice($this->getResult($response->getBody()->getContents()));
        } catch (RequestException $e) {
            throw new PaymentException($e->getMessage(), $e->getCode(), $e);
        }
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
     * Get wallet by wallet id or user id.
     *
     * @param int|null $walletId
     * @param string|null $userId
     *
     * @return Wallet
     */
    public function getWallet($walletId, $userId)
    {
        $response = $this->client->request('GET', '/api/getWallet', [
            'query' => [
                'shop_name' => $this->shopName,
                'wallet_id' => $walletId,
                'user_id'   => $userId,
            ],
        ]);

        return new Wallet($this->getResult($response->getBody()->getContents()));
    }

    /**
     * Create a wallet for a user with the given initial (credit) amount.
     *
     * @param string $userId
     * @param int $amount
     *
     * @return Wallet
     */
    public function createWallet($userId, $amount)
    {
        $response = $this->client->request('POST', '/api/createWallet', [
            'json' => [
                'shop_name' => $this->shopName,
                'user_id'   => $userId,
                'amount'    => $amount,
            ],
        ]);

        return new Wallet($this->getResult($response->getBody()->getContents()));
    }

    /**
     * Charge the wallet by the given amount, iff the uid is not duplicate.
     *
     * @param int $walletId
     * @param int $amount
     * @param string $uid
     * @param string $description
     *
     * @return Transaction
     */
    public function chargeWallet($walletId, $amount, $uid, $description)
    {
        $response = $this->client->request('POST', '/api/chargeWallet', [
            'json' => [
                'shop_name'   => $this->shopName,
                'wallet_id'   => $walletId,
                'amount'      => $amount,
                'uid'         => $uid,
                'description' => $description,
            ],
        ]);

        return new Transaction($this->getResult($response->getBody()->getContents()));
    }

    /**
     * Search in the list of transactions. The result is simple-paginated (not length aware).
     *
     * @param bool $descending
     * @param int|null $walletId
     * @param int|null $offset
     * @param int|null $limit
     *
     * @return Transaction[]
     */
    public function queryTransactions($descending = true, $walletId = null, $offset = null, $limit = null)
    {
        $query = ['decending' => $descending];
        if (! is_null($walletId)) {
            $query['wallet_id'] = $walletId;
        }
        if (! is_null($offset)) {
            $query['offset'] = $offset;
        }
        if (! is_null($limit)) {
            $query['limit'] = $limit;
        }
        $response = $this->client->request('GET', '/api/queryTransactions', [
            'query' => $query,
        ]);

        return $this->toTransactions($this->getResult($response->getBody()->getContents()));
    }

    private function toTransactions(array $transactions)
    {
        return array_map(function (array $transaction) {
            return new Transaction($transaction);
        }, $transactions);
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
