<?php

namespace Omnipay\Paynl\Test\Message;


use Omnipay\Common\CreditCard;
use Omnipay\Paynl\Message\Request\AuthorizeRequest;
use Omnipay\Paynl\Message\Request\PurchaseRequest;
use Omnipay\Paynl\Message\Response\PurchaseResponse;
use Omnipay\Tests\TestCase;

class AuthorizeRequestTest extends TestCase
{
    /**
     * @var AuthorizeRequest
     */
    protected $request;

    public function testSendSuccess()
    {

    }

    protected function setUp()
    {
        $this->request = new AuthorizeRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->request->initialize([
            'tokenCode' => 'AT-1234-5678',
            'apiToken' => 'some-token',
            'serviceId' => 'SL-1234-5678'
        ]);
    }
}