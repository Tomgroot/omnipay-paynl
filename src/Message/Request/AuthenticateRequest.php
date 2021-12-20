<?php

namespace Omnipay\Paynl\Message\Request;


use Omnipay\Common\CreditCard;
use Omnipay\Common\Item;
use Omnipay\Paynl\Message\Response\AuthenticateResponse;

/**
 * Class AuthenticateRequest
 * To start a payment authentication
 * @package Omnipay\Paynl\Message\Request
 *
 * @method AuthenticateResponse send()
 */
class AuthenticateRequest extends AuthorizeRequest
{

    /**
     * @param array $data
     * @return \Omnipay\Common\Message\ResponseInterface|PurchaseResponse
     */
    public function sendData($data)
    {
        $responseData = $this->sendRequest('authenticate', $data);
        return $this->response = new AuthenticateResponse($this, $responseData);
    }
}