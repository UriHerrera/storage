---
title: "Flashing the ISO to a USB"
eazydocs_id: 14594
---

These instructions will walk you through creating a bootable Nitrux USB stick on Windows or Linux. You can use a USB stick to boot, test, or install Nitrux on any computer that supports USB booting. We do not recommend using the following programs to flash the Nitrux ISO to a USB drive. We will close issues caused by the software listed below, including failed flash or write operations and an unbootable Live session.

- **BalenaEtcher**. BalenaEtcher runs an *unknown* test at the end of the flashing process that consistently fails, even though it writes the image to the USB device without errors. Additionally, users have reported [severe privacy concerns because the application ](https://github.com/balena-io/etcher/issues/2057)[sends user data to multiple third parties](https://github.com/balena-io/etcher/issues/2057).
- **Unetbootin**. Unetbootin does not write the image; it copies it to the USB. However, unlike Ventoy, Unetbootin does not boot the ISO file.
- **KDE ISOImageWriter**. ISOImageWriter fails to write the ISO image at 99%; this is a [known application issue](https://bugs.kde.org/show_bug.cgi?id=445036).
- **Linux Mint mintstick**. Mintstick fails to write the ISO image correctly to the target device.
 
 We strongly recommend using the following programs to flash our ISO file to a USB instead. The list of software below for flashing the ISO is not in any particular order of preference. - **Rufus** (Windows)
- **Ventoy** (Windows/Linux)
- **ROSA Image Writer** (Windows/Linux)
- **dd** (\*nix)
