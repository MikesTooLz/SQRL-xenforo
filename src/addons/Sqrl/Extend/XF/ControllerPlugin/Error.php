<?php

namespace Sqrl\Extend\XF\ControllerPlugin;

class Error extends \XF\ControllerPlugin\Error
{
    public function actionRegistrationRequired()
    {
        $view = parent::actionRegistrationRequired();
        $sqrl = \Sqrl\Util::separateSqrlFromProviders($view);
        if ($sqrl)
        {
            $handler = $sqrl->getHandler();
            $redirect = $this->controller->getDynamicRedirect();
            $handler->handleAuthorization($this->controller, $sqrl, $redirect);
        }
        return $view;
    }
}