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

        if (in_array('deleting', $splitStat))
        {
            // Get local association
            $connectedAccount = $this->finder('XF:UserConnectedAccount')
                ->where('provider', 'sqrl')
                ->where('provider_key', $sqrlId)
                ->fetchOne();
            $userId = $connectedAccount->user_id;
            $user = $this->assertRecordExists('XF:User', $userId, null, 'requested_user_not_found');
            // We have extended this class to actually delete the connected account in SQRL so we
            // don't have to do it explicitly here.
            $connectedAccount->delete();

            $provider = $this->assertRecordExists('XF:ConnectedAccountProvider', 'sqrl');
            $handler = $provider->getHandler();
            $storageState = $handler->getStorageState($provider, $user);
            $storageState->clearToken();

            unset($session->sqrlAction);
            $session->save();

            if ($visitor->user_id)
            {
                return $this->redirect($this->buildLink('account/connected-accounts'));
            }
            else
            {
                return $this->message(\XF::phrase('your_sqrl_id_has_been_successfully_disassociated'));
            }
        }

        if (in_array('disabled', $splitStat))
        {
            unset($session->sqrlAction);
            $session->save();

            // SQRL ID disabled. Cannot verify, cannot anything
            return $this->message(\XF::phrase('your_sqrl_id_has_been_successfully_disabled'));
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
