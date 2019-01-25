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
}