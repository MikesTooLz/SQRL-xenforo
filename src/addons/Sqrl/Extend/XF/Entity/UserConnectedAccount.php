<?php

namespace Sqrl\Extend\XF\Entity;

class UserConnectedAccount extends \XF\Entity\UserConnectedAccount
{
    protected function _preDelete()
    {
        if ($this->provider == 'sqrl')
        {
            \Sqrl\Api::removeAssociation(\Sqrl\Api::addPrefix($this->user_id));
        }

        parent::_preDelete();
    }
}