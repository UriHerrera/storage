---
title: "Energy Saving Information"
eazydocs_id: 14985
---

Nitrux includes energy-optimizing software like [Powertop](https://github.com/fenrus75/powertop) and [power-profiles-daemon](https://gitlab.freedesktop.org/upower/power-profiles-daemon).

```
sudo overlayroot-chroot

mount -t devtmpfs dev /dev

powertop --calibrate

sync

umount /dev

exit
```

 \[divider line\_type="no-line" custom\_height="5"\] ### Important Notes

 To adequately use Powertop, run it while using the battery so its auto-tune functionality creates the file "*saved\_results.powertop*" in `/var/cache/powertop`. When using Powertop for calibration, it will toggle various functions, such as the backlight or WiFi. Thus, it may turn your screen black for a while, cause a network connection loss, etc. **Do not touch the machine during the calibration**. Powertop needs to run 370+ measurements before correctly displaying power usage estimates. Each lasts 20 seconds, meaning Powertop must run for 1h30 in total. To do this, run the commands below. Check the available options in Powertop by running the following command `powertop --help`.
