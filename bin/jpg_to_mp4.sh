#!/bin/bash

while (( "$#" )); do
    cd "$1"
    
    ffmpeg -framerate 30 -pattern_type glob -i '*.jpg' -vcodec libx265 -strict -2 -crf 15 "../videos/$1.mp4"
    
    cd ..
    shift
done




