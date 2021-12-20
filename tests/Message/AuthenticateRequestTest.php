<?php

namespace Omnipay\Paynl\Test\Message;


use Omnipay\Common\CreditCard;
use Omnipay\Paynl\Message\Request\AuthenticateRequest;
use Omnipay\Paynl\Message\Response\AuthenticateResponse;
use Omnipay\Tests\TestCase;

class AuthenticateRequestTest extends TestCase
{
    /**
     * @var AuthenticateRequest
     */
    protected $request;

    public function testSendSuccess()
    {
        $this->setMockHttpResponse('AuthenticateSuccess.txt');

        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $response = $this->request->send();
        $this->assertInstanceOf(AuthenticateResponse::class, $response);

        $this->assertTrue($response->isSuccessful());
        $this->assertIsArray($response->getThreeDS());
        $this->assertIsArray($response->getTransaction());
        $this->assertIsString($response->getThreeDSMethodUrl());
        $this->assertIsString($response->getThreeDSMethodData());
        $this->assertIsString($response->getNextAction());

        $this->assertEquals('GET', $response->getRedirectMethod());
    }

    public function testSendError()
    {
        $this->setMockHttpResponse('AuthenticateError.txt');

        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $response = $this->request->send();
        $this->assertInstanceOf(AuthenticateResponse::class, $response);

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());

        $this->assertIsString($response->getTransactionReference());
        $this->assertIsString($response->getCode());
        $this->assertIsString($response->getMessage());

        $this->assertEquals('GET', $response->getRedirectMethod());
    }

    protected function setUp(): void
    {
        $this->request = new AuthenticateRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->request->initialize([
            'tokenCode' => 'AT-1234-5678',
            'apiToken' => 'some-token',
            'serviceId' => 'SL-1234-5678'
        ]);
    }
}