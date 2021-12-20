<?php

namespace Omnipay\Paynl\Message\Response;


class FetchEncryptionKeysResponse extends AbstractPaynlResponse
{
    /**
     * @return mixed|null
     */
    public function getKeys()
    {
        return isset($this->data['keys']) ? $this->data['keys'] : null;
    }
}