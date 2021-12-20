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

        if (!empty($this->getEntranceCode())) {
            $data['transaction'] = [
                'orderId'       => $this->getOrderId(),
                'entranceCode'  => $this->getEntranceCode()
            ];
        } else {
            $data['transaction'] = [
                'type'          => !empty($this->getType()) ? $this->getType() : 'ecom',
                'serviceId'     => $this->getServiceId(),
                'amount'        => $this->getAmountInteger(),
                'ipAddress'     => $this->getClientIp(),
                'finishUrl'     => $this->getReturnUrl(),
                'currency'      => !empty($this->getCurrency()) ? $this->getCurrency() : 'EUR',
                'description'   => $this->getDescription() ?: "",
                'expireDate'    => !empty($this->getExpireDate()) ? $this->getExpireDate() : "",
                'exchangeUrl'   => !empty($this->getNotifyUrl()) ? $this->getNotifyUrl() : "",
                'reference'     => !empty($this->getCustomerReference()) ? $this->getCustomerReference() : "" //Reference of customer?
            ];
        }

        $data['testMode'] = $this->getTestMode() ? 1 : 0;


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
            if (!empty($this->getPayTdsTransactionId())) {
                $data['payment']['auth'] = [
                    'payTdsTransactionId'   => $this->getPayTdsTransactionId(),
                    'payTdsAcquirerId'      => !empty($this->getPayTdsAcquirerId()) ? $this->getPayTdsAcquirerId() : ""
                ];
                $data['payment']['browser'] = [
                    "javaEnabled"       => $this->getJavaEnabled(),
                    "javascriptEnabled" => $this->getJavaScriptEnabled(),
                    "language"          => $this->getLanguage(),
                    "colorDepth"        => $this->getColorDepth(),
                    "screenHeight"      => $this->getScreenHeight(),
                    "screenWidth"       => $this->getScreenWidth(),
                    "tz"                => $this->getTz()
                ];
            }
        }

        if (!empty($this->getDeliveryDate())) {
            $data['order']['deliveryDate'] = $this->getDeliveryDate();
        }

        if (!empty($this->getInvoiceDate())) {
            $data['order']['invoiceDate'] = $this->getInvoiceDate();
        }

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
     * @param $value string
     * @return AuthorizeRequest
     */
    public function setOrderId($value)
    {
        return $this->setParameter('orderId', $value);
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->getParameter('orderId');
    }

    /**
     * @param $value string
     * @return AuthorizeRequest
     */
    public function setEntranceCode($value)
    {
        return $this->setParameter('entranceCode', $value);
    }

    /**
     * @return mixed
     */
    public function getEntranceCode()
    {
        return $this->getParameter('entranceCode');
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

    /**
     * @return AuthenticateRequest
     */
    public function setPayTdsAcquirerId($value)
    {
        return $this->setParameter('payTdsAcquirerId', $value);
    }

    /**
     * @return mixed
     */
    public function getPayTdsAcquirerId()
    {
        return $this->getParameter('payTdsAcquirerId');
    }

    /**
     * @return AuthenticateRequest
     */
    public function setPayTdsTransactionId($value)
    {
        return $this->setParameter('payTdsTransactionId', $value);
    }

    /**
     * @return mixed
     */
    public function getPayTdsTransactionId()
    {
        return $this->getParameter('payTdsTransactionId');
    }

    /**
     * @return AuthenticateRequest
     */
    public function setJavaEnabled($value)
    {
        return $this->setParameter('javaEnabled', $value);
    }

    /**
     * @return mixed
     */
    public function getJavaEnabled()
    {
        return $this->getParameter('javaEnabled');
    }

    /**
     * @return AuthenticateRequest
     */
    public function setJavaScriptEnabled($value)
    {
        return $this->setParameter('javaScriptEnabled', $value);
    }

    /**
     * @return mixed
     */
    public function getJavaScriptEnabled()
    {
        return $this->getParameter('javaScriptEnabled');
    }

    /**
     * @return AuthenticateRequest
     */
    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->getParameter('language');
    }

    /**
     * @return AuthenticateRequest
     */
    public function setColorDepth($value)
    {
        return $this->setParameter('colorDepth', $value);
    }

    /**
     * @return mixed
     */
    public function getColorDepth()
    {
        return $this->getParameter('colorDepth');
    }

    /**
     * @return AuthenticateRequest
     */
    public function setScreenHeight($value)
    {
        return $this->setParameter('screenHeight', $value);
    }

    /**
     * @return mixed
     */
    public function getScreenHeight()
    {
        return $this->getParameter('screenHeight');
    }

    /**
     * @return AuthenticateRequest
     */
    public function setScreenWidth($value)
    {
        return $this->setParameter('screenWidth', $value);
    }

    /**
     * @return mixed
     */
    public function getScreenWidth()
    {
        return $this->getParameter('screenWidth');
    }

    /**
     * @return AuthenticateRequest
     */
    public function setTz($value)
    {
        return $this->setParameter('tz', $value);
    }

    /**
     * @return mixed
     */
    public function getTz()
    {
        return $this->getParameter('tz');
    }
}