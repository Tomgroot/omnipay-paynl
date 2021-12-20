<?php

namespace Omnipay\Paynl\Message\Response;


class AuthenticateResponse extends AuthorizeResponse
{
    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return isset($this->data['request']['result']) && $this->data['request']['result'] == 1;
    }

    /**
     * Returns the next action needed for 3D secure authentication
     * @return mixed|null
     */
    public function getNextAction()
    {
        return isset($this->data['threeDs']['nextAction']) ? $this->data['threeDs']['nextAction'] : null;
    }

    /**
     * @return mixed|null
     */
    public function getThreeDS()
    {
        return isset($this->data['threeDs']) ? $this->data['threeDs'] : null;
    }

    /**
     * @return mixed|null
     */
    public function getThreeDSMethodUrl()
    {
        return isset($this->data['threeDs']['threeDSMethodURL']) ? $this->data['threeDs']['threeDSMethodURL'] : null;
    }

    /**
     * @return mixed|null
     */
    public function getThreeDSMethodData()
    {
        return isset($this->data['threeDs']['threeDSMethodData']) ? $this->data['threeDs']['threeDSMethodData'] : null;
    }
}