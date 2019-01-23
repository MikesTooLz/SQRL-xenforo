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
}