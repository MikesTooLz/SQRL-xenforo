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
    public function performDisassociation($sqrlId, \XF\Entity\UserConnectedAccount $connectedAccount = null)
    {
        $db = $this->app()->db();

        $db->beginTransaction();

        if ($connectedAccount)
        {
            $userId = $connectedAccount->user_id;
            $user = $this->assertRecordExists('XF:User', $userId, null, 'requested_user_not_found');
            // We have extended this class to actually delete the connected account in SQRL so we
            // don't have to do it explicitly here.
            $connectedAccount->delete();

            $provider = $this->assertRecordExists('XF:ConnectedAccountProvider', 'sqrl');
            $handler = $provider->getHandler();
            $storageState = $handler->getStorageState($provider, $user);
            $storageState->clearToken();
        }

        \Sqrl\Api::removeSqrlAccount($sqrlId);

        $db->commit();
    }

    public function actionDisassociate(ParameterBag $params)
    {
        /** @var \XF\Session\Session $session */
        // $session = \XF::app()['session.public'];
        $session = $this->session();

        $visitor = \XF::visitor();

        $expireTime = \XF::$time - 600;
        if (!$visitor->ConnectedAccounts['sqrl'] || $visitor->ConnectedAccounts['sqrl']->extra_data['removeRequested'] <= $expireTime)
        {
            return $this->noPermission();
        }

        // We are deleting, we cannot be sure the user is logged in already
        if ($this->isPost())
        {
            switch ($this->filter('action', 'str'))
            {
                case 'disassociate':
                    if (!$this->filter('confirm_disassociate', 'bool'))
                    {
                        return $this->error(\XF::phrase('you_need_to_confirm_disassociation'));
                    }
                    if (!$visitor->Auth->getAuthenticationHandler()->hasPassword())
                    {
                        return $this->error(\XF::phrase('cannot_remove_sqrl_id_account_without_password'));
                    }

                    $connectedAccount = $visitor->ConnectedAccounts['sqrl'];

                    $this->performDisassociation($connectedAccount->provider_key, $connectedAccount);

                    return $this->redirect($this->buildLink('account/connected-accounts'));

                case 'replace':
                    // Do custom SQRL auth here
                    $session->set('sqrlAction', 'replace');
                    $session->save();
                    return $this->view('', 'sqrl_replace_identity', ['sqrl' => \Sqrl\Util::getSqrl()]);
                
                case 'cancel':
                    $connectedAccount = $visitor->ConnectedAccounts['sqrl'];
                    $extraData = $connectedAccount->extra_data;
                    $extraData['removeRequested'] = 0;
                    $connectedAccount->extra_data = $extraData;
                    $connectedAccount->save();
                    // Re-add the connection to let SQRL know we didn't mean it
                    // Get local association
                    return $this->redirect($this->buildLink('account/connected-accounts'));
                default:
                    return $this->notFound();
            }
        }
        
        return $this->view('', 'sqrl_delete_or_replace', []);
    }

    // Entry point for the SSP API
    public function actionAuthenticate(ParameterBag $params)
    {
        $visitor = \XF::visitor();

        /** @var \XF\Session\Session $session */
        $session = $this->session();

        $connectedAccountRequest = $session->get('connectedAccountRequest');
        $connectedAccountRequest['tokenStored'] = true;

        $token = $this->filter('token', 'str');
        // Check if we have a user
        $cps = \Sqrl\Api::cps($token);
        $sqrlId = $cps['user'];
        $splitStat = explode(',', $cps['stat']);

        // For verification of identity during a session
        $sqrlAction = $session->get('sqrlAction');
        unset($session->sqrlAction);
        $session->save();

        // If the user requested a delete, we want to show them the disassociate dialog after succesful login
        if (in_array('remove', $splitStat))
        {
            $connectedAccountRequest['returnUrl'] = $this->buildLink('sqrl/disassociate');
        }

        if ($sqrlAction == 'verify')
        {
            // We are verifying, in this case we expect the user to be logged in
            if (\Sqrl\Util::isSqrlUser($visitor) && $visitor->ConnectedAccounts['sqrl']->provider_key == $sqrlId)
            {
                $session->set('lastSqrlAuthentication', \XF::$time);
                $session->save();
                return $this->redirect($connectedAccountRequest['returnUrl']);
            }
            else
            {
                return $this->message(\XF::phrase('you_have_authenticated_with_different_sqrl_id'));
            }
        }
        else if ($sqrlAction == 'replace')
        {
            $connectedAccount = $this->finder('XF:UserConnectedAccount')
                ->where('provider', 'sqrl')
                ->where('provider_key', $sqrlId)
                ->fetchOne();

            $oldConnectedAccount = $visitor->ConnectedAccounts['sqrl'];

            if ($connectedAccount)
            {
                if ($connectedAccount->user_id == $session->userId)
                {
                    return $this->error(\XF::phrase('this_account_is_already_associated_with_you'));
                }
                else
                {
                    return $this->error(\XF::phrase('this_account_is_already_associated_with_another_member'));
                }
            }

            if ($oldConnectedAccount)
            {
                \Sqrl\Api::removeAssociation(\Sqrl\Api::addPrefix($oldConnectedAccount->user_id));
                \Sqrl\Api::addUserToSqrl(\Sqrl\Api::addPrefix($oldConnectedAccount->user_id), $sqrlId);
                // Replace the SQRL ID
                $oldConnectedAccount->set('provider_key', $sqrlId);

                $extraData = $oldConnectedAccount->extra_data;
                $extraData['removeRequested'] = 0;
                $oldConnectedAccount->extra_data = $extraData;

                $oldConnectedAccount->save();
                // Follow along with the standard authentication as the currently authenticating ID
                // is now registered to the user.
            }
        }

        // Store the SQRL ID so XenForo knows about it
        $provider = \Sqrl\Util::getSqrl();

        $handler = $provider->getHandler();
        $storageState = $handler->getStorageState($provider, $visitor);
        $tokenObj = new \OAuth\OAuth2\Token\StdOAuth2Token();
        $tokenObj->setAccessToken($sqrlId);
        $storageState->storeToken($tokenObj);

        $providerData = $handler->getProviderData($storageState);
        $connectedRepo = $this->repository('XF:ConnectedAccount');
        $userConnected = $connectedRepo->getUserConnectedAccountFromProviderData($providerData);

        $extraData = $userConnected ? $userConnected->extra_data : [];
        if (!in_array('remove', $splitStat))
        {
            // We don't want to override the existing SQRL properties if the user requested deletion
            $extraData['properties'] = $splitStat;
        }
        $extraData['removeRequested'] = in_array('remove', $splitStat) ? \XF::$time : 0;

        $storageState->storeProviderData($extraData);

        // Prevent the rest of stuff by redirecting back to the connected-accounts page and
        // manually saving the latest properties.
        if ($sqrlAction == 'talk')
        {
            if (!$userConnected || $visitor->user_id != $userConnected->user_id)
            {
                return $this->error(\XF::phrase('you_have_authenticated_with_different_sqrl_id'));
            }

            $userConnected->extra_data = $providerData->extra_data;
            $userConnected->save();

            if ($connectedAccountRequest['returnUrl'])
            {
                return $this->redirect($connectedAccountRequest['returnUrl']);
            }
            return $this->redirect($this->buildLink('account/connected-accounts'));
        }

        $session->set('connectedAccountRequest', $connectedAccountRequest);
        $session->save();

        // Redirect back to connected-accounts to let it know that we succeeded
        $redirect = \XF::app()->router('public')->buildLink('register/connected-accounts', $provider);

        return $this->redirect($redirect);
    }
}
