<?php

namespace Sqrl\Extend\XF\Entity;

class ConnectedAccountProvider extends \XF\Entity\ConnectedAccountProvider
{
    public function isAssociated(\XF\Entity\User $user)
    {
        if ($this->provider_id == 'sqrl')
        {
            $apiResult = \Sqrl\Api::getAssociationsByUserId($user->user_id);
            return count($apiResult) > 0;
        }
        return parent::isAssociated($user);
    }
}
