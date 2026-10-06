---
title: "Using Secure Boot with Nitrux"
eazydocs_id: 14634
---

When Secure Boot is enabled, we strongly recommend using Ventoy to boot the ISO. [Ventoy provides documentation on enrolling its key in the computer's MOKManager](https://www.ventoy.net/en/doc_secure.html). After enrolling Ventoy's key and rebooting, select our ISO and use GRUB 2 Boot mode. However, enrolling Ventoy's key only authorizes Ventoy; it does not authorize the bootloader installed on the system. *The Nitrux GRUB packages currently contain unsigned UEFI binaries, and the default Nitrux kernel is also unsigned. Therefore, an installed system may fail to boot before the kernel loads if Secure Boot remains enabled.* To boot the installed system with Secure Boot enabled, the installed bootloader must first be trusted—through a signed shim and compatible GRUB, or a locally trusted GRUB—and the kernel must also be signed. Nitrux SB Manager currently signs only the kernel, so it is sufficient only when the installed bootloader is already trusted. Kernel Boot can switch kernels after Nitrux is already running; however, it does not solve an unsigned GRUB or shim problem during a cold boot.

### Important Notes

 If Secure Boot is enabled and the kernel is unsigned, booting will fail with an error such as "Secure Boot Violation", "Secure Boot Fail", and other variants. Some distributions, like Ubuntu, have their bootloaders (shims) signed by Microsoft's UEFI CA, allowing them to trust their kernels without manual enrollment. Given [our past interactions with Microsoft](https://nxos.org/news/official-statement-regarding-xamarin-forms-rebranding-as-maui/) and [its partners](https://nxos.org/news/official-statement-regarding-uxdivers-grial-kit-and-mauikit-com-usage/), we're unwilling to pay Microsoft for its signing service. Users can freely generate and enroll their Machine Owner Keys (MOKs) to locally sign and boot custom kernels.
