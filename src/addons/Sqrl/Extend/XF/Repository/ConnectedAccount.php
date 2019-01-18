<?php

namespace Sqrl\Extend\XF\Repository;

use XF\ConnectedAccount\ProviderData\AbstractProviderData;

class ConnectedAccount extends \XF\Repository\ConnectedAccount
{
	public function associateConnectedAccountWithUser(\XF\Entity\User $user, AbstractProviderData $providerData)
	{
		$this->db()->beginTransaction();
		$connectedAccount = parent::associateConnectedAccountWithUser($user, $providerData);

		\Sqrl\Api::addUserToSqrl($user->user_id, $providerData->extra_data['token']);

		$this->db()->commit();

		return $connectedAccount;
	}
}
