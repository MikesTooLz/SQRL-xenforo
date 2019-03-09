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

        if (!$session->sqrlDelete)
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
                        return $this->message(\XF::phrase('you_need_to_confirm_disassociation'));
                    }

                    $connectedAccount = $this->finder('XF:UserConnectedAccount')
                        ->where('provider', 'sqrl')
                        ->where('provider_key', $session->sqrlDelete)
                        ->fetchOne();

                    if (!$connectedAccount)
                    {
                        exit("YOES");
                    }

                    $this->performDisassociation($session->sqrlDelete, $connectedAccount);

                    unset($session->sqrlDelete);
                    $session->save();

                    if ($visitor->user_id)
                    {
                        return $this->redirect($this->buildLink('account/connected-accounts'));
                    }
                    else
                    {
                        // TODO: Make some redirect, this messages shows up as Ajax message in the top and stays in the form
                        return $this->message(\XF::phrase('your_sqrl_id_has_been_successfully_disassociated'));
                    }

                case 'replace':
                    // Do custom SQRL auth here
                    $session->set('sqrlAction', 'replace');
                    $session->save();
                    return $this->view('', 'sqrl_replace_identity', ['sqrl' => \Sqrl\Util::getSqrl()]);
                
                case 'cancel':
                    // Re-add the connection to let SQRL know we didn't mean it
                    // Get local association
                    $connectedAccount = $this->finder('XF:UserConnectedAccount')
                    ->where('provider', 'sqrl')
                    ->where('provider_key', $session->sqrlDelete)
                    ->fetchOne();

                    unset($session->sqrlDelete);
                    $session->save();

                    if (!$connectedAccount)
                    {
                        return $this->notFound(\XF::phrase('no_account_registered_with_this_sqrl_id'));
                    }

                    \Sqrl\Api::addUserToSqrl(\Sqrl\Api::addPrefix($connectedAccount->user_id), $connectedAccount->provider_key);
                    if ($visitor->user_id)
                    {
                        return $this->redirect($this->buildLink('account/connected-accounts'));
                    }
                    else
                    {
                        return $this->redirect($this->buildLink('forums'));
                    }
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
        // $session = \XF::app()['session.public'];
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

        // Deletion or replacement of SQRL ID
        if (in_array('remove', $splitStat) && $sqrlAction != 'replace')
        {
            // User deletion is two-step. First we get a 'remove' and redirect the user to
            // sqrl/disassociate, we then get redirected back here to authenticate with the new ID,
            // in that case $sqrlAction == 'replace'.
            // Just in case
            unset($session->sqrlAction);
            $session->set('sqrlDelete', $sqrlId);
            $session->save();
            return $this->redirect($this->buildLink('sqrl/disassociate'));
        }

        // Disabling of SQRL ID
        if (in_array('disabled', $splitStat))
        {
            unset($session->sqrlAction);
            $session->save();

            // SQRL ID disabled. Cannot verify, cannot anything
            if ($session->userId)
            {
                return $this->redirect($this->buildLink('account/connected-accounts'));
            }
            else
            {
                return $this->message(\XF::phrase('your_sqrl_id_has_been_successfully_disabled'));
            }
        }

        if ($sqrlAction == 'verify')
        {
            // We are verifying, in this case we expect the user to be logged in
            unset($session->sqrlAction);
            if (\Sqrl\Util::isSqrlUser($visitor) && $visitor->ConnectedAccounts['sqrl']->provider_key == $sqrlId)
            {
                $session->set('lastSqrlAuthentication', \XF::$time);
                $session->save();
                return $this->redirect($connectedAccountRequest['returnUrl']);
            }
            else
            {
                $session->save();
                return $this->message(\XF::phrase('you_have_authenticated_with_different_sqrl_id'));
            }
        }
        else if ($sqrlAction == 'replace')
        {
            $connectedAccount = $this->finder('XF:UserConnectedAccount')
                ->where('provider', 'sqrl')
                ->where('provider_key', $sqrlId)
                ->fetchOne();

            $oldSqrlId = $session->sqrlDelete;
            unset($session->sqrlAction);
            unset($session->sqrlDelete);
            $session->save();

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

            $oldConnectedAccount = $this->finder('XF:UserConnectedAccount')
            ->where('provider', 'sqrl')
            ->where('provider_key', $oldSqrlId)
            ->fetchOne();

            if ($oldConnectedAccount)
            {
                \Sqrl\Api::removeAssociation(\Sqrl\Api::addPrefix($oldConnectedAccount->user_id));
                \Sqrl\Api::addUserToSqrl(\Sqrl\Api::addPrefix($oldConnectedAccount->user_id), $sqrlId);
                // Replace the SQRL ID
                $oldConnectedAccount->set('provider_key', $sqrlId);
                $oldConnectedAccount->save();
                // Follow along with the standard authentication as the currently authenticating ID
                // is now registered to the user.
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
