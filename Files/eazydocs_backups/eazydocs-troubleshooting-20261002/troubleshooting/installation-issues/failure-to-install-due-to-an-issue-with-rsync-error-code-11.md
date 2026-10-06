---
title: "Failure to install due to an issue with rsync error code 11"
eazydocs_id: 14654
---

As we have indicated numerous times in the [Installation Guide](https://nxos.org/documentation/getting-started/installation/) section, Nitrux uses a custom partition layout with fixed percentages that scale with the device's size or the allocated space. Failing to provide sufficient storage for the root partition will prevent the installation from finalizing, as we mentioned in [this issue in our bug tracker](https://github.com/Nitrux/nitrux-bug-tracker/issues/98); **this is neither a bug in Nitrux nor** **in Calamares**. For context, on Legacy BIOS devices, Calamares may not display an error when that scenario occurs; in that case, it is a bug in Calamares (not displaying the error), as it does display an error message when the device uses EFI.
