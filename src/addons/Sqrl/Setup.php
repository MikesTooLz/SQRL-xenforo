<?php

namespace Sqrl;

use XF\AddOn\AbstractSetup;

class Setup extends AbstractSetup
{
	public function install(array $stepParams = [])
	{
		// TODO: Implement install() method.
		$db = $this->db();
		$db->query("REPLACE INTO `xf_connected_account_provider` (`provider_id`, `provider_class`, `display_order`, `options`)
		VALUES
			('sqrl', 'Sqrl:Provider\\\\Sqrl', 80, '')");
	}

	public function upgrade(array $stepParams = [])
	{
		// TODO: Implement upgrade() method.
	}

	public function uninstall(array $stepParams = [])
	{
		// TODO: Implement uninstall() method.
	}
}