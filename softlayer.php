#!/usr/bin/php
<?php
/**
 * This assumes the SoftLayer API PHP client
 * <http://github.com/softlayer/softlayer-api-php-client> is installed in the
 * directory '/SoftLayer' in this script's path and that you wish to use the
 * SOAP client instead of our XML-RPC client.
 *
 */

define('SANDBOX', 'api-sandbox.service.softlayer.com');

// Softlayer library
require_once(dirname(__FILE__) . '/SoftLayer/SoapClient.class.php');

$conf = array();
get_arguments($conf);
if (!($client = sl_client($conf))) {
	return 1;
}

if (isset($conf['action'])) {
	switch ($conf['action']) {
		case 'off':
			// Uses IPMI
			return sl_power_off($client);
			break;
		
		case 'on':
			// Uses IPMI
			return sl_power_on($client);
			break;
		
		case 'reboot':
			// Via powerstrip
			return sl_power_cycle($client);
			break;
		
		default:
		case 'monitor':
		case 'status':
			return sl_power_state($client);
			break;
	}
} else {
	return 1;
}

function get_arguments(&$conf) {
	while($line = trim(fgets(STDIN))) {
		$line = trim($line);
		if (substr($line, 0, 1) != '#') {
			$thisline = explode('=', $line, 2);
			$conf[$thisline[0]] = $thisline[1];
		}
	}
}

/**
 *	Get connection to Softlayer endpoint
 */

function sl_client($conf) {
	$endpoint = isset($conf['endpoint']) ? $conf['endpoint'] : SoftLayer_SoapClient::API_PRIVATE_ENDPOINT;
	// Make a connection to the SoftLayer_Hardware_Server service.
	if ($client = SoftLayer_SoapClient::getClient('SoftLayer_Hardware_Server',
																						$conf['serverid'],
																						$conf['apiuser'],
																						$conf['apikey'],
																						$endpoint)) {
	$objectMask = new SoftLayer_ObjectMask();
	$client->setObjectMask($objectMask);return $client;
	} else {
		return 1;
	}
}

function sl_power_state($client) {
	try {
		$server = $client->getServerPowerState();
	} catch (Exception $e) {
		return 1;
	}
	
	// Returns 'on' or 'off'
	$state = ($server == 'on') ? 0 : 1;
	return $state;
}

function sl_power_off($client) {
	try {
		$server = $client->powerOff();
	} catch (Exception $e) {
		return 1;
	}

	return (int) $server;
}

function sl_power_on($client) {
	try {
		$server = $client->powerOn();
	} catch (Exception $e) {
		return 1;
	}
	
	return (int) $server;
}

function sl_power_cycle($client) {
	try {
		$server = $client->powerCycle();
	} catch (Exception $e) {
		return 1;
	}
	
	return (int) $server;
}