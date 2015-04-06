#!/bin/bash
# Put this file to crontab, mine is running every minute and creates
# about 350 Mb of files / day. File sizes vary from 30 kb at night to
# 400 kb at clowdy day. Sunny or snowy images are 250 - 300 kb.

# To see a list of controls:
# v4l2-ctl --list-ctrls

v4l2-ctl --set-ctrl focus_auto=0
v4l2-ctl --set-ctrl focus_absolute=1

v4l2-ctl --set-ctrl contrast=9
v4l2-ctl --set-ctrl saturation=165

v4l2-ctl --set-ctrl white_balance_temperature_auto=0
v4l2-ctl --set-ctrl white_balance_temperature=5000
v4l2-ctl --set-ctrl backlight_compensation=2
v4l2-ctl --set-ctrl zoom_absolute=30

# Using UTC timezone to avoid issues with summer and normal times
F=`date -u +'%F/%F_%H-%M-%S'`
F="/storage/webcam_pics/images/$F.jpg"
D=`dirname $F`

if [ ! -d "$D" ]; then
	mkdir $D
fi

# My webcam is hung upside down so I need to rotate the image by 180 degrees,
# also I skip first 30 frames to let the camera do its adjustments and calibration.
fswebcam --rotate 180 --no-banner -r 1280x720 --jpeg 95 -S 30 "$F"
