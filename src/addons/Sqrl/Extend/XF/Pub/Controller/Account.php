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
            $redirect = $this->router()->buildLink('account/connected-account');
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
}