<?php

namespace Omnipay\Paynl\Test\Message;


use Omnipay\Common\CreditCard;
use Omnipay\Paynl\Message\Request\FetchEncryptionKeysRequest;
use Omnipay\Paynl\Message\Response\FetchEncryptionKeysResponse;
use Omnipay\Tests\TestCase;

class FetchEncryptionKeysRequestTest extends TestCase
{
    /**
     * @var FetchEncryptionKeysRequest
     */
    protected $request;

    public function testSendSuccess()
    {
        $this->setMockHttpResponse('FetchEncryptionKeysSuccess.txt');

        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $response = $this->request->send();
        $this->assertInstanceOf(FetchEncryptionKeysResponse::class, $response);

        $this->assertTrue($response->isSuccessful());
        $this->assertIsArray($response->getKeys());
        $keys = $response->getKeys();
        foreach ($keys as $key) {
            $this->assertIsString($key['identifier']);
            $this->assertIsString($key['public_key']);
        }

        $this->assertEquals('GET', $response->getRedirectMethod());
    }

    protected function setUp(): void
    {
        $this->request = new FetchEncryptionKeysRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->request->initialize([
            'tokenCode' => 'AT-1234-5678',
            'apiToken' => 'some-token',
            'serviceId' => 'SL-1234-5678'
        ]);
    }
}