<?php

namespace Sqrl\Extend\XF\Repository;

use XF\ConnectedAccount\ProviderData\AbstractProviderData;

class ConnectedAccount extends XFCP_ConnectedAccount
{
    public function associateConnectedAccountWithUser(\XF\Entity\User $user, AbstractProviderData $providerData)
    {
        $this->db()->beginTransaction();
        $connectedAccount = parent::associateConnectedAccountWithUser($user, $providerData);

        \Sqrl\Api::addUserToSqrl(\Sqrl\Api::addPrefix($user->user_id), $providerData->provider_key);

        $this->db()->commit();

        return $connectedAccount;
    }
}
