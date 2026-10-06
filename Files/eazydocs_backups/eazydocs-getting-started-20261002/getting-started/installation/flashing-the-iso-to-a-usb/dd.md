---
title: "dd (*nix)"
eazydocs_id: 14610
---

If you are using Linux and the command **'dd,'** follow these steps:

1. Download a Nitrux ISO file.
2. Insert the USB flash drive into a USB port, then open a Terminal window.
3. Enter the following command in the Terminal window and hit Enter. 
    - You must replace **/dev/sdX** with the correct device, such as **/dev/sda**,**/dev/sdb,** or others.
 
 \[divider line\_type="undefined" custom\_height="5"\] ```
sudo dd if=nitrux.iso of=/dev/sdX oflag=sync bs=4M status=progress
```
