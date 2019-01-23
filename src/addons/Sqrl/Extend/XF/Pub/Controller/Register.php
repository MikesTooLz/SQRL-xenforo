<?php

namespace Sqrl\Extend\XF\Pub\Controller;

class Register extends \XF\Pub\Controller\Register
{
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

    /**
     * Seemingly the simplest way to implement our 'allow register without email' function is to
     * override this standard action, check if we allow registering without email, set a global
     * value and check that value in our extension of \XF\Entity\User. Kind of a hack, but it
     * works.
     */
    public function actionConnectedAccountRegister(\XF\Mvc\ParameterBag $params)
    {
        if ($params->provider_id == 'sqrl')
        {
            $provider = $this->assertProviderExists($params->provider_id);
            $handler = $provider->getHandler();
            \Sqrl\GlobalState::$allowRegisterWithoutEmail = intval($provider->options['allow_register_without_email']);
        }
        return parent::actionConnectedAccountRegister($params);
    }
}
