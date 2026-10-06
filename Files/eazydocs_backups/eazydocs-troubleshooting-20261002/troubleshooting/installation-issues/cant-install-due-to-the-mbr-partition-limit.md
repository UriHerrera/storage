---
title: "Can&#039;t install due to the MBR partition limit"
eazydocs_id: 14650
---

We strongly discourage using an [MBR (Master Boot Record)](https://en.wikipedia.org/wiki/Master_boot_record) storage device when installing Nitrux because the MBR partition table supports only four primary partitions, and Calamares won't be able to continue the installation. We strongly suggest using a different storage device for Nitrux installation that does not use MBR, deleting the partition table, and using [GPT (GUID Partition Table)](https://en.wikipedia.org/wiki/GUID_Partition_Table) instead. Alternatively, [visit this AskUbuntu link to learn how to convert the partition table from MBR to GPT without data loss](https://askubuntu.com/questions/1314111/convert-mbr-partition-to-gpt-without-data-loss). **This problem is neither a Nitrux bug nor a Calamares bug**. Also, be aware that deleting the partition table will delete the data on the storage device.
