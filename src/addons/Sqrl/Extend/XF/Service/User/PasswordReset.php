<?php

namespace Sqrl\Extend\XF\Service\User;

class PasswordReset extends XFCP_PasswordReset
{
    public function canTriggerConfirmation(&$error = null)
    {
        if (\Sqrl\Util::isHardlockedUser($this->user))
        {
            $error = \XF::phrase('this_account_is_hardlocked_using_sqrl_no_recovery_possible');
            return false;
        }
        return parent::canTriggerConfirmation($error);
    }
}
