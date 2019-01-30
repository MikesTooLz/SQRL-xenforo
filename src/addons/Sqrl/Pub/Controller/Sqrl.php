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
    /*
    public function actionRemovePassword(ParameterBag $params)
    {
        $visitor = \XF::visitor();
        $visitor->Auth->setNoPassword();
        $visitor->Auth->save();
        $this->plugin('XF:Login')->handleVisitorPasswordChange();
        $this->session()->save();
        return $this->message('Done');
    }
    */

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

        // For verification of identity during a session
        $sqrlAction = $session->get('sqrlAction');
        if ($sqrlAction == 'verify')
        {
            $this->session()->set('sqrlAction', '');
            if (isset($visitor->ConnectedAccounts['sqrl']) && $visitor->ConnectedAccounts['sqrl']->provider_key == $sqrlId)
            {
                $this->session()->set('lastSqrlAuthentication', \XF::$time);
                $this->session()->save();
                return $this->redirect($connectedAccountRequest['returnUrl']);
            }
            else
            {
                $this->session()->save();
                return $this->message("You are using the wrong SQRL id.");
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

        $session->set('connectedAccountRequest', $connectedAccountRequest);
        $session->save();

        // Redirect back to connected-accounts to let it know that we succeeded
        $redirect = \XF::app()->router('public')->buildLink('register/connected-accounts', $provider);

        return $this->redirect($redirect);
    }
}
