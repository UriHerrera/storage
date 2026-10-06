---
title: "GRUB Menu Information"
eazydocs_id: 14993
---

Run the following commands to edit the GRUB Menu, such as adding or modifying kernel parameters.

```
sudo overlayroot-chroot

mount -t devtmpfs dev /dev

mount -t auto $(findfs LABEL=NX_VAR_LIB) /var/lib

micro /etc/default/grub

# (... do stuff...)

update-grub

sync

umount /dev /var/lib

exit
```

 When booting with UEFI, press `Escape` to display the GRUB menu after the manufacturer logo. When booting with Legacy BIOS, press `Shift` to display the GRUB menu after the manufacturer logo. ### Important Notes

 The GRUB menu will not be displayed in VirtualBox or GNOME Boxes unless there's an additional entry in GRUB to display a menu; pressing Escape has no effect.
