<?php

namespace Sqrl\Extend\XF\Entity;

class User extends \XF\Entity\User
{
    protected function verifyEmail(&$email)
    {
        if (\Sqrl\GlobalState::$allowRegisterWithoutEmail && $email == '')
        {
            return true;
        }
        return parent::verifyEmail($email);
    }

    public function canRemoveEmail()
    {
        return \Sqrl\Util::isEmailOptional()
            && \Sqrl\Util::isSqrlUser($this)
            && $this->email != '';
    }

    public function canRemovePassword()
    {
        return \Sqrl\Util::isEnabled()
            && \Sqrl\Util::isSqrlUser($this)
            && $this->Auth->getAuthenticationHandler()->hasPassword();
    }

    public function isSqrlHardlocked()
    {
        return \Sqrl\Util::isHardlockedUser($this);
    }
}
