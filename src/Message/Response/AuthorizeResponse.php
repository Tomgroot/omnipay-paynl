<?php

namespace Omnipay\Paynl\Message\Response;


class AuthorizeResponse extends AbstractPaynlResponse
{
    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        if (!empty($this->data['request']['result']) && $this->data['request']['result'] == 1) {
            if (!empty($this->data['payment']['bankCode']) && $this->data['payment']['bankCode'] == "00") {
                if (!empty($this->data['transaction']['state']) && in_array(
                        $this->data['transaction']['state'],
                        array(85, 95, 100)
                    )) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns the next action needed for 3D secure authentication
     * @return mixed|null
     */
    public function getNextAction()
    {
        return isset($this->data['transaction']['stateName']) ? $this->data['transaction']['stateName'] : null;
    }

    /**
     * @return mixed|null
     */
    public function getTransactionOrderId()
    {
        return isset($this->data['transaction']['orderId']) ? $this->data['transaction']['orderId'] : null;
    }

    /**
     * @return mixed|null
     */
    public function getTransactionEntranceCode()
    {
        return isset($this->data['transaction']['entranceCode']) ? $this->data['transaction']['entranceCode'] : null;
    }

    /**
     * @inheritdoc
     */
    public function getTransactionReference()
    {
        return isset($this->data['transaction']['transactionId']) ? $this->data['transaction']['transactionId'] : null;
    }

    /**
     * @return mixed|string|null
     */
    public function getCode()
    {
        return isset($this->data['request']['errorId']) ? $this->data['request']['errorId'] : null;
    }

}