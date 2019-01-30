<?php

namespace Sqrl\Extend\XF\Pub\Controller;

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
            return $verify->verify($this->buildLink('account/email'));
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
            return $verify->verify($this->buildLink('account/security'));
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