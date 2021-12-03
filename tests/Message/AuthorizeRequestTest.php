<?php

namespace Omnipay\Paynl\Test\Message;


use Omnipay\Common\CreditCard;
use Omnipay\Paynl\Message\Request\AuthorizeRequest;
use Omnipay\Paynl\Message\Response\AuthorizeResponse;
use Omnipay\Tests\TestCase;

class AuthorizeRequestTest extends TestCase
{
    /**
     * @var AuthorizeRequest
     */
    protected $request;

    public function testSendSuccess()
    {
        //AuthorizeSuccess according to https://docs.pay.nl/developers#card-payments
        //but is not valid JSON as brackets/commas are missing and does not have the same structure as the error response
        $this->setMockHttpResponse('AuthorizeSuccess.txt');

        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $response = $this->request->send();
        $this->assertInstanceOf(AuthorizeResponse::class, $response);

        var_dump($response);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());

        //$this->assertIsString($response->getTransactionReference());
        $this->assertIsString($response->getCode());

        $this->assertEquals('GET', $response->getRedirectMethod());
    }

    public function testSendError()
    {
        $this->setMockHttpResponse('AuthorizeError.txt');

        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $response = $this->request->send();
        $this->assertInstanceOf(AuthorizeResponse::class, $response);

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());

        $this->assertIsString($response->getTransactionReference());
        $this->assertIsString($response->getCode());

        $this->assertEquals('GET', $response->getRedirectMethod());
    }

    protected function setUp(): void
    {
        $this->request = new AuthorizeRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->request->initialize([
            'tokenCode' => 'AT-1234-5678',
            'apiToken' => 'some-token',
            'serviceId' => 'SL-1234-5678'
        ]);
    }
}