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
        $this->setMockHttpResponse('AuthorizeSuccess.txt');

        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');
        $cse = json_decode('{"identifier":"6BDEA18D76846D3D23B1B2761F5E50ABA0314F7A8DE5FF9593EDAE7614319B8A","public_key":"LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlJQ0lqQU5CZ2txaGtpRzl3MEJBUUVGQUFPQ0FnOEFNSUlDQ2dLQ0FnRUFxbGtmK0lyTHl1NW42TGJTdTVhegpzOGFWTFhFbUVFd21rMFZjYTkwZVdvN1pOQ1RIcFY5T1BCNUs3QzlXT0gzVW9pNzljRnZIR0JIVmk4cC9NOFdxCjUwTVhISW1ZRytTK3dzcnJDcTFmazNHNTlQbWhxZUlZRmR0bi84YUZ2d2dYYWV2QlIvK0tzU3NiTzBhVHNSRGsKeWFuOGZYYklpZVVwL0RrT3JPaEhjUkZOS3k4TmpCT3ZLZjc2YjNJRWhLdmMySFFRWlBnOEwyRzlOYlBZeitkbQpkZ3lYWlF4WE00YkZDNHAxeVMzSDBGR3FtUkVmaThtU2lSRVNxZjFUcEttVWxCQStncUY3WXN1NE1abmFtbTAyCkhPS2NZSWtmQ3RGTm5zcUxtWGZpUHNLZzNJME1QVEpBdzhyTFMwemhud2lNVVVmcmR5RmM3Z2tJZWhadk9rL2IKbU9wOWxPN3NqMHBraG5SQi9rSEE1Tm5aZis5Vy8ra2tGOUVQZXQxVUV6SEEzUnR0RVYwV05qUENOcHp4c0FsZAo5VG51dXloY2RzSDlPUmh1NHlldzJlVnhWQXJsTHMzbFUybFZJSFZBakZ1K0p0TjBuQ2ZGb29NN1kyY2RhallFCk1CUlA5UGhkWnBiZE16OFkzRDJoOW5WVVlZZUlvbno1OFNSdHhVd014aitnemV3NjhLemo3SXVSUUE2bml6azQKbFU2OGl1RnZrdVI0WkZISjdPZ0pkUTdDUEwreFhkbjdPSmZ6T1hGeVVVVTZjMFRmSEFpRFJxL0hZTlNoYkZ6OAozSWo3YnJhbGMwWjE1YyttaHk1S1pPalF1K0lxRU1YR1AvUHRJbHpnWk9FRWdpSi9RTkJTei9JTGZyK1RuM0xrCkYwK085YSs3cnZpQS9zYVRmS25Vcjc4Q0F3RUFBUT09Ci0tLS0tRU5EIFBVQkxJQyBLRVktLS0tLQo="}');
        $this->request->setCse($cse);

        $response = $this->request->send();
        $this->assertInstanceOf(AuthorizeResponse::class, $response);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());

        $this->assertIsString($response->getTransactionReference());
        $this->assertIsString($response->getRedirectUrl());
        $this->assertIsString($response->getAcceptCode());

        $this->assertEquals('GET', $response->getRedirectMethod());
        $this->assertNull($response->getRedirectData());
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