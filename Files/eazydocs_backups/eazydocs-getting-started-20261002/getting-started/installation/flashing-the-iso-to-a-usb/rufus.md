---
title: "Rufus (Windows)"
eazydocs_id: 14604
---

If you are using Windows and [**Rufus**](https://rufus.ie/), follow these steps:

1. Download a Nitrux ISO file, and download and install Rufus.
2. Insert the USB flash drive into a USB port, then launch Rufus.
3. Rufus will update the device within the '**Device'** field. If the '**Device'** selected is incorrect (perhaps you have multiple USB storage devices), select the correct one from the dropdown menu in the device field.
4. Now, select the Boot selection. The choices will include *Non-bootable,* *FreeDOS, Disk, or ISO image*. Select "**Disk or ISO image**."
5. To select the Nitrux ISO file you downloaded previously, click **SELECT** to the right of "Boot selection." If this is the only ISO file in the Downloads folder, you will see only one file listed. Select the appropriate ISO file, then click **Open**.
6. The default selections for the Partition scheme (*GPT*) and Target system (*UEFI (no CSM)*) are appropriate (and are the only options available).
7. Rufus will update the "Volume" label to reflect the ISO selected. Leave all other parameters at their default values, then click **START** to initiate the writing process.
8. When prompted to select which mode to write this image, choose to **Write in DD Image mode.**
9. Rufus will now write the ISO to your USB stick, and the progress bar will show your progress. With a reasonably modern machine, this should take around 10 minutes. Rufus displays the total elapsed time in the lower-right corner of the Rufus window.
10. When Rufus has finished writing the USB device, the Status bar will be green-filled, and the word **READY** will appear in the center. Select **CLOSE** to complete the writing process.
 
 \[divider line\_type="undefined" custom\_height="10"\]![](https://nxos.org/wp-content/uploads/2021/12/Captura-de-pantalla-2021-12-08-203241.png) \[divider line\_type="undefined" custom\_height="40"\] ### Troubleshooting

 [Rufus 4.2.4070 Beta](https://www.neowin.net/software/rufus-424070-beta/) introduced a feature to display a warning about revoked UEFI bootloaders: "Add detection and warning for UEFI revoked bootloaders (including ones revoked through SkuSiPolicy.p7b)." Doing this will cause Rufus to display a warning message. Users can ignore this warning, as the bootloader post-installation is signed; however, the bootloader in the ISO isn't signed, nor are the kernels, and the distribution will not boot with Secure Boot enabled. - See [this issue at the Rufus repository](https://github.com/pbatard/rufus/issues/2244) for context about this warning.
- Also, [see this reply in a Reddit thread of the Rufus developer](https://www.reddit.com/r/ghostspectre/comments/15un0rq/trying_to_download_ghost_for_first_time_so/) about their reasoning for implementing it.
