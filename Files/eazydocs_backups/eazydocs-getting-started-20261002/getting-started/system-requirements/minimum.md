---
title: "Minimum Requirements"
eazydocs_id: 14347
---

The minimum requirements specify the hardware configuration needed to run Nitrux without harming the user experience.

### System

 **Motherboard, CPU, RAM**- **Memory**
    - **Minimum:** 4 GB RAM (for a standard workstation experience).
- **Processor**
    - **Required:** 64-bit x86 processor with AVX2 support (x86-64-v3).
    - **Not supported:** 32-bit x86 processors.
- **Firmware**
    - **Supported:** EFI or UEFI firmware. Secure Boot support is optional.
    - **Conditionally supported:** Legacy boot modes (CSM) may function if the system firmware is UEFI-based.
    - **Not supported:** Legacy BIOS-only systems and systems limited to pre-UEFI firmware.
- **Boot support**
    - **Required:** Motherboard support for booting from USB storage.
 
### Graphics / Display

- **Video memory (VRAM)**
    - **Required:** 512MB VRAM (minimum).
- **3D acceleration**
    - **Supported:** GPUs with working [Mesa GBM/KMS](https://docs.mesa3d.org/systems.html) support.
    - **Supported:** NVIDIA GPUs with working Open Kernel Module support.
    - **Not supported:** NVIDIA GPUs that require the Nouveau driver.
    - **Not supported:** NVIDIA GPUs that require the proprietary NVIDIA driver (current or legacy).
    - **Not supported:** Legacy ATI/AMD GCN 1.0 and older GPUs that require Wayland workarounds.
    - **Not supported:** Other unsupported or obsolete GPUs.
 
### Storage

- **Device type**
    - **Supported:** Mechanical hard drives (HDD) and SSDs (NAND-based).
- **Capacity**
    - **Minimum:** 64 GB total storage.
 
### Network

- **Internet access**
    - **Optional for installation:** Nitrux can be installed without an active internet connection.
    - **Required for updates:** An internet connection is required to download and apply system updates.
 
### Audio

- **Audio hardware**
    - **Supported:** Any audio device supported by the Linux kernel included with Nitrux.
 
### Important Notes

 While Nitrux can boot on modern systems (with CPUs that meet the required instructions) with as little as 2 GB of RAM, this configuration is strictly limited to single-tasking and lightweight operations. For a standard desktop experience, including workstation workloads, we've defined a minimum of 4 GB, as indicated above.
