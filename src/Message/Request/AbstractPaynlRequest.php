<?php

namespace Omnipay\Paynl\Message\Request;


use Omnipay\Common\Message\AbstractRequest;

/**
 * Class AbstractPaynlRequest
 * @package Omnipay\Paynl\Message\Request
 */
abstract class AbstractPaynlRequest extends AbstractRequest
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://rest-api.pay.nl/v12/transaction/';

    /**
     * @param string $endpoint
     * @param array|null $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendRequest($endpoint, array $data = null)
    {
        $uri = $this->baseUrl . $endpoint . '/json';
        $method = 'GET';
        $headers = $this->getAuthHeader();
        $body = null;

        if (!is_null($data)) {
            $method = 'POST';
            $headers += ['Content-Type' => 'application/json'];
            $body = json_encode($data);
        }

        $response = $this->httpClient->request($method, $uri, $headers, $body);

        return json_decode($response->getBody(), true);
    }

    /**
     * @return array
     */
    private function getAuthHeader()
    {
        if (!$this->getTokenCode() || !$this->getApiToken()) {
            return [];
        }
        return [
            'Authorization' => 'Basic ' .
                base64_encode($this->getTokenCode() . ':' . $this->getApiToken())
        ];
    }

    /**
     * @return string
     */
    public function getTokenCode()
    {
        return $this->getParameter('tokenCode');
    }

    /**
     * @return string
     */
    public function getApiToken()
    {
        return $this->getParameter('apiToken');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setTokenCode($value)
    {
        return $this->setParameter('tokenCode', $value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setApiToken($value)
    {
        return $this->setParameter('apiToken', $value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setServiceId($value)
    {
        return $this->setParameter('serviceId', $value);
    }

    /**
     * @return string
     */
    public function getServiceId()
    {
        return $this->getParameter('serviceId');
    }
}