<?php

namespace Sqrl;

use XF\Entity\ConnectedAccountProvider;
use XF\ConnectedAccount\Storage\StorageState as BaseStorageState;
use XF\Entity\User;

class StorageState extends BaseStorageState
{
    public function __construct(ConnectedAccountProvider $provider, User $user)
    {
        parent::__construct($provider, $user);
    }

    public function clearToken()
    {
        \Sqrl\Api::removeAssociation($this->user->user_id);
        parent::clearToken();
    }
}