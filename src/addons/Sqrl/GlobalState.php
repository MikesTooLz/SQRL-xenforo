<?php

namespace Sqrl;

/**
 * This was in one situation the simplest way to pass on a flag to an override of built-in
 * functionality. Read more about it in \Sqrl\Extend\XF\Pub\Controller\Register.
 */
abstract class GlobalState
{
    public static $allowRegisterWithoutEmail = false;

    public static $isLoggingInWithSqrl = false;
}
