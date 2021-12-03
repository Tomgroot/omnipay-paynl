<?php

namespace Omnipay\Paynl\Message\Request;


use Omnipay\Common\CreditCard;
use Omnipay\Common\Item;
use Omnipay\Paynl\Message\Response\AuthorizeResponse;

/**
 * Class AuthorizeRequest
 * To start a card payment
 * @package Omnipay\Paynl\Message\Request
 *
 * @method AuthorizeResponse send()
 */
class AuthorizeRequest extends AbstractPaynlRequest
{
    /**
     * Regex to find streetname, housenumber and suffix out of a street string
     * @var string
     */
    private $addressRegex = '#^([a-z0-9 [:punct:]\']*) ([0-9]{1,5})([a-z0-9 \-/]{0,})$#i';

    /**
     * @var string
     */
    protected $baseUrl = 'https://payment.pay.nl/v1/Payment/';

    /**
     * @return array
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getData()
    {

        $this->validate('tokenCode', 'apiToken', 'serviceId', 'amount', 'clientIp', 'returnUrl');

        $data['transaction'] = [
            'type'          => !empty($this->getType()) ? $this->getType() : 'ecom',
            'serviceId'     => $this->getServiceId(),
            'amount'        => $this->getAmountInteger(),
            'ipAddress'     => $this->getClientIp(),
            'finishUrl'     => $this->getReturnUrl(),
            'currency'      => !empty($this->getCurrency()) ? $this->getCurrency() : 'EUR',
            'description'   => $this->getDescription() ?: null,
            'expireDate'    => !empty($this->getExpireDate()) ? $this->getExpireDate() : null,
            'exchangeUrl'   => !empty($this->getNotifyUrl()) ? $this->getNotifyUrl() : null,
            'reference'     => !empty($this->getCustomerReference()) ? $this->getCustomerReference() : null //Reference of customer?
        ];

        $data['options'] = [];
        $data['testMode'] = $this->getTestMode() ? 1 : 0;

        $data['customer'] = [];

        if ($card = $this->getCard()) {
            $billingAddressParts = $this->getAddressParts($card->getBillingAddress1() . ' ' . $card->getBillingAddress2());
            $shippingAddressParts = ($card->getShippingAddress1() ? $this->getAddressParts($card->getShippingAddress1() . ' ' . $card->getShippingAddress2()) : $billingAddressParts);

            $data['transaction']['language'] = substr($card->getCountry(), 0, 2);

            $data['customer'] = [
                'firstName' => $card->getFirstName(), //Pay has no support for firstName, but some methods require full name. Conversion to initials is handled by Pay.nl based on the payment method.
                'lastName' => $card->getLastName(),
                'gender' => $card->getGender(), //Should be inserted in the CreditCard as M/F
                'dob' => $card->getBirthday('d-m-Y'),
                'phoneNumber' => $card->getPhone(),
                'emailAddress' => $card->getEmail(),
                'address' => array(
                    'streetName' => isset($shippingAddressParts[1]) ? $shippingAddressParts[1] : null,
                    'streetNumber' => isset($shippingAddressParts[2]) ? $shippingAddressParts[2] : null,
                    'streetNumberExtension' => isset($shippingAddressParts[3]) ? $shippingAddressParts[3] : null,
                    'zipCode' => $card->getShippingPostcode(),
                    'city' => $card->getShippingCity(),
                    'countryCode' => $card->getShippingCountry(),
                    'state' => $card->getShippingState()
                ),
                'invoice' => array(
                    'firstName' => $card->getBillingFirstName(),
                    'lastName' => $card->getBillingLastName(),
                    'gender' => $card->getGender(),
                    'address' => array(
                        'streetName' => isset($billingAddressParts[1]) ? $billingAddressParts[1] : null,
                        'streetNumber' => isset($billingAddressParts[2]) ? $billingAddressParts[2] : null,
                        'streetNumberExtension' => isset($billingAddressParts[3]) ? $billingAddressParts[3] : null,
                        'zipCode' => $card->getBillingPostcode(),
                        'city' => $card->getBillingCity(),
                        'countryCode' => $card->getBillingCountry(),
                        'state' => $card->getBillingState()
                    )
                )
            ];

            if (empty($this->getCse())) {
                $data['payment'] = [
                    'method'    => 'card',
                    'card'      => [
                        'number'        => $card->getNumber(),
                        'expire_month'  => $card->getExpiryMonth(),
                        'expire_year'   => $card->getExpiryYear(),
                        'csv'           => $card->getCvv(),
                        'name'          => $card->getName(),
                        'type'          => 'cit'
                    ]
                ];
            }
        }

        if (!empty($this->getCse())) {
            $data['payment'] = [
                'method'    => 'cse',
                'cse'       => $this->getCse()
            ];
        }

        $data['order'] = [
            'deliveryDate'  => !empty($this->getDeliveryDate()) ? $this->getDeliveryDate() : null,
            'invoiceDate'   => !empty($this->getInvoiceDate()) ? $this->getInvoiceDate() : null
        ];

        if ($items = $this->getItems()) {
            $data['order'] = [
                'products' => array_map(function ($item) {
                    /** @var Item $item */
                    $data = [
                        'description'   => $item->getName() ?: $item->getDescription(),
                        'price'         => round($item->getPrice() * 100),
                        'quantity'      => $item->getQuantity(),
                        'vatCode'       => 0,
                    ];
                    if (method_exists($item, 'getProductId')) {
                        $data['id'] = $item->getProductId();
                    } else {
                        $data['id'] = substr($item->getName(), 0, 25);
                    }
                    if (method_exists($item, 'getProductType')) {
                        $data['type'] = $item->getProductType();
                    }
                    if (method_exists($item, 'getVatPercentage')) {
                        $data['vatPercentage'] = $item->getVatPercentage();
                    }
                    return $data;
                }, $items->all()),
            ];
        }

        if ($statsData = $this->getStatsData()) {
            // Could be someone erroneously not set an array
            if (is_array($statsData)) {
                $allowableParams = ["info", "tool", "extra1", "extra2", "extra3"];
                $data['stats'] = array_filter($statsData, function($k) use ($allowableParams) {
                    return in_array($k, $allowableParams);
                }, ARRAY_FILTER_USE_KEY);
                $data['stats']['object'] = 'omnipay';
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @return \Omnipay\Common\Message\ResponseInterface|PurchaseResponse
     */
    public function sendData($data)
    {
        $responseData = $this->sendRequest('authorize', $data);
        return $this->response = new AuthorizeResponse($this, $responseData);
    }

    /**
     * Sets the CSE array
     * @param $value array
     * @return AuthorizeRequest
     */
    public function setCse($value)
    {
        return $this->setParameter('cse', $value);
    }

    /**
     * Returns the CSE array, with the identifier and encrypted data
     * @return mixed
     */
    public function getCse()
    {
        return $this->getParameter('cse');
    }

    /**
     * @param $value string
     * @return AuthorizeRequest
     */
    public function setType($value)
    {
        return $this->setParameter('type', $value);
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->getParameter('type');
    }

    /**
     * Set the expireDate
     *
     * @param $value array
     * @return $this
     */
    public function setExpireDate($value)
    {
        return $this->setParameter('expireDate', $value);
    }

    /**
     * @return mixed
     */
    public function getExpireDate()
    {
        return $this->getParameter('expireDate');
    }

    /**
     * @param $value string
     * @return $this
     */
    public function setCustomerReference($value)
    {
        return $this->setParameter('customerReference', $value);
    }

    /**
     * @return mixed
     */
    public function getCustomerReference()
    {
        return $this->getParameter('customerReference');
    }

    /**
     * Get the parts of an address
     * @param string $address
     * @return array
     */
    public function getAddressParts($address)
    {
        $addressParts = [];
        preg_match($this->addressRegex, trim($address), $addressParts);
        return array_filter($addressParts, 'trim');
    }

    /**
     * @param $value array
     * @return $this
     */
    public function setStatsData($value)
    {
        return $this->setParameter('statsData', $value);
    }

    /**
     * @return array
     */
    public function getStatsData()
    {
        return $this->getParameter('statsData');
    }

    /**
     * @param $value string
     * @return $this
     */
    public function setInvoiceDate($value)
    {
        return $this->setParameter('invoiceDate', $value);
    }

    /**
     * @return string
     */
    public function getInvoiceDate()
    {
        return $this->getParameter('invoiceDate');
    }

    /**
     * @param $value string
     * @return $this
     */
    public function setDeliveryDate($value)
    {
        return $this->setParameter('deliveryDate', $value);
    }

    /**
     * @return string
     */
    public function getDeliveryDate()
    {
        return $this->getParameter('deliveryDate');
    }
}