<?php

namespace Sqrl\ControllerPlugin;

use XF\Mvc\Reply\View;

class Verify extends \XF\ControllerPlugin\AbstractPlugin
{
    public function isVerified()
    {
        return $this->session()->get('lastSqrlAuthentication') > \XF::$time - 5 * 60;
    }

    public function verify($returnUrl)
    {
        $sqrl = $this->assertProviderExists('sqrl');
        $handler = $sqrl->getHandler();
        $handler->handleAuthorization($this->controller, $sqrl, $returnUrl);
        $this->session()->set('sqrlAction', 'verify');
        $this->session()->save();
        $view = $this->view('XF:Account\SqrlVerify', 'sqrl_verify', ['sqrl' => $sqrl]);
        return $this->addAccountWrapperParams($view, 'account_details');
    }

    // Method stolen from the \XF\Pub\Controller\Account
    protected function assertProviderExists($id, $with = null, $phraseKey = null)
    {
        return $this->assertRecordExists('XF:ConnectedAccountProvider', $id, $with, $phraseKey);
    }

    protected function addAccountWrapperParams(View $view, $selected)
    {
        $view->setParam('pageSelected', $selected);
        return $view;
    }
}
