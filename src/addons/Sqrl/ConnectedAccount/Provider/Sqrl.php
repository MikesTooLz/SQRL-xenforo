<?php

namespace Sqrl\ConnectedAccount\Provider;

use XF\ConnectedAccount\Http\HttpResponseException;
use XF\ConnectedAccount\Provider\AbstractProvider;
use XF\Entity\ConnectedAccountProvider;
use XF\Mvc\Controller;
use SQRL\SqrlStore;
use XF\Entity\User;

class Sqrl extends AbstractProvider
{
	public function getOAuthServiceName()
	{
		return 'Sqrl';
	}

	public function getProviderDataClass()
	{
		return 'Sqrl:ProviderData\\' . $this->getOAuthServiceName();
	}

	/**
	 * @param ConnectedAccountProvider $provider
	 * @param User $user
	 *
	 * @return StorageState
	 */
	public function getStorageState(ConnectedAccountProvider $provider, User $user)
	{
		return new \Sqrl\StorageState($provider, $user);
	}

	public function getDefaultOptions()
	{
		return [
			'public_query_url' => '',
			'private_query_url' => '',
			'private_query_port' => '',
			'query_hostname' => '',
		];
	}

	public function getOAuthConfig(ConnectedAccountProvider $provider, $redirectUri = null)
	{
		// We just want a link to our QR page
		return [
			'key' => '',
			'secret' => '',
			'scopes' => '',
			'redirect' => $redirectUri ?: $this->getRedirectUri($provider),
			// 'redirect' => \XF::app()->router()->buildLink('sqrl/authenticate'),
		];
	}

	// We override this because AbstractProvider assumes everyone is using OAuth, for custom behavior we need something like this instead
	public function handleAuthorization(Controller $controller, ConnectedAccountProvider $provider, $returnUrl)
	{
		$viewParams = [
			'queryUrl' => $provider->options['public_query_url'],
			'hostname' => $provider->options['query_hostname'],
		];

		/** @var \XF\Session\Session $session */
		$session = \XF::app()['session.public'];

		$session->set('connectedAccountRequest', [
			'provider' => $this->providerId,
			'returnUrl' => $returnUrl,
			// 'test' => $this->testMode,
		]);
		$session->save();

		return $controller->view('Sqrl\ViewQR', 'sqrl_view_qr_code', $viewParams);
	}

	public function renderAssociated(ConnectedAccountProvider $provider, \XF\Entity\User $user)
	{
		$data = \Sqrl\Api::getAssociationsByUserId($user->user_id);
		return \XF::app()->templater()->renderTemplate('public:connected_account_associated_' . $provider->provider_id, [
			'provider' => $provider,
			'user' => $user,
			'providerData' => $provider->getUserInfo($user),
			'connectedAccounts' => $user->Profile->connected_accounts,
			'sqrlData' => $data,
		]);
	}
}
