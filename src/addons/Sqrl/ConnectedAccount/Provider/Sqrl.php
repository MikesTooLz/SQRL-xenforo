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

    public function getDefaultOptions()
    {
        return [
            'hostname' => '',
            'private_hostname' => '',
            'private_port' => 55219,
            'allow_register_without_email' => '0',
        ];
    }

    public function isUsable(ConnectedAccountProvider $provider)
    {
        $addon = \XF::app()->finder('XF:Addon')->whereId('Sqrl')->fetchOne();
        if (!$addon || !$addon->active)
        {
            return false;
        }
        return parent::isUsable($provider);
    }

    public function canBeTested()
    {
        return false;
    }

    public function getOAuthConfig(ConnectedAccountProvider $provider, $redirectUri = null)
    {
        // We just want a link to our QR page
        return [
            'key' => '',
            'secret' => '',
            'scopes' => '',
            'redirect' => $redirectUri ?: $this->getRedirectUri($provider),
        ];
    }

    // We override this because AbstractProvider assumes everyone is using OAuth, for custom behavior we need something like this instead
    public function handleAuthorization(Controller $controller, ConnectedAccountProvider $provider, $returnUrl)
    {
        /** @var \XF\Session\Session $session */
        $session = \XF::app()['session.public'];

        $session->set('connectedAccountRequest', [
            'provider' => $this->providerId,
            'returnUrl' => $returnUrl,
        ]);
        $session->save();

        return $controller->message('This page is not supposed to show');
    }
}
