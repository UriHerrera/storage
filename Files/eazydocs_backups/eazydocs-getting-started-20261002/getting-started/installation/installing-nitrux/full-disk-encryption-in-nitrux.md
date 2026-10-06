---
title: "Full-disk Encryption in Nitrux"
eazydocs_id: 14640
---

Nitrux supports full-disk encryption with [dm-crypt](https://en.wikipedia.org/wiki/Dm-crypt) during installation via the Calamares installer.

- During early boot, the system prompts the user for their passphrase to unlock all encrypted partitions.
- Block-device encryption during installation is in addition to any userland tool (such as [fscrypt](https://github.com/google/fscrypt)).
 
 The checkbox to encrypt the installation is visible only when selecting automated options in the partitions module, such as **Install alongside**, **Erase disk**, and **Replace partition**. When selecting **Manual partitioning**, the user must use a new partition—not edit an existing partition—to make the checkbox visible in the individual partition setup window.
