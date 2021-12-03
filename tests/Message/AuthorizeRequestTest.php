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
        //Response according to https://docs.pay.nl/developers#card-payments
        //but it is not valid JSON as brackets/commas are missing
        //and does not have the same structure as the error response
        $this->setMockHttpResponse('AuthorizeSuccess.txt');

        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $response = $this->request->send();
        $this->assertInstanceOf(AuthorizeResponse::class, $response);

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

    public function testCardPayment()
    {

        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $card = $this->getValidCard();
        $objCard = new CreditCard($card);
        $this->request->setCard($objCard);

        $data = $this->request->getData();

        $this->assertNotEmpty($data['payment']);
        $payment = $data['payment'];
        $this->assertEquals("card", $payment['method']);
        $this->assertNotEmpty($payment['card']);
        $requestCard = $payment['card'];
        $this->assertEquals($objCard->getNumber(), $requestCard['number']);
        $this->assertEquals($objCard->getExpiryMonth(), $requestCard['expire_month']);
        $this->assertEquals($objCard->getExpiryYear(), $requestCard['expire_year']);
        $this->assertEquals($objCard->getCvv(), $requestCard['csv']);
        $this->assertEquals($objCard->getName(), $requestCard['name']);
    }

    public function testCsePayment()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $cse = [
            "identifier"    => uniqid(),
            "data"          => uniqid()
        ];
        $this->request->setCse($cse);
        $data = $this->request->getData();

        $this->assertNotEmpty($data['payment']);
        $payment = $data['payment'];
        $this->assertEquals("cse", $payment['method']);
        $this->assertNotEmpty($payment['cse']);
        $requestCse = $payment['cse'];
        $this->assertEquals($requestCse['identifier'], $cse['identifier']);
        $this->assertEquals($requestCse['data'], $cse['data']);
    }

    public function testCardAddress()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $card = $this->getValidCard();
        $objCard = new CreditCard($card);
        $this->request->setCard($objCard);

        $data = $this->request->getData();

        $this->assertNotEmpty($data['customer']);
        $customer = $data['customer'];
        $this->assertNotEmpty($customer['address']);
        $address = $customer['address'];

        $strAddress = $objCard->getShippingAddress1() . ' ' . $objCard->getShippingAddress2();
        $arrAddressParts = $this->request->getAddressParts($strAddress);

        if (isset($arrAddressParts[0])) $this->assertEquals($arrAddressParts[0], $address['streetName']);
        if (isset($arrAddressParts[1])) $this->assertEquals($arrAddressParts[1], $address['streetNumber']);
        if (isset($arrAddressParts[2])) $this->assertEquals($arrAddressParts[2], $address['streetNumberExtension']);

        $this->assertEquals($objCard->getShippingPostcode(), $address['zipCode']);
        $this->assertEquals($objCard->getShippingCity(), $address['city']);
        $this->assertEquals($objCard->getShippingCountry(), $address['countryCode']);
        $this->assertEquals($objCard->getShippingState(), $address['state']);
    }

    public function testCardInvoiceAddress()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $card = $this->getValidCard();
        $objCard = new CreditCard($card);
        $this->request->setCard($objCard);

        $data = $this->request->getData();

        $this->assertNotEmpty($data['customer']);
        $customer = $data['customer'];
        $this->assertNotEmpty($customer['invoice']);
        $this->assertNotEmpty($customer['invoice']['address']);
        $address = $customer['invoice']['address'];

        $strAddress = $objCard->getBillingAddress1() . ' ' . $objCard->getBillingAddress2();
        $arrAddressParts = $this->request->getAddressParts($strAddress);

        if (isset($arrAddressParts[0])) $this->assertEquals($arrAddressParts[0], $address['streetName']);
        if (isset($arrAddressParts[1])) $this->assertEquals($arrAddressParts[1], $address['streetNumber']);
        if (isset($arrAddressParts[2])) $this->assertEquals($arrAddressParts[2], $address['streetNumberExtension']);

        $this->assertEquals($objCard->getBillingPostcode(), $address['zipCode']);
        $this->assertEquals($objCard->getBillingCity(), $address['city']);
        $this->assertEquals($objCard->getBillingCountry(), $address['countryCode']);
        $this->assertEquals($objCard->getBillingState(), $address['state']);
    }

    public function testStatsData()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $statsData = [
            'info' => uniqid(),
            'tool' => uniqid(),
            'extra1' => uniqid(),
            'extra2' => uniqid(),
            'extra3' => uniqid()
        ];

        $this->request->setStatsData($statsData);

        $data = $this->request->getData();

        $this->assertArrayHasKey('stats', $data);
        $this->assertEquals($statsData['info'], $data['stats']['info']);
        $this->assertEquals($statsData['tool'], $data['stats']['tool']);
        $this->assertEquals($statsData['extra1'], $data['stats']['extra1']);
        $this->assertEquals($statsData['extra2'], $data['stats']['extra2']);
        $this->assertEquals($statsData['extra3'], $data['stats']['extra3']);
    }

    public function testDates()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $invoiceDate = new \DateTime('now');
        $deliveryDate = new \DateTime('tomorrow');
        $expireDate = new \DateTime('now + 4 hours');

        $invoiceDate = $invoiceDate->format('d-m-Y');
        $deliveryDate = $deliveryDate->format('d-m-Y');
        $expireDate = $expireDate->format('d-m-Y H:i:s');

        $this->request->setInvoiceDate($invoiceDate);
        $this->request->setDeliveryDate($deliveryDate);
        $this->request->setExpireDate($expireDate);

        $data = $this->request->getData();

        $this->assertArrayHasKey('order', $data);
        $this->assertArrayHasKey('transaction', $data);
        $this->assertArrayHasKey('invoiceDate', $data['order']);
        $this->assertArrayHasKey('deliveryDate', $data['order']);
        $this->assertArrayHasKey('expireDate', $data['transaction']);
        $this->assertEquals($invoiceDate, $data['order']['invoiceDate']);
        $this->assertEquals($deliveryDate, $data['order']['deliveryDate']);
        $this->assertEquals($expireDate, $data['transaction']['expireDate']);
    }

    public function testCustomerData()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $customerReference = uniqid();
        $this->request->setCustomerReference($customerReference);

        $data = $this->request->getData();

        $this->assertArrayHasKey('customer', $data);
        $this->assertArrayHasKey('transaction', $data); //Not sure if customer reference needs to be in transaction
        $this->assertArrayHasKey('reference', $data['transaction']);
        $this->assertEquals($customerReference, $data['transaction']['reference']);
    }

    public function testPaynlItem()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');
        $this->request->setNotifyUrl('https://www.pay.nl/exchange');


        $name = uniqid();
        $price = rand(1, 1000) / 100;
        $quantity = rand(1, 10);
        $productId = uniqid();
        $vatPercentage = rand(0, 21);

        $objItem = new \Omnipay\Paynl\Common\Item([
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            'productId' => $productId,
            'productType' => \Omnipay\Paynl\Common\Item::PRODUCT_TYPE_ARTICLE,
            'vatPercentage' => $vatPercentage
        ]);

        $this->request->setItems([$objItem]);

        $data = $this->request->getData();

        $this->assertNotEmpty($data['order']['products'][0]);
        $item = $data['order']['products'][0];

        $this->assertEquals($objItem->getProductId(), $item['id']);
        $this->assertEquals($objItem->getName(), $item['description']);
        $this->assertEquals(round($objItem->getPrice() * 100), $item['price']);
        $this->assertEquals($objItem->getQuantity(), $item['quantity']);
        $this->assertEquals($objItem->getProductType(), $item['type']);
        $this->assertEquals($objItem->getVatPercentage(), $item['vatPercentage']);
    }

    public function testStockItem()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');
        $this->request->setNotifyUrl('https://www.pay.nl/exchange');

        $name = uniqid();
        $price = rand(1, 1000) / 100;
        $quantity = rand(1, 10);

        $objItem = new \Omnipay\Common\Item([
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
        ]);

        $this->request->setItems([$objItem]);

        $data = $this->request->getData();

        $this->assertNotEmpty($data['order']['products'][0]);
        $item = $data['order']['products'][0];

        $this->assertEquals($objItem->getName(), $item['description']);
        $this->assertEquals(round($objItem->getPrice() * 100), $item['price']);
        $this->assertEquals($objItem->getQuantity(), $item['quantity']);

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