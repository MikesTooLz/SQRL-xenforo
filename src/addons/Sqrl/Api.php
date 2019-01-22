<?php

namespace Sqrl;

class Api
{
	protected static function queryApi($urlSuffix)
	{
		$app = \XF::app();
		$provider = $app->finder('XF:ConnectedAccountProvider')
			->whereId('sqrl')
			->fetchOne();
		if (!$provider)
		{
			throw new \XF\PrintableException("The SQRL provider is missing. Was this add-on installed properly?");
		}
		
		$ch = curl_init();

		$url = 'http://' . $provider->options['private_hostname']  . '/' . $urlSuffix;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_PORT, $provider->options['private_port']);

		// return the transfer as a string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$response = curl_exec($ch);

		$errorCode = curl_errno($ch);
		if ($errorCode)
		{
			throw new \XF\PrintableException("SQRL SSP server connection failed with error code $errorCode.");
		}
		
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if ($httpCode !== 200)
		{
			throw new \XF\PrintableException("SQRL SSP server failed with status code $httpCode.");
		}
		
		curl_close($ch);

		parse_str($response, $parsedResponse);
		return $parsedResponse;
	}

	public static function cps($token)
	{
		return self::queryApi('cps.sqrl?' . $token);
	}

	public static function addUser($userId)
	{
		return self::queryApi('add.sqrl?' . http_build_query(['acct' => $userId]));
	}

	public static function addUserToSqrl($userId, $sqrlId, $stat = null, $identityName = null)
	{
		$query = [
			'acct' => $userId,
			'user' => $sqrlId,
		];
		if ($stat !== null)
		{
			$query['stat'] = $stat;
		}
		if ($identityName !== null)
		{
			$query['name'] = $identityName;
		}
		return self::queryApi('add.sqrl?' . http_build_query($query));
	}

	public static function removeSqrlAccount($sqrlId, $identityName = null)
	{
		$query = ['user' => $sqrlId];
		if ($identityName !== null)
		{
			$query['name'] = $identityName;
		}
		return self::queryApi('rem.sqrl?' . http_build_query($query));
	}

	public static function removeAssociation($userId, $identityName = null)
	{
		$query = ['acct' => $userId];
		if ($identityName !== null)
		{
			$query['name'] = $identityName;
		}
		return self::queryApi('rem.sqrl?' . http_build_query($query));
	}

	public static function getAssociationsByUserId($userId)
	{
		return self::queryApi('lst.sqrl?' . http_build_query(['acct' => $userId]));
	}

	public static function getAssociationsBySqrlId($sqrlId)
	{
		return self::queryApi('lst.sqrl?' . http_build_query(['user' => $sqrlId]));
	}

	public static function getInvite($userId, $identityName, $stat) 
	{
		return self::queryApi('inv.sqrl?', http_build_query([
			'acct' => $userId,
			'name' => $identityName,
			'stat' => $stat,
		]));
	}
}
