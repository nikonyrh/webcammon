#!/bin/bash
cd "$(dirname "$0")"

MEDIA=/media/toshiba_bak/nas_backup

cd ../images
for ymd in ./20*/; do
    fname=`find $ymd -name '*.jpg' | sort | sha1sum | cut -f1 -d' '`
    fname=`echo "$ymd-$fname.zip" | sed -r 's_\.?/__g'`

    ym=`echo "$ymd" | sed -r 's/.+(20[0-9]{2}-[0-9]{2}).+/\1/'`
    mkdir -p "$MEDIA/storage_webcam_pics/images_zip/$ym"

    target="$MEDIA/storage_webcam_pics/images_zip/$ym/$fname"

    if [ ! -f "$target" ]; then
        zip -q -r $target $ymd
        echo "$ymd: done"
    else
        echo "$ymd: skipped"
    fi
done

