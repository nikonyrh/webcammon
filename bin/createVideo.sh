#!/bin/bash

CODEC=mpeg4
#CODEC=mpeg2video

FPS=30

# In my machine /storage is two HDDs in RAID-1 and /rapid is two SSDs in RAID-0

cd /storage/webcam_pics

if [ -z "$1" ]; then
	#im=`find images -type f -name '*.jpg' | sort`
	#im=`find images -type f -name '*14-00-*.jpg' | sort`
    im=`find images/2016-01-26 -type f -name '*.jpg' | sort`

	# Split to 'P' equal chunks for parallel processing
	N=`echo "$im" | wc -l`
	P=4

	# 's' is the number of images / segment
	s=$((($N + $P - 1) / $P))

	# Clean up, in case this was aborted earlier
	rm -f tmp.*
	rm -f /rapid/webcam_cache/output.avi.tmp.*

	echo "$N rows / $P = $s"
	echo "$im" | split -l $s -d - tmp.

	# Call this recursively, creating 'P' processes, each processing a different segment of images
	echo tmp.* | xargs -P0 -n1 "$0"

	# Combine chunks into a single file
	mencoder -forceidx -ovc copy -o /rapid/webcam_cache/output.avi \
		/rapid/webcam_cache/output.avi.tmp.* > /dev/null

	# Clean up
	rm tmp.*
	rm -f /rapid/webcam_cache/output.avi.tmp.*
else
	# Read filenames from a file $1 and create a video segment
	mencoder "mf://@$1" -mf "fps=$FPS" -ovc lavc -lavcopts \
		"vcodec=$CODEC:vbitrate=10000:keyint=15" -o "/rapid/webcam_cache/output.avi.$1" > /dev/null
fi

