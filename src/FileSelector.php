<?php
class FileSelector
{
	protected $base;
	
	public function __construct($base)
	{
		$this->base = $base;
	}
	
	// This finds images closest to given timestamps
	public function getFilenames(array $epochArr)
	{
		$threshold = ceil(0.51 * (end($epochArr) - $epochArr[0]));
		
		$result = array();
		
		$epochInd = 0;
		$nEpoch   = sizeof($epochArr);
		$UTC      = new DateTimeZone('Etc/UTC');
		$prevDiff = -1;
		
		foreach (glob($this->base . '/*/') as $folder) {
			if (!is_dir($folder)) {
				continue;
			}
			
			$folderEnd = (new DateTime(
				preg_replace('_/.*?([^/]+)/?$_', '\1', $folder),
				$UTC
			))->add(
				DateInterval::createFromDateString('+1 day -1 second')
			)->getTimestamp();
			
			if (
				isset($epochArr[$epochInd]) &&
				$folderEnd < $epochArr[$epochInd] - $threshold
			) {
				continue;
			}
			
			//$result[] = array($folder, $folderEnd); continue;
			
			foreach (glob($folder . '*.jpg') as $fname) {
				preg_match_all('/[0-9]+/', $fname, $epoch);
				$epoch = array_slice($epoch[0], -6);
				$epoch = sprintf(
					'%s-%s-%s %s:%s:%s',
					$epoch[0],
					$epoch[1],
					$epoch[2],
					$epoch[3],
					$epoch[4],
					$epoch[5]
				);
				
				$epoch = (new DateTime($epoch, $UTC))->getTimestamp();
				/*$result[] = array(
					$fname,
					$epoch,
					abs($epoch - $epochArr[$epochInd])
				); continue;*/
				
				$epochDiff = $epochInd > 0 ?
					abs($epoch -  $epochArr[$epochInd-1]) : 1e9;
				
				if ($epochDiff < $prevDiff) {
					$prevDiff = $epochDiff;
					//$result[$epochInd-1] = array($fname, $epoch, $epochDiff);
					$result[$epochInd-1] = $fname;
				}
				else {
					if ($epochInd == $nEpoch) {
						break;
					}
					
					$epochDiff = abs($epoch -  $epochArr[$epochInd]);
					if ($epochDiff < $threshold) {
						$prevDiff = $epochDiff;
						//$result[] = array($fname, $epoch, $epochDiff);
						$result[] = $fname;
						++$epochInd;
					}
				}
			}
		}
		
		return $result; /*array(
			'threshold' => $threshold,
			'result'    => $result
		);*/
	}
}
