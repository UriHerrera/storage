---
title: "Non-automated Partitioning Options"
eazydocs_id: 14638
---

Users can also select **Manual partitioning**. However, we strongly recommend following our partition layout; otherwise, users will lose functionality such as upgrades and rollbacks with the Nitrux Update Tool System. We recommend reading the following information before proceeding with manual partitioning.

1. Installing Nitrux in Legacy BIOS hardware is not supported.
2. While the layout below is our recommendation, users can add *almost any directory* they want as a separate partition. However, the root and home must be separate because the root directory is immutable.
3. Please be aware that neither XFS nor F2FS partitions support downsizing, only growing.
4. Most importantly, the root directory is immutable, and any directory within it is read-only. **Do not use a single partition layout**.
 
 Select the mount point of all partitions and options (where applicable). The layout below defines our **partition layout for EFI/****UEFI-based** computers with GPT ([GUID Partition Table](https://en.wikipedia.org/wiki/GUID_Partition_Table)) storage devices. ### Default Partition Layout

- **EFI System Partition**
    - **Label:** ESP
    - **Mount point:** /boot/efi
    - **Filesystem type:** FAT32
    - **Flags:** boot
    - **Size:** 100 M
- **Root filesystem**
    - **Label:** NX\_ROOT
    - **Mount point:** /
    - **Filesystem type:** XFS
    - **Flags:** none
    - **Size:** We **require** at least 7.5 GB of free space for the root filesystem. However, we **recommend** at least 10 GB of free space
- **Home directory**
    - **Label:** NX\_HOME
    - **Mount point:** /home
    - **Filesystem type:** F2FS
    - **Flags:** none
    - **Size:** Up to the user's discretion
- **/var/lib directory**
    - **Label:** NX\_VAR\_LIB
    - **Mount point:** /var/lib
    - **Filesystem type:** F2FS
    - **Flags:** none
    - **Size:** At least 7 GB; more if using *system* Flatpaks and Distrobox containers
- **Swap**
    - **Label:** SWAP
    - **Mount point:** None (automatically configured)
    - **Filesystem type:** Linux swap
    - **Flags:** swap
    - **Size:** 1x RAM (Suspend) or 2x RAM (Suspend/Hibernation)
 
### Important Notes

 **Do not add the directory** `/etc` **as a separate partition**; otherwise, the system will be unbootable Nitrux **requires** XFS for the root partition. The Nitrux Update Tool System depends on XFS features to create redundant backups. Systems installed on other filesystems cannot use Rescue or Rescue Mode. We emphasize that **users must** **label** the partitions; otherwise, they will lose functionality such as upgrades and rollbacks with the Nitrux Update Tool System. Optionally, encrypt the entire system (or each partition individually).
