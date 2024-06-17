<?php

namespace KvaPay\Services;

use KvaPay\Resources\CreateOrder;

class PaymentsService extends AbstractService
{
    /**
     * Create order at KvaPay and redirect shopper to invoice (shortLink).
     *
     * @param string[] $params
     * @return CreateOrder|mixed
     */
    public function createPaymentShortLink(array $params = [])
    {

        return $this->request('post', '/payments/shortlink', $params);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function createPayment(array $params = [])
    {
        return $this->request('post', '/payments', $params);
    }

    /**
     * @param $link
     * @return mixed
     */
    public function paymentShortLinkDetail($link)
    {
        return $this->request('get', '/payments/shortlink/' . $link);
    }

    /**
     * @param $paymentId
     * @param array $params
     * @return mixed
     */
    public function createPaymentRefund($paymentId, array $params = [])
    {
        return $this->request('post', '/payments/' . $paymentId . 'refund', $params);
    }


    /**
     * @param string $paymentId
     * @return mixed
     */
    public function paymentDetail(string $paymentId)
    {
        return $this->request('get', '/payments/' . $paymentId);
    }

    /**
     * Verify order at KvaPay.
     *
     * @param string[] $params
     * @return mixed
     */
    public function verify(array $params = [])
    {
        return $this->request('get', '/payments/verify', $params);
    }

    /**
     * Verify order at KvaPay.
     *
     * @param string[] $params
     * @return mixed
     */
    public function paymentOptions(array $params = [])
    {
        return $this->request('get', '/payments/options', $params);
    }
}
