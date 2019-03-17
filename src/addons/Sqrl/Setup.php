<?php

namespace Sqrl;

use XF\AddOn\AbstractSetup;

class Setup extends AbstractSetup
{
    use \XF\AddOn\StepRunnerUpgradeTrait;

    public function install(array $stepParams = [])
    {
        $db = $this->db();
        $db->query("REPLACE INTO `xf_connected_account_provider` (`provider_id`, `provider_class`, `display_order`, `options`)
        VALUES
            ('sqrl', 'Sqrl:Provider\\\\Sqrl', 80, '')");
    }

    public function upgrade1Step1()
    {
        $db = $this->db();
        // Remove invalid singleton token
        $db->query("DELETE FROM `xf_user_connected_account`
            WHERE `provider` = 'sqrl'
            AND `provider_key` = 'sqrl_provider_key'
        ");
    }

    public function upgrade5Step1()
    {
        if (!\Sqrl\Util::isEnabled())
        {
            return;
        }
        $db = $this->db();
        $sqrls = $this->app->finder('XF:UserConnectedAccount')
            ->where('provider', 'sqrl')
            ->fetch();
        foreach ($sqrls as $sqrl)
        {
            \Sqrl\Api::addUserToSqrl(\Sqrl\Api::addPrefix($sqrl->user_id), $sqrl->provider_key);
        }
    }

    public function uninstall(array $stepParams = [])
    {
        $db = $this->db();
        $db->query("DELETE FROM `xf_connected_account_provider` WHERE `provider_id` = 'sqrl'");
    }
}
