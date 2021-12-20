<?php

namespace Omnipay\Paynl\Message\Response;


class FetchAuthenticationStatusResponse extends AbstractPaynlResponse
{
    /**
     * @return mixed|null
     */
    public function getThreeDS()
    {
        return isset($this->data['threeDs']) ? $this->data['threeDs'] : null;
    }
}