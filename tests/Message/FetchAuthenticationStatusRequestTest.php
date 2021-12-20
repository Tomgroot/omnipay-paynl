<?php

namespace Omnipay\Paynl\Test\Message;


use Omnipay\Common\CreditCard;
use Omnipay\Paynl\Message\Request\FetchAuthenticationStatusRequest;
use Omnipay\Paynl\Message\Response\FetchAuthenticationStatusResponse;
use Omnipay\Tests\TestCase;

class FetchAuthenticationStatusRequestTest extends TestCase
{
    /**
     * @var AuthenticateRequest
     */
    protected $request;

    public function testSendSuccess()
    {
        $this->setMockHttpResponse('FetchAuthenticationStatusSuccess.txt');

        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $response = $this->request->send();
        $this->assertInstanceOf(FetchAuthenticationStatusResponse::class, $response);

        $this->assertTrue($response->isSuccessful());
        $this->assertIsArray($response->getThreeDS());
        $three_ds = $response->getThreeDS();
        $this->assertIsString($three_ds['transactionID']);
        $this->assertIsString($three_ds['transactionStatusCode']);

        $this->assertEquals('GET', $response->getRedirectMethod());
    }

    protected function setUp(): void
    {
        $this->request = new FetchAuthenticationStatusRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->request->initialize([
            'tokenCode' => 'AT-1234-5678',
            'apiToken' => 'some-token',
            'serviceId' => 'SL-1234-5678'
        ]);
    }
}