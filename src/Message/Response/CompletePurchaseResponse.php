<?php


namespace Omnipay\Paynl\Message\Response;


class CompletePurchaseResponse extends FetchTransactionResponse
{
    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return parent::isSuccessful() && $this->isPaid();
    }
}
