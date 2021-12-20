<?php

namespace Omnipay\Paynl\Message\Request;

use Omnipay\Paynl\Message\Response\FetchAuthenticationStatusResponse;

/**
 * Class FetchAuthenticationStatusRequest
 * To fetch the authentication status
 * @package Omnipay\Paynl\Message\Request
 *
 * @method FetchAuthenticationStatusResponse send()
 */
class FetchAuthenticationStatusRequest extends AbstractPaynlRequest
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://payment.pay.nl/v1/Payment/';

    /**
     * @return array
     */
    public function getData()
    {
        $this->validate('apiToken', 'tokenCode', 'transactionId');
        return [
            'transactionId' => $this->getParameter('transactionId')
        ];
    }

    /**
     * @param array $data
     * @return \Omnipay\Common\Message\ResponseInterface|PurchaseResponse
     */
    public function sendData($data)
    {
        $responseData = $this->sendRequest('getAuthenticationStatus', $data);
        return $this->response = new FetchAuthenticationStatusResponse($this, $responseData);
    }
}