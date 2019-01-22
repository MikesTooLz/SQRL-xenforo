<?php

namespace Sqrl\Extend\XF\Pub\Controller;

class Register extends \XF\Pub\Controller\Register
{
    public function actionIndex()
    {
        $replyView = parent::actionIndex();

        $providers = $replyView->getParam('providers');
        // Separate out the SQRL provider and pass it separately to our template
        if (isset($providers['sqrl']))
        {
            $sqrl = $providers['sqrl'];
            $replyView->setParam('sqrl', $sqrl);
            unset($providers['sqrl']);

            // Also setup session
            $handler = $sqrl->getHandler();
            $redirect = $this->getDynamicRedirect();
            $handler->handleAuthorization($this, $sqrl, $redirect);
        }

        return $replyView;
    }
}
