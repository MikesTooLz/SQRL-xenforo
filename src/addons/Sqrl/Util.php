<?php

namespace Sqrl;

abstract class Util
{
    public static function separateSqrlFromProviders($replyView)
    {
        if (!($replyView instanceof \XF\Mvc\Reply\View))
        {
            return null;
        }
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

    public static function isSqrlUser(\XF\Entity\User $user)
    {
        return self::getSqrl()->isAssociated($user);
    }

    public static function getUserSqrlProperties(\XF\Entity\User $user)
    {
        if (!self::isSqrlUser($user))
        {
            return [];
        }
        $extraData = $user->ConnectedAccounts['sqrl']->extra_data;
        if (!isset($extraData['properties']))
        {
            return [];
        }
        return $extraData['properties'];
    }

    public static function isSqrlExclusiveUser(\XF\Entity\User $user)
    {
        return \in_array('sqrlonly', self::getUserSqrlProperties($user));
    }

    public static function mustVerifyWithSqrl(\XF\Entity\User $user)
    {
        $auth = $user->Auth->getAuthenticationHandler();

        // Only override this method if we don't have SQRL and don't have a password
        if (self::isSqrlUser($user) && (!$auth || !$auth->hasPassword() || self::isSqrlExclusiveUser($user)))
        {
            return true;
        }
        return false;
    }

    public static function getSqrl()
    {
        return \XF::app()->finder('XF:ConnectedAccountProvider')->whereId('sqrl')->fetchOne();
    }

    public static function isEnabled()
    {
        $sqrl = self::getSqrl();
        return $sqrl && $sqrl->isUsable();
    }    

    public static function isEmailOptional()
    {
        return self::isEnabled()
            && self::getSqrl()->options['allow_register_without_email'] == '1';
    }
}