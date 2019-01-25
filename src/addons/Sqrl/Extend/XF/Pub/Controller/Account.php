<?php

namespace Sqrl\Extend\XF\Pub\Controller;

class Account extends \XF\Pub\Controller\Account
{
    public function actionConnectedAccount()
    {
        \XF::dump(\Sqrl\Api::getAssociationsByUserId(\XF::visitor()->user_id));
        $replyView = parent::actionConnectedAccount();
        $providers = $replyView->getParam('providers');

        if (isset($providers['sqrl']))
        {
            $sqrl = $providers['sqrl'];
            $handler = $sqrl->getHandler();
            // $redirect = $this->getDynamicRedirect();
            $redirect = $this->router()->buildLink('account/connected-account');
            $handler->handleAuthorization($this, $sqrl, $redirect);
        }

        return $replyView;
    }
}