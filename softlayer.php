#!/usr/bin/php
<?php
/**
 * This assumes the SoftLayer API PHP client
 * <http://github.com/softlayer/softlayer-api-php-client> is installed in the
 * directory '/SoftLayer' in this script's path and that you wish to use the
 * SOAP client instead of our XML-RPC client.
 */

// Softlayer library
require_once(dirname(__FILE__) . '/SoftLayer/SoapClient.class.php');

$conf = array();
$opts = array('apiuser', 'apikey', 'endpoint', 'serverid', 'servertype', 'hostname', 'hostlist');
get_arguments($conf, $opts);

if (isset($conf['action'])) {
	switch ($conf['action']) {
		case 'off':
			// Uses IPMI
			$s = sl_power_off(sl_client($conf));
			exit($s);
			break;
		
		case 'on':
			// Uses IPMI
			$s = sl_power_on(sl_client($conf));
			exit($s);
			break;
		
		case 'reset':
		case 'reboot':
			// Via powerstrip
			$s = sl_power_cycle(sl_client($conf));
			exit($s);
			break;
		
		case 'gethosts':
			print $conf['hostname'];
			exit(0);
			break;
		
		case 'getconfignames':
			foreach ($opts as $o) {
				echo $o;
			}
			exit(0);
			break;
		
		case 'getinfo-devdescr':
			print longdesc();
			exit(0);
			break;
		
		case 'getinfo-devurl':
			print 'https://github.com/bradjones1/stonith-softlayer';
			exit(0);
			break;
		
		case 'getinfo-devid':
		case 'getinfo-devname':
			print 'Softlayer API Stonith Device';
			exit(0);
			break;
		
		case 'getinfo-xml':
		case 'metadata':
		case 'meta-data':
			print metadata();
			exit(0);
			break;
		
		case 'monitor':
		case 'status':
			$s = sl_power_state(sl_client($conf));
			exit($s);
			break;
		
		default:
			exit(1);
			break;
	}
} else {
	exit(1);
}

function get_arguments(&$conf, $opts) {
	// From STDIN
	stream_set_blocking(STDIN, 0);
	while($line = trim(fgets(STDIN))) {
		$line = trim($line);
		if (substr($line, 0, 1) != '#') {
			$thisline = explode('=', $line, 2);
			$conf[$thisline[0]] = $thisline[1];
		}
	}
	
	// From globals (Ubuntu, perhaps other flavors -- cluster-glue)
	foreach ($opts as $o) {
		if (isset($_SERVER[$o])) {
			$conf[$o] = $_SERVER[$o];
		}
	}
	
	// From CLI
	global $argv;
	if (count($argv) > 1) {
		// Get rid of filename
		array_shift($argv);
		foreach ($argv as $k => $a) {
			// Note: no legacy support for -p "val1 val2 ... valn" format
			$thisline = explode('=', $a, 2);
			if (count($thisline) == 2) {
				// Overwrites anything we got above
				$conf[$thisline[0]] = $thisline[1];
			}
			// Ignore flags that start with - and don't overwrite action if
			// We have it already; in effect, ignore subsequent non-delimited CLI opts
			else if (substr($a, 0, 1) != '-' && !isset($conf['action'])) {
				$conf['action'] = $a;
			}
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
	if ($client = SoftLayer_SoapClient::getClient($servertype, $conf['serverid'], $conf['apiuser'], $conf['apikey'], $endpoint)) {
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
	
	// If we get to this point, we were successful and talked to API.
	// Stonith doesn't care if the node is up or down, just that we know
	if ($server == 'on' || $server == 'off') {
		return 0;
	}
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

function longdesc() {
	$longdesc = <<< EOF
SoftLayer technologies (Dallas, Texas, USA with datacenters around the world)
offers IPMI interfaces for servers, which may be power-cycled using the
external/ipmi plugin.  However these IPMI cards utilize the same power supply
as the server itself, which is not recommended.  Use this STONITH plugin to
make a call to the Softlayer API to power cycle the machine.

Note only the "reboot" method uses the power strip; 'on' and 'off' use IPMI
per the API documentation.
EOF;

	return $longdesc;
}

function metadata() {
	$metadata = <<< EOF
<parameters>
<parameter name="apiuser" unique="0">
<content type="string" />
<shortdesc lang="en">API user</shortdesc>
</parameter>
<parameter name="apikey" unique="0">
<content type="string" />
<shortdesc lang="en">API Key</shortdesc>
</parameter>
<parameter name="servertype" unique="0">
<content type="string" />
<shortdesc lang="en">Server type - defaults to dedicated, pass 'SoftLayer_Virtual_Guest' for cloud</shortdesc>
</parameter>
<parameter name="endpoint" unique="0">
<content type="string" />
<shortdesc lang="en">Endpoint URL; defaults to private network</shortdesc>
</parameter>
<parameter name="serverid" unique="0">
<content type="integer" />
<shortdesc lang="en">Server or instance ID</shortdesc>
</parameter>
<parameter name="hostname" unique="0">
<content type="string" />
<shortdesc lang="en">Hostname to fence</shortdesc>
</parameter>
</parameters>
EOF;
	
	return $metadata;
}
