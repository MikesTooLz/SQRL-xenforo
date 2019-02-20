<?php

namespace Sqrl\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

// Friendly URLs enabled
// WebServerAuthURL = https://steve.com/sqrl/authenticate/?token=%s
// Friendly URLs disabled
// WebServerAuthURL = https://steve.com/?sqrl/authenticate/&token=%s

class Sqrl extends AbstractController
{
    public function actionAuthenticate(ParameterBag $params)
    {
        $visitor = \XF::visitor();

        /** @var \XF\Session\Session $session */
        $session = \XF::app()['session.public'];

        $connectedAccountRequest = $session->get('connectedAccountRequest');
        $connectedAccountRequest['tokenStored'] = true;

        $token = $this->filter('token', 'str');
        // Check if we have a user
        $cps = \Sqrl\Api::cps($token);
        $sqrlId = $cps['user'];
        $splitStat = explode(',', $cps['stat']);

        // For verification of identity during a session
        $sqrlAction = $session->get('sqrlAction');

        if (in_array('disabled', $splitStat))
        {
            // SQRL ID disabled. Cannot verify, cannot anything
            return $this->message('Your SQRL ID has been successfully disabled.');
        }

        if ($sqrlAction == 'verify')
        {
            $this->session()->set('sqrlAction', '');
            if (\Sqrl\Util::isSqrlUser($visitor) && $visitor->ConnectedAccounts['sqrl']->provider_key == $sqrlId)
            {
                $this->session()->set('lastSqrlAuthentication', \XF::$time);
                $this->session()->save();
                return $this->redirect($connectedAccountRequest['returnUrl']);
            }
            else
            {
                $this->session()->save();
                return $this->message(\XF::phrase('you_have_authenticated_with_different_sqrl_id'));
            }
        }

        // Store the SQRL ID so XenForo knows about it
        $provider = $this->finder('XF:ConnectedAccountProvider')
            ->whereId('sqrl')
            ->fetchOne();

        $handler = $provider->getHandler();
        $storageState = $handler->getStorageState($provider, $visitor);
        $tokenObj = new \OAuth\OAuth2\Token\StdOAuth2Token();
        $tokenObj->setAccessToken($sqrlId);
        $storageState->storeToken($tokenObj);
        $storageState->storeProviderData([
            'properties' => $splitStat,
        ]);

        $session->set('connectedAccountRequest', $connectedAccountRequest);
        $session->save();

        // Redirect back to connected-accounts to let it know that we succeeded
        $redirect = \XF::app()->router('public')->buildLink('register/connected-accounts', $provider);

        return $this->redirect($redirect);
    }
}
