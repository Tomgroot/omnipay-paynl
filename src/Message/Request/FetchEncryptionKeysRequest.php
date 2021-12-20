<?php

namespace Omnipay\Paynl\Message\Request;


use Omnipay\Common\CreditCard;
use Omnipay\Common\Item;
use Omnipay\Paynl\Message\Response\FetchEncryptionKeysResponse;

/**
 * Class FetchEncryptionKeysRequest
 * To start a payment authentication
 * @package Omnipay\Paynl\Message\Request
 *
 * @method FetchEncryptionKeysResponse send()
 */
class FetchEncryptionKeysRequest extends AbstractPaynlRequest
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://payment.pay.nl';
    protected $version = '/v1/Payment/';

    /**
     * @return array
     */
    public function getData()
    {
        return [];
    }

    /**
     * @param array $data
     * @return \Omnipay\Paynl\Message\Response\FetchEncryptionKeysResponse
     */
    public function sendData($data)
    {
        $responseData = $this->sendRequest('getEncryptionKeys', $data);
        return $this->response = new FetchEncryptionKeysResponse($this, $responseData);
    }
}