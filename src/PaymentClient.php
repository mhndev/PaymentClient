<?php

namespace Digipeyk\PaymentClient;

use DateTime;
use Digipeyk\PaymentClient\Auth\StringTokenResolver;
use Digipeyk\PaymentClient\Auth\TokenResolverInterface;
use Digipeyk\PaymentClient\Exceptions\MalformedResponseException;
use Digipeyk\PaymentClient\Exceptions\PaymentException;
use Digipeyk\PaymentClient\Objects\Invoice;
use Digipeyk\PaymentClient\Objects\Transaction;
use Digipeyk\PaymentClient\Objects\TransactionPagination;
use Digipeyk\PaymentClient\Objects\TransferAndPayDescriptions;
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
     * @param string $tag
     *
     * @throws PaymentException
     *
     * @return Wallet
     */
    public function getWallet($walletId, $userId = null, $tag = '')
    {
        try {
            $response = $this->request('GET', '/api/getWallet', [
                'query' => [
                    'shop_name'  => $this->shopName,
                    'wallet_id'  => $walletId,
                    'user_id'    => $userId,
                    'tag' => $tag,
                ],
            ]);

            return new Wallet($this->getResult((string) $response->getBody()));
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * @param array $userIds
     * @param string $tag
     * @return array
     * @throws PaymentException
     */
    public function getWallets($userIds = [], $tag='')
    {
        try {

            $response = $this->request('GET', '/api/digipeyk/wallets', [
                'query' => [
                    'user_ids' => $userIds,
                    'tag'      => $tag
                ],
            ]);

            $wallets = $this->getResult((string) $response->getBody());
            
            return array_map(function ($wallet){
                    return new Wallet($wallet);
            }, $wallets);

        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }




    /**
     * Create a wallet for a user.
     *
     * @param string $userId
     * @param string $tag
     *
     * @throws PaymentException
     *
     * @return Wallet
     */
    public function createWallet($userId, $tag = '')
    {
        return $this->getWallet(null, $userId, $tag);
    }

    /**
     * Charge the wallet by the given amount, iff the uid is not duplicate.
     *
     * @param int $walletId
     * @param int $amount
     * @param string $uid
     * @param string $description
     * @param bool $isVirtual
     * @param string|null $coupon
     * @param int|null $pairId
     *
     * @throws PaymentException
     *
     * @return Transaction
     */
    public function chargeWallet($walletId, $amount, $uid, $description, $isVirtual = false, $coupon = null, $pairId = null)
    {
        try {
            $response = $this->request('POST', '/api/chargeWallet', [
                'json' => [
                    'shop_name'   => $this->shopName,
                    'wallet_id'   => $walletId,
                    'amount'      => $amount,
                    'uid'         => $uid,
                    'description' => $description,
                    'is_virtual'  => $isVirtual,
                    'coupon'      => $coupon,
                    'pair_id'     => $pairId,
                ],
            ]);

            return new Transaction($this->getResult($response->getBody()->getContents()));
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }

    public function chargeOrGetTransaction(
        $walletId, $amount, $uid, $description, $isVirtual = false, $coupon = null, $pairId = null)
    {
        try {
            return $this->chargeWallet($walletId, $amount, $uid, $description, $isVirtual, $coupon, $pairId);
        } catch (PaymentException $e) {
            return $this->getTransactionFromException($e);
        }
    }

    public function transferAndPay($fromWalletId, $toWalletId, $amount, $uid,
                                   TransferAndPayDescriptions $descriptions, $coupon)
    {
        try {
            $response = $this->request('POST', '/api/transferAndPay', [
                'json' => [
                    'shop_name'         => $this->shopName,
                    'from_wallet_id'    => $fromWalletId,
                    'to_wallet_id'      => $toWalletId,
                    'amount'            => $amount,
                    'uid'               => $uid,
                    'descriptions'      => $descriptions->toArray(),
                    'coupon'            => $coupon,
                ],
            ]);
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
        return new Transaction($this->getResult($response->getBody()->getContents()));
    }

    public function transferAndPayOrGetTransaction($fromWalletId, $toWalletId, $amount, $uid,
                                                   TransferAndPayDescriptions $descriptions, $coupon)
    {
        try {
            return $this->transferAndPay($fromWalletId, $toWalletId, $amount, $uid, $descriptions, $coupon);
        } catch (PaymentException $e) {
            return $this->getTransactionFromException($e);
        }
    }

    public function revertTransaction($transactionId, $description)
    {
        try {
            $response = $this->request('POST', '/api/revertTransaction', [
                'json' => [
                    'shop_name' => $this->shopName,
                    'transaction_id' => $transactionId,
                    'description' => $description,
                ],
            ]);
            return new Transaction($this->getResult((string)$response->getBody()));
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * Search in the list of transactions. The result is simple-paginated (not length aware).
     *
     * @param int|null $userId
     * @param string|null $tag
     * @param DateTime|null $fromDate
     * @param DateTime|null $toDate
     * @param bool $pair
     * @param bool $descending
     * @param int|null $page
     * @param int|null $perPage
     * @return TransactionPagination
     * @throws PaymentException
     */
    public function queryTransactions($userId = null, $tag = null, DateTime $fromDate = null, DateTime $toDate = null, $pair = true, $descending = true, $page = 1, $perPage = 100)
    {
        try {
            $query = [
                'shop_name'  => $this->shopName,
                'descending' => (bool) $descending,
                'pair'       => (bool) $pair,
            ];

            if (! is_null($userId)) {
                $query['user_id'] = $userId;
            }
            if (! is_null($tag)) {
                $query['tag'] = $tag;
            }

            if (! is_null($fromDate)) {
                $query['from_date'] = $fromDate->format('Y-m-d H:i:s');
            }
            if (! is_null($toDate)) {
                $query['to_date'] = $toDate->format('Y-m-d H:i:s');
            }
            if (! is_null($page)) {
                $query['page'] = $page;
            }
            if (! is_null($perPage)) {
                $query['perPage'] = $perPage;
            }
            $response = $this->request('GET', '/api/digipeyk/queryTransactions', [
                'query' => $query,
            ]);

            return $this->toTransactions($this->getResult($response->getBody()->getContents()));
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * @param int[] $ids
     *
     * @return Transaction[]
     *
     * @throws PaymentException
     */
    public function getTransactionsById(array $ids)
    {
        try {
            $response = $this->request('GET', '/api/digipeyk/getTransactionsById', [
                'query' => [
                    'ids' => $ids,
                ],
            ]);

            return $this->toArrayOfTransactions($this->getResult($response->getBody()->getContents()));
        } catch (RequestException $e) {
            throw $this->wrapException($e);
        }
    }

    /**
     * @param $token
     * @return array
     * @throws PaymentException
     */
    public function getReferral($token)
    {
        try {
            $response = $this->client->request('GET', '/api/referral/me', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => $token,
                ],
            ]);
            return $this->getResult($response->getBody()->getContents());
        }
        catch (RequestException $e) {
            if ($e->getCode() == 401) {
               throw new PaymentException();
            }
            else{
                throw $this->wrapException($e);
            }
        }
    }



    private function toTransactions(array $paginator)
    {
        $transactions = $this->toArrayOfTransactions($paginator['_embedded']['transactions']);

        return new TransactionPagination(
            $transactions,
            $paginator['total'],
            $paginator['per_page'],
            $paginator['current_page'],
            $paginator['last_page']);
    }

    private function toArrayOfTransactions($raw)
    {
        return array_map(function (array $transaction) {
            return new Transaction($transaction);
        }, $raw);
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

    /**
     * @param PaymentException $e
     *
     * @throws PaymentException
     *
     * @return Transaction
     */
    private function getTransactionFromException(PaymentException $e)
    {
        if ($e->getCode() != 400) {
            throw $e;
        }
        $json = json_decode($e->body, true);
        if (!is_array($json) ||
            !array_key_exists('error', $json) ||
            !is_array($json['error']) ||
            !array_key_exists('code', $json['error']) ||
            $json['error']['code'] != 'DuplicateUid'
        ) {
            throw $e;
        }
        return new Transaction($json['error']['info']['transaction']);
    }


}
