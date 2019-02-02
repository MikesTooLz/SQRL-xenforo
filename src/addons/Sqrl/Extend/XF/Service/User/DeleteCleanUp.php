<?php

namespace Sqrl\Extend\XF\Service\User;

class DeleteCleanUp extends \XF\Service\User\DeleteCleanUp
{
    public function __construct(\XF\App $app, $userId, $userName)
    {
        parent::__construct($app, $userId, $userName);

        $this->steps[] = 'stepCleanUpSqrl';
    }

    protected function stepCleanUpSqrl()
    {
        \Sqrl\Api::removeAssociation(\Sqrl\Api::addPrefix($this->userId));
    }
}