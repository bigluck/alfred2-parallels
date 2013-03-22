<?php


// Main configuration
$inQuery = $argv[1] ?: '';
$reUUID = '(?<uuid>\{[\dA-Fa-f]{8}\-[\dA-Fa-f]{4}\-[\dA-Fa-f]{4}\-[\dA-Fa-f]{4}\-[\dA-Fa-f]{12}\})';
$reList = '/^\s*'.$reUUID.'\s*(?<status>[^\s]+)\s*(?<ip>[^\s]+)\s*(?<name>.*)$/';
if (($isUUID = preg_match('/'.$reUUID.'/', $inQuery, $tmpData) ? true : false) === true)
	$inQuery = $tmpData['uuid'];
$supportedActions = array(
	'start' => array('running', 'suspended', 'paused'),
	'stop' => array('stopped'),
	'reset' => array('stopped', 'suspended'),
	'suspend' => array('stopped', 'suspended'),
	'resume' => array('running', 'stopped'),
        'pause' => array('paused', 'suspended', 'stopped')
    );
$foundVM = array();
$results = array();


// Read VM lists
if (exec('prlctl list -a --no-header '.($isUUID ? $inQuery : ''), $output))
	foreach ($output AS $row)
		if (preg_match($reList, $row, $tmp))
			$foundVM[] = $tmp;


if ($isUUID)
{
	// Action lists per VM
	foreach ($supportedActions AS $action => $currentStatus)
		if (!in_array($foundVM[0]['status'], $currentStatus))
			$results[] = array(
				'uid' => $foundVM[0]['name'].':'.$action,
				'arg' => $action.' '.$inQuery,
				'title' => ucfirst($action),
				'subtitle' => $foundVM[0]['name'],
				'icon' => 'icon.png',
				'valid' => 'yes');

	if ($foundVM[0]['status'] == 'running')
		$results[] = array(
			'uid' => $foundVM[0]['name'].':'.'capture',
			'arg' => 'capture '.$inQuery.' --file ~/Desktop/'.str_replace(array(' ', '/'), array('-', '-'), $foundVM[0]['name']).'-'.@date('Ymd-his').'.jpg',
			'title' => 'Capture a screenshot',
			'subtitle' => $foundVM[0]['name'],
			'icon' => 'icon.png',
			'valid' => 'yes');
} else
{
	// List of VM matched
	$reRowQuery = '/'.preg_quote($inQuery).'/i';
	foreach ($foundVM AS $vm)
		if (preg_match($reRowQuery, $vm['name']))
			$results[] = array(
				'uid' => $vm['uuid'],
				'arg' => $vm['uuid'],
				'title' => $vm['name'],
				'subtitle' => 'Status: '.ucfirst($vm['status']),
				'icon' => 'icon.png',
				'valid' => 'no',
				'autocomplete' => $vm['name'].' '.$vm['uuid']);
}


// No VM matched
if (!count($results))
	$results[] = array(
		'uid' => '',
		'arg' => 'none',
		'title' => 'No VM found!',
		'subtitle' => 'There aren\'t any VM...',
		'icon' => 'icon.png',
		'valid' => 'no');


// Preparing the XML output file
$xmlObject = new SimpleXMLElement("<items></items>");
$xmlAttributes = array('uid', 'arg', 'valid', 'autocomplete');
foreach($results AS $rows)
{
	$nodeObject = $xmlObject->addChild('item');
	$nodeKeys = array_keys($rows);
	foreach ($nodeKeys AS $key)
		$nodeObject->{ in_array($key, $xmlAttributes) ? 'addAttribute' : 'addChild' }($key, $rows[$key]);
}

// Print the XML output
echo $xmlObject->asXML();  

?>

