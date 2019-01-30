<?php

namespace Sqrl\Extend\XF\Service\User;

class DeleteCleanUp extends \XF\Service\User\DeleteCleanUp
{
    public function __construct(\XF\App $app, $userId, $userName)
    {
        parent::__construct($app, $userId, $userName);

        $this->steps = array_merge(['stepCleanUpSqrl'], $this->steps);
    }

    protected function stepCleanUpSqrl()
    {
        $sqrl = $this->app->finder('XF::UserConnectedAccount')
            ->where('user_id', $this->userId)
            ->where('provider', 'sqrl')
            ->fetchOne();
        if ($sqrl)
        {
            \Sqrl\Api::removeSqrlAccount($sqrl->provider_key);
        }
    }
}