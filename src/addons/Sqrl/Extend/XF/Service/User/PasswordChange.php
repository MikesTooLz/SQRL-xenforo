<?php

namespace Sqrl\Extend\XF\Service\User;

/**
 * Suppress an exception thrown when trying to notify the user via email that his password was
 * changed if the user has no email.
 */
class PasswordChange extends \XF\Service\User\PasswordChange
{
    protected function sendPasswordChangedNotice()
    {
        $user = $this->user;
        if ($user->email != '')
        {
            parent::sendPasswordChangedNotice();
        }
    }
}