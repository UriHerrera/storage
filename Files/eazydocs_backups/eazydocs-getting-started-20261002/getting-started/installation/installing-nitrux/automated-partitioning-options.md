---
title: "Automated Partitioning Options"
eazydocs_id: 14636
---

Users can select the automated partition options**,** such as **Install alongside**, **Erase disk**, and **Replace partition**. When you use these options, Calamares uses our custom partition layout and creates three partitions, in addition to ESP and Swap. Our partition layout uses percentages to assign partition sizes, which scale based on the storage device size, unallocated space, or the selected partition to replace. We base these percentages on a device with at least 64 GiB of storage; for example, we assign 22% to the root directory, which allocates 14.08 GiB, leaving sufficient space for the root.

- The `/` partition (**NX\_ROOT**) will occupy 22% of the disk and use XFS.
- The `/home` partition (**NX\_HOME**) will occupy 68% of the disk and use F2FS.
- The `/var/lib` partition (**NX\_VAR\_LIB**) will occupy 10% of the disk and use F2FS.
- The `/boot/efi` The partition (**EFI System Partition**) is fixed-size (300 MB) and uses FAT32.
- The `swap` partition (**SWAP**) uses whatever space remains on the storage device.
 
### Important Notes

 Please be aware that the root directory is immutable, and any directory within it is read-only. **Our custom partition layout separates user-managed directories from the root**.
