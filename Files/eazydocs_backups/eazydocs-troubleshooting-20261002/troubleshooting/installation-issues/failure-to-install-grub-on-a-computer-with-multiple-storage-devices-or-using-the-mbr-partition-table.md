---
title: "Failure to install GRUB on a computer with multiple storage devices or using the MBR partition table"
eazydocs_id: 14656
---

When installing Nitrux on a computer with multiple available storage devices and using an MBR disk as the installation target, Calamares may not select the same device, resulting in an error during GRUB installation. This issue was very common with Legacy BIOS computers. As mentioned, we strongly discourage the use of MBR storage devices. Users who choose to continue the installation despite our recommendations must ensure that the disk with the MBR partition table is the same disk on which Calamares will install Nitrux. Otherwise, Calamares will fail to install GRUB. **This discrepancy in target device selection is a bug in Calamares, since it is not configurable**. However, most importantly, Nitrux does not support Legacy BIOS hardware; Nitrux supports only UEFI systems.
