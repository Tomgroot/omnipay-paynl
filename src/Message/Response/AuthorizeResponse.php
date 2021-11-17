<?php

namespace Omnipay\Paynl\Message\Response;


class AuthorizeResponse extends AbstractPaynlResponse
{
    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        return isset($this->data['request']['result']) && $this->data['request']['result'];
    }

    /**
     * @inheritdoc
     */
    public function getTransactionReference()
    {
        return isset($this->data['transaction']['transactionId']) ? $this->data['paymentDetails']['transactionId'] : null;
    }

    /**
     * @return mixed|string|null
     */
    public function getCode()
    {
        return isset($this->data['request']['errorId']) ? $this->data['request']['errorId'] : null;
    }

}