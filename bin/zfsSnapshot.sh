#!/bin/bash
if [ -z "$1" ]; then
	# Since this is triggered at the same second as webcam
	# capture is, this lets the capture finish.
	echo Wait... && sleep 25
fi

zfs snapshot storage/webcam_pics@`date +%Y-%m-%d-%H-%M-%S`
