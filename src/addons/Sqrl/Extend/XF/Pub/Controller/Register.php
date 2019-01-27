<?php

namespace Sqrl\Extend\XF\Pub\Controller;

class Register extends \XF\Pub\Controller\Register
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

    protected function finalizeRegistration(\XF\Entity\User $user)
    {
        // We know this variable is only set if we are already registering with SQRL
        if (\Sqrl\GlobalState::$allowRegisterWithoutEmail)
        {
            // Set the user to email validated despite the missing email
            $user->set('user_state', 'valid');
            $user->save();
        }
        return parent::finalizeRegistration($user);
    }
}
