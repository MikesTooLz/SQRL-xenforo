<?php

namespace Sqrl\Extend\XF\ControllerPlugin;

/**
 * We inject into this peculiar place of all to detect whether or not the user is logging in with
 * SQRL or not and determine whether that's the only allowed action. The method
 * `triggerIfTfaConfirmationRequired` is called in any place when a user is logging in to see if
 * they require two-factor. If they do, they first have to prove themselves. This gives us a good
 * place to inject because we get the user object and can redirect the program flow to our error.
 * Simple as that.
 */
class Login extends XFCP_Login
{
    public function triggerIfTfaConfirmationRequired(\XF\Entity\User $user, $callbackOrUrl)
    {
        if (\Sqrl\Util::isSqrlExclusiveUser($user) && ! \Sqrl\GlobalState::$isLoggingInWithSqrl)
        {
            $error = \XF::phrase('this_account_only_allows_logging_in_with_sqrl');
            throw $this->errorException($error);
        }
        parent::triggerIfTfaConfirmationRequired($user, $callbackOrUrl);
    }
}
