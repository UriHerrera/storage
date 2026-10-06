---
title: "Dual-booting Nitrux with Windows or other Linux distributions"
eazydocs_id: 14644
---

To install Nitrux alongside Windows, do the following steps:

1. Disable Secure Boot using your device's firmware.
2. After disabling Secure Boot, do either of the following: 
    - Boot the Nitrux ISO, launch the installer, select "Install alongside," and click on an existing Windows NTFS partition with sufficient space. Adjust the slider to allocate space for Nitrux.
 
 \[divider line\_type="undefined" custom\_height="10"\] ![](https://nxos.org/wp-content/uploads/2024/11/Captura-de-pantalla-de-2024-11-11-20-17-52.png)Allocating storage space to install Nitrux within Calamares. Image for reference.



 \[divider line\_type="undefined" custom\_height="10"\] 1. - Or, boot into Windows and use Disk Management to shrink a partition on your storage device to create unallocated space. Then, launch the installer, select "Replace partition," and click the unallocated space.
 
 \[divider line\_type="undefined" custom\_height="10"\] ![](https://nxos.org/wp-content/uploads/2024/11/Captura-de-pantalla-de-2024-11-11-21-33-14.png)Using unallocated space to install Nitrux. Image for reference.



 \[divider line\_type="undefined" custom\_height="10"\] - Alternatively, select "Manual partitioning" and follow our partition layout table.
 
 Regardless of the selection, once the installation is complete, GRUB will display entries to boot into Nitrux or Windows (Windows Boot Manager) upon reboot. \[divider line\_type="undefined" custom\_height="10"\] ![](https://nxos.org/wp-content/uploads/2024/11/Captura-de-pantalla-de-2024-11-11-18-53-56.png)Upon rebooting, GRUB will display an entry for the Windows Boot Manager. Image for reference.



 \[divider line\_type="undefined" custom\_height="10"\] Installing Nitrux alongside other Linux distributions follows the same process described above. 1. Disable Secure Boot using your device's firmware.
2. After disabling Secure Boot, do either of the following: 
    1. Boot the Nitrux ISO, launch the installer, select "Install alongside," and click on an existing partition with sufficient space. Adjust the slider to allocate space for Nitrux.
    2. Alternatively, select "Manual partitioning" and follow our partition layout table.
 
 Regardless of the selection, once installation is complete, GRUB will display entries to boot into Nitrux or another Linux distribution at reboot.
