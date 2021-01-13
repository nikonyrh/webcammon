#!/bin/bash

# cd /storage/webcam_pics/images
# time echo 2020-09-* 2020-10-* 2020-11-* 2020-12-* | xargs -n1 -P8 /storage/webcam_pics/bin/backup.sh

cd "$(dirname "$0")"
cd ../images

if [ "$1" == "" ]; then
    echo ./20* | xargs -n1 -P8 ../bin/backup.sh
    exit $?
fi

MEDIA=/media/toshiba_bak/nas_backup

ymd="$1"
fname=`find $ymd -name '*.jpg' | sort | sha1sum | cut -f1 -d' '`
fname=`echo "$ymd-$fname.zip" | sed -r 's_\.?/__g'`

ym=`echo "$ymd" | sed -r 's/.*(20[0-9]{2}-[0-9]{2}).*/\1/'`
mkdir -p "$MEDIA/storage_webcam_pics/images_zip/$ym"

target="$MEDIA/storage_webcam_pics/images_zip/$ym/$fname"
# echo "$ymd -> $ym -> $target" && exit 0

if [ ! -f "$target" ]; then
    zip -q -r $target $ymd
    echo `date` "$target: done"
else
    echo `date` "$target: skipped"
fi

