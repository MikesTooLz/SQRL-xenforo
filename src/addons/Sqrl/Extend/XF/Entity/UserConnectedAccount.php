<?php

namespace Sqrl\Extend\XF\Entity;

class UserConnectedAccount extends \XF\Entity\UserConnectedAccount
{
    protected function _preDelete()
    {
        if ($this->provider == 'sqrl')
        {
            \Sqrl\Api::removeSqrlAccount($this->provider_key);
        }

        parent::_preDelete();
    }
}