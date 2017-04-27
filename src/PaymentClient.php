<?php

namespace Digipeyk\PaymentClient;

use Digipeyk\PaymentClient\Auth\StringTokenResolver;
use Digipeyk\PaymentClient\Auth\TokenResolverInterface;
use Digipeyk\PaymentClient\Exceptions\MalformedResponseException;
use Digipeyk\PaymentClient\Exceptions\PaymentException;
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
     * @var TokenResolverInterface|string
     */
    protected $token;

    /**
     * @param string $shopName
     * @param Client $client
     * @param string|TokenResolverInterface $token
     */
    public function __construct($shopName, Client $client, $token)
    {
        $this->shopName = $shopName;
        $this->client = $client;
        $this->token = $token instanceof TokenResolverInterface ? $token : new StringTokenResolver($token);
    }

    public static function create($shopName, $serverUrl, $token, $guzzleConfig = [])
    {
        return new static($shopName, new Client(array_merge([
            'base_uri' => $serverUrl
        ], $guzzleConfig)), $token);
    }

    protected function request($method, $url, $options, $retry = true)
    {
        $headers['headers'] = [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer '.$this->token->getToken(),
        ];
        try {
            return $this->client->request($method, $url, array_merge($options, $headers));
        } catch (RequestException $e) {
            if ($e->getCode() == 401 && $retry) {
                try {
                    $this->token->refreshToken();
                } catch (\Exception $refreshException) {
                    throw $e;
                }
                return $this->request($method, $url, $options, false);
            }
            throw $e;
        }
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

            $response = $this->request('POST', '/api/createInvoice', [
                'json' => $params
            ]);
            return new Invoice($this->getResult($response->getBody()->getContents()));
        } catch (RequestException $e) {
            throw $this->wrapException($e);
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
            $response = $this->request('GET', '/api/getInvoice', [
                'query' => [
                    'shop_name'      => $this->shopName,
                    'invoice_id'     => $id,
                    'invoice_salt'   => $salt,
                ]
            ]);

            return new Invoice($this->getResult($response->getBody()->getContents()));
        } catch (RequestException $e) {
            throw $this->wrapException($e);
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
     * @throws PaymentException
     *
     * @return Wallet
     */
    public function getWallet($walletId, $userId = null)
    {
        try {
            $response = $this->request('GET', '/api/getWallet', [
                'query' => [
                    'shop_name' => $this->shopName,
                    'wallet_id' => $walletId,
                    'user_id'   => $userId,
                ],
            ]);

            return new Wallet($this->getResult($response->getBody()->getContents()));
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * Create a wallet for a user.
     *
     * @param string $userId
     *
     * @throws PaymentException
     *
     * @return Wallet
     */
    public function createWallet($userId)
    {
        try {
            $response = $this->request('POST', '/api/createWallet', [
                'json' => [
                    'shop_name' => $this->shopName,
                    'user_id'   => $userId,
                ],
            ]);

            return new Wallet($this->getResult($response->getBody()->getContents()));
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * Charge the wallet by the given amount, iff the uid is not duplicate.
     *
     * @param int $walletId
     * @param int $amount
     * @param string $uid
     * @param string $description
     *
     * @throws PaymentException
     *
     * @return Transaction
     */
    public function chargeWallet($walletId, $amount, $uid, $description)
    {
        try {
            $response = $this->request('POST', '/api/chargeWallet', [
                'json' => [
                    'shop_name'   => $this->shopName,
                    'wallet_id'   => $walletId,
                    'amount'      => $amount,
                    'uid'         => $uid,
                    'description' => $description,
                ],
            ]);

            return new Transaction($this->getResult($response->getBody()->getContents()));
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * Search in the list of transactions. The result is simple-paginated (not length aware).
     *
     * @param bool $descending
     * @param int|null $walletId
     * @param int|null $offset
     * @param int|null $limit
     *
     * @throws PaymentException
     *
     * @return Transaction[]
     */
    public function queryTransactions($descending = true, $walletId = null, $offset = null, $limit = null)
    {
        try {
            $query = [
                'shop_name' => $this->shopName,
                'descending' => $descending,
            ];
            if (! is_null($walletId)) {
                $query['wallet_id'] = $walletId;
            }
            if (! is_null($offset)) {
                $query['offset'] = $offset;
            }
            if (! is_null($limit)) {
                $query['limit'] = $limit;
            }
            $response = $this->request('GET', '/api/queryTransactions', [
                'query' => $query,
            ]);

            return $this->toTransactions($this->getResult($response->getBody()->getContents()));
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
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
     * @throws MalformedResponseException
     *
     * @return array
     */
    private function getResult($body)
    {
        $decoded = json_decode($body, true);

        if (! isset($decoded['status']) || $decoded['status'] != 'OK' || ! isset($decoded['result'])) {

            throw new MalformedResponseException($body);
        }

        return $decoded['result'];
    }

    private function wrapException(RequestException $e)
    {
        return new PaymentException($e);
    }
}
