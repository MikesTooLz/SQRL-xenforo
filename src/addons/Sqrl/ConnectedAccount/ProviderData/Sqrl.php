<?php

namespace Sqrl\ConnectedAccount\ProviderData;

use XF\ConnectedAccount\ProviderData\AbstractProviderData;
use XF\ConnectedAccount\Storage\StorageState;

class Sqrl extends AbstractProviderData
{
    public function getDefaultEndpoint()
    {
        return null;
    }

    public function getProviderKey()
    {
        return $this->storageState->retrieveToken()->getAccessToken();
    }

    public function getUsername()
    {
        return null;
    }

    public function getEmail()
    {
        return null;
    }

    public function getDob()
    {
        return null;
    }

    public function getProfileLink()
    {
        return null;
    }

    public function getAvatarUrl()
    {
        return null;
    }

    public function getExtraData()
    {
        return ['token' => ''];
    }
}
