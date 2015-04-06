<?php
exec("zfs list -t snapshot | grep webcam_pics | cut -d' ' -f1", $snapshots);
$snapshots = array_flip($snapshots);

$now = time();

foreach (array_keys($snapshots) as $snapshot) {
	$time = preg_replace('/.+@([0-9]+).([0-9]+).([0-9]+).([0-9]+).([0-9]+).([0-9]+)/', '\1-\2-\3 \4:\5:\6', $snapshot);
	$time = strtotime($time);
	$time = ($now - $time) / (60*60*24);
	$snapshots[$snapshot] = $time;
}

if (!isset($argv[1]) || $argv[1] != 'run') {
	die(print_r($snapshots, true));
}

foreach ($snapshots as $snapshot => $time) {
	if ($time > 14) {
		exec("zfs destroy $snapshot\n");
	}
}
