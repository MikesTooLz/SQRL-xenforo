<?php

namespace Sqrl\Extend\XF\Pub\Controller;

class Login extends \XF\Pub\Controller\Login
{
    // Ensure that SQRL is displayed separately
    public function actionIndex()
    {
        $replyView = parent::actionIndex();
        $sqrl = \Sqrl\Util::separateSqrlFromProviders($replyView);

        if ($sqrl)
        {
            $handler = $sqrl->getHandler();
            $redirect = $this->getDynamicRedirect();
            $handler->handleAuthorization($this, $sqrl, $redirect);
        }

        return $replyView;
    }

    // Ensure that SQRL is displayed separately
    public function actionLogin()
    {
        $replyView = parent::actionLogin();
        $sqrl = \Sqrl\Util::separateSqrlFromProviders($replyView);

        if ($sqrl)
        {
            $handler = $sqrl->getHandler();
            $redirect = $this->getDynamicRedirect();
            $handler->handleAuthorization($this, $sqrl, $redirect);
        }

        return $replyView;
    }
}
