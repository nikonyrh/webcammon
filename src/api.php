<?php

$base      = '/storage/webcam_pics';
$imageBase = "$base/images";

function json($data) {
	header('Content-Type: application/json');
	die(json_encode($data));
}

if (preg_match('_/api/([^/]*?)\.?([a-z]+)?$_', $_SERVER['DOCUMENT_URI'], $match)) {
	//json($match);
	
	switch ($match[1]) {
		case 'fileSizes':
			$aggs   = array();
			$dt     = round(60 / (isset($_GET['dt']) ? intval($_GET['dt']) : 2)); // time resolution = (60 / dt) minutes
			$offset = (new DateTimeZone(date_default_timezone_get()))
				->getOffset(new DateTime()); // This is +2 or +3 hours in Helsinki
			
			// Make strtotime to use UTC, no daylight saving time :)
			date_default_timezone_set('UTC');
			
			$now    = time();
			$result = array();
			$times  = array();
			
			foreach (glob("$imageBase/*") as $folder) {
				$folderName = basename($folder);
				$timeDiff   = ($now - strtotime($folderName)) / (60*60*24);
				$cacheFile  = null;
				$results    = array();
				
				if ($timeDiff > 1.1) {
					// If the day is complete we can save time by caching filesizes into a text file
					
					$cacheFile = "$base/cache/$dt.$folderName.txt";
					//json(array('folderName' => $folderName, 'timeDiff' => $timeDiff, 'cacheFile' => $cacheFile));
					
					if (file_exists($cacheFile)) {
						$results   = json_decode(file_get_contents($cacheFile), true);
						$cacheFile = null;
					}
				}
				
				if (empty($results)) {
					foreach (glob("$folder/*.jpg") as $file) {
						$size = filesize($file);
						$file = preg_split('/[^0-9]+/', basename($file), -1, PREG_SPLIT_NO_EMPTY);
						$time = strtotime(sprintf('%s-%s-%s %s:%s:%s',
							$file[0], $file[1], $file[2], 
							$file[3], $file[4], $file[5])) + $offset;
						
						$time = explode(' ', (new \DateTime("@$time"))->format('Y m d H i s'));
						//die(json_encode(array($file, $time)));
						
						$day  = implode('/', array_slice($time, 0, 3));
						$time = sprintf('%s.%02dh', $time[3],
							(floor(intval($time[4])/$dt)*$dt));
						
						if (!isset($results[$day])) {
							$results[$day] = array();
						}
						
						if (!isset($results[$day][$time])) {
							$results[$day][$time] = array('sum' => 0.0, 'count' => 0, 'files' => array());
						}
						
						$results[$day][$time]['sum'] += $size;
						$results[$day][$time]['count']++;
						$results[$day][$time]['files'][] = implode('-', $file);
					}
				}
				
				foreach ($results as $day => $time2result) {
					if (!isset($aggs[$day])) {
						$aggs[$day] = array();
					}
					
					foreach ($time2result as $time => $result) {
						$times[$time] = true;
						
						if (!isset($aggs[$day][$time])) {
							$aggs[$day][$time] = array('sum' => 0.0, 'count' => 0, 'files' => array());
						}
						
						$aggs[$day][$time]['sum']   += $result['sum'];
						$aggs[$day][$time]['count'] += $result['count'];
						$aggs[$day][$time]['files'] = array_merge($aggs[$day][$time]['files'], $result['files']);
					}
				}
				
				if (!empty($cacheFile)) {
					file_put_contents($cacheFile, json_encode($results));
				}
			}
			
			$times = array_keys($times);
			sort($times);
			
			$files  = array();
			$median = function (array $array) {
				return $array[floor(sizeof($array)/2)];
			};
			
			foreach (array_keys($aggs) as $key => $day) {
				$files[$day] = array();
				
				foreach ($aggs[$day] as $time => $stats) {
					$files[$day][$time] = $median($stats['files']);
					$aggs[$day][$time]  = round($stats['sum'] / (1024.0 * $stats['count']), 1);
				}
			}
			
			if ($match[2] == 'json') {
				// This is for the HTML+Ajax based UI
				json(array(
					'metadata' => array(
						'times' => $times,
						'files' => $files
					),
					'response' => $aggs
				));
			}
			
			// This is for easy Matlab export
			$rows = array();
			$floatFormat = '%6.2f';
			
			{
				$row = array('  -1', '-1', '-1');
				foreach ($times as $time) {
					$time = explode('.', rtrim($time, 'h'));
					$row[] = sprintf($floatFormat, floatval($time[0]) + floatval($time[1]) / 60);
				}
				$rows[] = implode(' ', $row);
			}
			
			foreach ($aggs as $day => $dataTimes) {
				$row = explode('/', $day);
				
				foreach ($times as $time) {
					$row[] = sprintf($floatFormat, isset($dataTimes[$time]) ? $dataTimes[$time] : 0.0);
				}
				
				$rows[] = implode(' ', $row);
			}
			
			header('Content-Type: text/plain');
			die(implode("\n", $rows));
	}
}
