<?php

namespace Sqrl\Extend\XF\Pub\Controller;

class Account extends \XF\Pub\Controller\Account
{
    public function actionConnectedAccount()
    {
        $replyView = parent::actionConnectedAccount();
        $providers = $replyView->getParam('providers');

        if (isset($providers['sqrl']))
        {
            $sqrl = $providers['sqrl'];
            $handler = $sqrl->getHandler();
            // $redirect = $this->getDynamicRedirect();
            $redirect = $this->buildLink('account/connected-account');
            $handler->handleAuthorization($this, $sqrl, $redirect);
        }

        return $replyView;
    }

    /**
     * This function should never be necessary. However, to prevent any sort of desync between the
     * SQRL DB and the XenForo DB, we ensure that the association is properly deleted no matter
     * what when the user tries to delete it.
     */
    public function actionConnectedAccountDisassociate(\XF\Mvc\ParameterBag $params)
    {
        $this->assertPostOnly();

        $visitor = \XF::visitor();
        $auth = $visitor->Auth->getAuthenticationHandler();
        if (!$auth)
        {
            return $this->noPermission();
        }
        \Sqrl\Api::removeAssociation($visitor->user_id);
        return parent::actionConnectedAccountDisassociate($params);
    }

    public function actionEmail()
    {
        $visitor = \XF::visitor();

        // Only override this method if we don't have SQRL and don't have a password
        if (!\Sqrl\Util::isSqrlOnlyUser($visitor))
        {
            return parent::actionEmail();
        }

        // Do SQRL verification first
        if ($this->session()->get('lastSqrlAuthentication') < \XF::$time - 5 * 60)
        {
            $sqrl = $this->assertProviderExists('sqrl');
            $handler = $sqrl->getHandler();
            $returnUrl = $this->buildLink('account/email');
            $handler->handleAuthorization($this, $sqrl, $returnUrl);
            $this->session()->set('sqrlAction', 'verify');
            $this->session()->save();
            $view = $this->view('XF:Account\SqrlVerify', 'sqrl_verify', ['sqrl' => $sqrl]);
            return $this->addAccountWrapperParams($view, 'account_details');
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

    public function actionSecurity()
    {
        $lastSqrlAuthentication = $this->session()->get('lastSqrlAuthentication');
        $visitor = \XF::visitor();
        $reply = parent::actionSecurity();

        if (!\Sqrl\Util::isSqrlOnlyUser($visitor))
        {
            return $reply;
        }

        // Do SQRL verification first
        if ($this->session()->get('lastSqrlAuthentication') < \XF::$time - 5 * 60)
        {
            $sqrl = $this->assertProviderExists('sqrl');
            $handler = $sqrl->getHandler();
            $returnUrl = $this->buildLink('account/security');
            $handler->handleAuthorization($this, $sqrl, $returnUrl);
            $this->session()->set('sqrlAction', 'verify');
            $this->session()->save();
            $view = $this->view('XF:Account\SqrlVerify', 'sqrl_verify', ['sqrl' => $sqrl]);
            return $this->addAccountWrapperParams($view, 'account_security');
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

}