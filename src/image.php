<?php
require 'FileSelector.php';
$base = '/storage/webcam_pics/images';

$timespanMapping = array(
	'I' => array('minute',60),
	'H' => array('hour',  60*60),
	'D' => array('day',   60*60*24),
	'W' => array('week',  60*60*24*7),
	'M' => array('month', 60*60*24*30)
);

$timePattern = '([0-9]+)([' . implode('', array_keys($timespanMapping)) . '])';
$imgPattern  = '@/' . $timePattern . '\.jpg$@i';

$renderImage = function($epochArr, $grid, $match) use ($base) {
	if (isset($_GET['debug'])) {
		$times = array();
		foreach ($epochArr as $epoch) {
			$time = new DateTime("@$epoch");
			$time->setTimeZone(new DateTimeZone('Etc/UTC'));
			$times[] = $time->format('Y-m-d H:i:s');
		}
		die('<pre>' . print_r($times, true));
	}
	
	$cacheDir = '/rapid/webcam_cache/img_cache';
	
	if (!is_dir($cacheDir)) {
		mkdir($cacheDir);
	}
	
	$fnames = (new FileSelector($base))->getFilenames($epochArr);
	
	if (empty($fnames)) {
		die('Not enough images found!');
	}
	
	// Use a few smallest hash values, cached image can be used even if
	// it doesn't quite match the filename list here.
	$cacheHash = array_map(function($fname) { return sha1($fname); }, $fnames);
	sort($cacheHash);
	
	$w = min(2048 * 4, isset($_GET['w']) ? intval($_GET['w']) : 2048);
	$h = 4*ceil($w * 720/1280 / (4 * $grid));
	$w = 4*ceil($w / (4 * $grid));
	$scale = "${w}x${h}";
	
	$quality = '80';
	
	$cacheName = sprintf("$cacheDir/%s.jpg", sha1(
			json_encode(array(
				array_slice($cacheHash, 0, 2),
				$scale,
				$quality,
				$match
		))));
	
	if (!file_exists($cacheName)) {
		exec("find $cacheDir -name '*.jpg' " .
			"-type f -mtime 0.1 | xargs -n10 rm");
		
		$fnames = implode(' ', $fnames);
		exec("montage -mode concatenate -resize $scale " .
			"-quality $quality -tile ${grid}x $fnames $cacheName");
	}
	
	return $cacheName;
};

/*if (preg_match('@/([^/_]+)_([^/_]+)$@', $_SERVER['DOCUMENT_URI'], $match)) {
	//die('<pre>' . print_r($match, true));
	
	$file = sprintf(
		"$base/%s/%s_%s.jpg",
		$match[1], $match[1], $match[2]
	);
	
	header('Content-Type: image/jpg');
	readfile($file);
}
else*/ if (preg_match($imgPattern, strtoupper($_SERVER['DOCUMENT_URI']), $match)) {
	$grid    = max(1, min(10, isset($_GET['grid']) ? intval($_GET['grid']) : 4));
	$nFrames = $grid * $grid;
	
	$timeStr = -$match[1] . ' ' . $timespanMapping[$match[2]][0];
	if (intval($match[1]) > 1) {
		// Add the plural
		$timeStr .= 's';
	}
	
	$time = (new DateTime())->add(DateInterval::createFromDateString($timeStr));
	$time->setTimeZone(new DateTimeZone('Etc/UTC'));
	$epochStart = $time->getTimestamp();
	$epochEnd   = time();
	
	$delta = ($epochEnd - $epochStart) / ($nFrames - 1);
	
	if (
		isset($_GET['skip']) &&
		preg_match("/$timePattern/", strtoupper($_GET['skip']), $skipMatch)
	) {
		$skip = floatval($skipMatch[1]) * $timespanMapping[$skipMatch[2]][1];
		$epochStart -= $skip;
		$epochEnd   -= $skip;
	}
	
	$epochArr = array();
	foreach (range(0, $nFrames-1) as $i) {
		$epochArr[] = round($epochStart + $i * $delta);
	}
	
	$cacheName = $renderImage($epochArr, $grid, $match);
	header('Content-Type: image/jpg');
	readfile($cacheName);
}
elseif (preg_match(
	'/([0-9]{1,2})(\.([0-9]{2}))?\.jpg/i',
	$_SERVER['DOCUMENT_URI'],
	$match
)) {
	$time  = new DateTime();
	$parts = explode(' ', $time->format('Y m d H i s'));
	//die('<pre>' . print_r(array($parts, $match), true));
	
	$hours   = $match[1];
	$minutes = isset($match[3]) ? $match[3] : '00';
	
	if (strlen($hours) < 2) {
		$hours = "0$hours";
	}
	
	$time = DateTime::createFromFormat(
		'Y-m-d H:i:s',
		sprintf(
			'%s-%s-%s %s:%s:00',
			$parts[0], $parts[1], $parts[2],
			$hours,    $minutes
		)
	);
	$time->setTimeZone(new DateTimeZone('Etc/UTC'));
	$epochEnd = $time->getTimestamp();
	
	/*die('<pre>' . print_r(array(
		'match'        => $match,
		'epochEnd'     => $epochEnd,
		'epochFormat'  => $time->format('Y-m-d H:i:s')
	), true));*/
	
	$grid    = max(1, min(10, isset($_GET['grid']) ? intval($_GET['grid']) : 4));
	$nFrames = $grid * $grid;
	
	$delta    = 60*60*24;
	$epochArr = array();
	foreach (range($nFrames, 1, -1) as $i) {
		$epochArr[] = round($epochEnd - $i * $delta);
	}
	
	$cacheName = $renderImage($epochArr, $grid, $match);
	header('Content-Type: image/jpg');
	readfile($cacheName);
}
elseif (isset($_GET['recent'])) {
	$folder = end(glob("$base/*-*-*/"));
	$file   = end(glob("$folder*.*"));
	
	header('Content-Type: image/jpg');
	readfile($file);
}
else {
	die('Unknown URL!');
}
