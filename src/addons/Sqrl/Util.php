<?php

namespace Sqrl;

abstract class Util
{
    public static function separateSqrlFromProviders(\XF\Mvc\Reply\View $replyView)
    {
        $providers = $replyView->getParam('providers');
        // Separate out the SQRL provider and pass it separately to our template
        if (isset($providers['sqrl']))
        {
            $sqrl = $providers['sqrl'];
            $replyView->setParam('sqrl', $sqrl);
            unset($providers['sqrl']);
            return $sqrl;
        }
        return null;
    }

    public static function isSqrlOnlyUser(\XF\Entity\User $user = null)
    {
        if ($user == null)
        {
            $user = \XF::visitor();
        }

        $auth = $user->Auth->getAuthenticationHandler();

        // Only override this method if we don't have SQRL and don't have a password
        if (isset($user->ConnectedAccounts['sqrl']) && (!$auth || !$auth->hasPassword()))
        {
            return true;
        }
        return false;
    }
}