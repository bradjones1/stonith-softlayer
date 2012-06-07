#!/usr/bin/php
<?php
/**
 * This assumes the SoftLayer API PHP client
 * <http://github.com/softlayer/softlayer-api-php-client> is installed in the
 * directory '/SoftLayer' in this script's path and that you wish to use the
 * SOAP client instead of our XML-RPC client.
 *
 */

// Softlayer library
require_once(dirname(__FILE__) . '/SoftLayer/SoapClient.class.php');

$conf = array();
get_arguments($conf);

if (isset($conf['action'])) {
	if (!($client = sl_client($conf))) {
		exit(1);
	}
	switch ($conf['action']) {
		case 'off':
			// Uses IPMI
			$s = sl_power_off($client);
			exit($s);
			break;
		
		case 'on':
			// Uses IPMI
			$s = sl_power_on($client);
			exit($s);
			break;
		
		case 'reboot':
			// Via powerstrip
			$s = sl_power_cycle($client);
			exit($s);
			break;
		
		case 'metadata':
			print metadata();
			exit(0);
			break;
		
		default:
		case 'monitor':
		case 'status':
			$s = sl_power_state($client);
			exit($s);
			break;
	}
} else {
	exit(1);
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
	$servertype = isset($conf['servertype']) ? $conf['servertype'] : 'SoftLayer_Hardware_Server';
	// Make a connection to the SoftLayer_Hardware_Server service.
	if ($client = SoftLayer_SoapClient::getClient($servertype,
																						$conf['serverid'],
																						$conf['apiuser'],
																						$conf['apikey'],
																						$endpoint)) {
		$objectMask = new SoftLayer_ObjectMask();
		$client->setObjectMask($objectMask);
		return $client;
	} else {
		return 0;
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
	
	// Returns bool; same with two functions below.
	$s = $server ? 0 : 1;
	return $s;
}

function sl_power_on($client) {
	try {
		$server = $client->powerOn();
	} catch (Exception $e) {
		return 1;
	}
	
	$s = $server ? 0 : 1;
	return $s;
}

function sl_power_cycle($client) {
	try {
		$server = $client->powerCycle();
	} catch (Exception $e) {
		return 1;
	}
	
	$s = $server ? 0 : 1;
	return $s;
}

function metadata() {
	$metadata = <<< EOF
	<?xml version="1.0" ?>
	<resource-agent name="softlayer" shortdesc="Fence agent for Softlayer servers and cloud instances">
	<longdesc>
	SoftLayer technologies (Dallas, Texas, USA with datacenters around the world)
	offers IPMI interfaces for servers, which may be power-cycled using the
	external/ipmi plugin.  However these IPMI cards utilize the same power supply
	as the server itself, which is not recommended.  Use this STONITH plugin to
	make a call to the Softlayer API to power cycle the machine.
	
	Note only the "reboot" method uses the power strip; 'on' and 'off' use IPMI
	per the API documentation.</longdesc>
		
	<parameters>
	<parameter name="apiuser" unique="1">
	<content type="string" />
	<shortdesc lang="en">API user</shortdesc>
	</parameter>
	<parameter name="apikey" unique="1">
	<content type="string" />
	<shortdesc lang="en">API Key</shortdesc>
	</parameter>
	<parameter name="servertype" unique="1">
	<content type="string" />
	<shortdesc lang="en">Server type - defaults to dedicated, pass 'SoftLayer_Virtual_Guest' for cloud</shortdesc>
	</parameter>
	<parameter name="endpoint" unique="1">
	<content type="string" />
	<shortdesc lang="en">Endpoint URL; defaults to private network</shortdesc>
	</parameter>
	<parameter name="serverid" unique="1">
	<content type="integer" />
	<shortdesc lang="en">Server or instance ID</shortdesc>
	</parameter>
	</parameters>
	<actions>
	<action name="on" />
	<action name="off" />
	<action name="reboot" />
	<action name="status" />
	<action name="monitor" />
	<action name="metadata" />
	</actions>
	</resource-agent>

EOF;
	
	return $metadata;
}
