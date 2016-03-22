#!/bin/bash

for X in /sys/bus/usb/devices/*; do 
    echo "$X"
    cat "$X/idVendor" 2>/dev/null 
    cat "$X/idProduct" 2>/dev/null
    echo
done
