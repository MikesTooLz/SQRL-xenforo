<?php

namespace Sqrl\ConnectedAccount\ProviderData;

use XF\ConnectedAccount\ProviderData\AbstractProviderData;

class Sqrl extends AbstractProviderData
{
	public function getDefaultEndpoint()
	{
		return null;
	}

	public function getProviderKey()
	{
		return 'sqrl_provider_key';
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
}
