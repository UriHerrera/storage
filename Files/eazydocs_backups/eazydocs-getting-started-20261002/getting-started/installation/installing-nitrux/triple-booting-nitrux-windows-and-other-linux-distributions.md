---
title: "Triple-booting Nitrux, Windows, and other Linux distributions"
eazydocs_id: 14646
---

Installing Nitrux alongside Windows and other Linux distributions follows the same process as a dual-boot setup.

1. Assuming Windows is already present, install the other Linux distribution before installing Nitrux.
2. Disable Secure Boot using your device's firmware.
3. After disabling Secure Boot, do either of the following: 
    - Boot the Nitrux ISO, launch the installer, select "Install alongside," and click on an existing partition with sufficient space. Adjust the slider to allocate space for Nitrux.
 
![](https://nxos.org/wp-content/uploads/2024/11/Captura-de-pantalla-de-2024-11-11-23-09-24.png)As before, use the slider to allocate storage for installing Nitrux within Calamares. Image for reference.



 \[divider line\_type="undefined" custom\_height="10"\] - - Or use Windows or another Linux distribution to shrink an existing partition to create unallocated space. Then, launch the installer, select "Replace partition," and click the unallocated space.
 
 \[divider line\_type="undefined" custom\_height="10"\] ![](https://nxos.org/wp-content/uploads/2024/11/Captura-de-pantalla-de-2024-11-11-23-18-19.png)Using unallocated space to install Nitrux. Image for reference.



 \[divider line\_type="undefined" custom\_height="10"\] - - Alternatively, select "Manual partitioning" and follow our partition layout table.
 
 Regardless of the selection, once the installation is complete, GRUB will display entries to boot into Nitrux, Windows (Windows Boot Manager), and other Linux distributions upon reboot. \[divider line\_type="undefined" custom\_height="10"\] ![](https://nxos.org/wp-content/uploads/2024/11/Captura-de-pantalla-de-2024-11-11-23-21-18.png)Upon reboot, GRUB will display an entry for each available OS. Image for reference.



 \[divider line\_type="undefined" custom\_height="10"\]
