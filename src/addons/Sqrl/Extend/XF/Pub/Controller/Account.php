<?php

namespace Sqrl\Extend\XF\Pub\Controller;

use XF\Mvc\ParameterBag;

class Account extends \XF\Pub\Controller\Account
{
    // Ensure that SQRL is displayed separately
    public function actionConnectedAccount()
    {
        $replyView = parent::actionConnectedAccount();
        $providers = $replyView->getParam('providers');

        if (isset($providers['sqrl']))
        {
            $sqrl = $providers['sqrl'];
            $handler = $sqrl->getHandler();
            $redirect = $this->buildLink('account/connected-account');
            $handler->handleAuthorization($this, $sqrl, $redirect);

            // Move SQRL to top by creating a new array and then merging it with the old one
            if (\XF::options()->sqrlCentricDisplayInTopOfConnectedAccounts)
            {
                $c = new \XF\Mvc\Entity\ArrayCollection(['sqrl' => $sqrl]);
                $c = $c->merge($providers);
                $replyView->setParam('providers', $c);
            }
        }

        return $replyView;
    }

    /**
     * We override the email change page to display a special one where the user does not have to
     * do password verification but rather verify their identity using SQRL.
     */
    public function actionEmail()
    {
        $visitor = \XF::visitor();

        // Only override this method if we don't have SQRL and don't have a password
        if (!\Sqrl\Util::isSqrlOnlyUser($visitor))
        {
            return parent::actionEmail();
        }

        /**
         * @var \Sqrl\ControllerPlugin\Verify $verify
         */
        $verify = $this->plugin('Sqrl:Verify');
        // Do SQRL verification first
        if (!$verify->isVerified())
        {
            return $verify->verify($this->buildLink('account/email'), 'account_details');
        }

        if ($this->isPost())
        {
            $this->emailSaveProcess($visitor)->run();

            return $this->redirect($this->buildLink('account/account-details'));
        }
        else
        {
            $viewParams = [
                'hasPassword' => true,
            ];
            $view = $this->view('XF:Account\Email', 'account_email_sqrl', $viewParams);

            return $this->addAccountWrapperParams($view, 'account_details');
        }
    }

    /**
     * This function is copy-pasted from the parent class. We had to remove the password
     * verification but the rest is the same...
     */
    protected function emailSaveProcess(\XF\Entity\User $visitor)
    {
        // Only override this method if we don't have SQRL and don't have a password
        if (!\Sqrl\Util::isSqrlOnlyUser($visitor))
        {
            return parent::emailSaveProcess($visitor);
        }

        $form = $this->formAction();

        $input = $this->filter([
            'email' => 'str',
        ]);

        if ($input['email'] != $visitor->email)
        {
            /** @var \XF\Service\User\EmailChange $emailChange */
            $emailChange = $this->service('XF:User\EmailChange', $visitor, $input['email']);

            $form->validate(function(\XF\Mvc\FormAction $form) use ($visitor, $input, $emailChange)
            {
                if (!$emailChange->isValid($changeError))
                {
                    $form->logError($changeError, 'email');
                }
                else if (!$emailChange->canChangeEmail($error))
                {
                    if (!$error)
                    {
                        $error = \XF::phrase('your_email_may_not_be_changed_at_this_time');
                    }
                    $form->logError($error, 'email');
                }
            });
            $form->apply(function() use ($emailChange)
            {
                $emailChange->save();
            });
        }

        return $form;
    }


    /**
     * We override the password change page to display a special one where the user does not have
     * to do password verification but rather verify their identity using SQRL.
     */
    public function actionSecurity()
    {
        $lastSqrlAuthentication = $this->session()->get('lastSqrlAuthentication');
        $visitor = \XF::visitor();
        $reply = parent::actionSecurity();

        if (!\Sqrl\Util::isSqrlOnlyUser($visitor))
        {
            return $reply;
        }

        /**
         * @var \Sqrl\ControllerPlugin\Verify $verify
         */
        $verify = $this->plugin('Sqrl:Verify');
        // Do SQRL verification first
        if (!$verify->isVerified())
        {
            return $verify->verify($this->buildLink('account/security'), 'security');
        }

        // This ensures a template modification renders without 'old password'
        if (!$this->isPost() && \Sqrl\Util::isSqrlOnlyUser($visitor))
        {
            $reply->setParam('sqrlAuthentication', true);
            $reply->setParam('hasPassword', true);
        }

        if ($this->isPost() && $lastSqrlAuthentication)
        {
            $this->session()->set('lastSqrlAuthentication', $lastSqrlAuthentication);
            $this->session()->save();
        }

        return $reply;
    }

    protected function setupPasswordChange()
    {
        $visitor = \XF::visitor();
        if (!\Sqrl\Util::isSqrlOnlyUser($visitor))
        {
            return parent::setupPasswordChange();
        }
        $input = $this->filter([
            'password' => 'str',
            'password_confirm' => 'str'
        ]);

        if ($input['password'] !== $input['password_confirm'])
        {
            throw $this->errorException(\XF::phrase('passwords_did_not_match'));
        }

        return $this->service('XF:User\PasswordChange', $visitor, $input['password']);
    }

    /**
     * We want to prevent users from removing their SQRL ID in case they have no password, no
     * email and no other connected accounts to avoid lock-out.
     */
    public function actionConnectedAccountDisassociate(ParameterBag $params)
    {
        $this->assertPostOnly();

        $visitor = \XF::visitor();
        $auth = $visitor->Auth->getAuthenticationHandler();
        if (!$auth)
        {
            return $this->noPermission();
        }
        
        $connectedAccounts = $visitor->ConnectedAccounts;
        if ($this->filter('disassociate', 'bool')
         && !$auth->hasPassword()
         && $visitor->email == '' 
         && count($connectedAccounts) == 1
        )
        {
            throw $this->errorException(\XF::phrase('cannot_remove_last_connected_account_without_password'));
        }
        return parent::actionConnectedAccountDisassociate($params);
    }

    public function actionRemoveEmail(ParameterBag $params)
    {
        if ($this->filter('confirm', 'bool'))
        {
            $this->assertPostOnly();

            $visitor = \XF::visitor();
            if (!$visitor->canRemoveEmail())
            {
                return $this->noPermission();
            }
            // We want to overrule any email validation if the user removes his/her email
            \Sqrl\GlobalState::$allowRegisterWithoutEmail = true;
            $visitor->set('email', '');
            if ($visitor->user_state == 'email_confirm' || $visitor->user_state == 'email_confirm_edit')
            {
                $visitor->set('user_state', 'valid');
            }
            $visitor->save();
            return $this->redirect('account/account-details');
        }
        else
        {
            return $this->view('Sqrl:Account/RemoveEmail', 'remove_email_confirm');
        }
    }

    public function actionRemovePassword(ParameterBag $params)
    {
        if ($this->filter('confirm', 'bool'))
        {
            $this->assertPostOnly();

            $visitor = \XF::visitor();
            if (!$visitor->canRemovePassword())
            {
                return $this->noPermission();
            }
            $visitor->Auth->setNoPassword();
            $visitor->Auth->save();
            $this->plugin('XF:Login')->handleVisitorPasswordChange();
            $this->session()->save();
            return $this->redirect('account/security');
        }
        else
        {
            return $this->view('Sqrl:Account/RemovePassword', 'remove_password_confirm');
        }
    }
}