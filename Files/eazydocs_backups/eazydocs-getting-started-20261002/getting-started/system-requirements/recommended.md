---
title: "Recommended Requirements"
eazydocs_id: 14583
---

The recommended requirements ensure an optimal Nitrux user experience.

### System

 **Motherboard, CPU, RAM**- **Memory**
    - **Recommended:** 8 GB RAM.
- **Processor**
    - **Required:** 64-bit x86 processor with AVX2 support (x86-64-v3).
    - **Recommended Intel:** 8th-generation Core (Coffee Lake) or newer.
    - **Recommended AMD:** Ryzen 1000 series or newer.
- **Firmware**
    - **Supported:** EFI or UEFI firmware. Secure Boot support is optional.
- **Motherboard capabilities**
    - **Recommended:** USB 3.0 boot support and NVMe storage support.
    - **Recommended:** Modern kernel feature support (IOMMU, virtualization extensions, PS/SMT control).
 
### Graphics / Display

- **Video memory (VRAM)**
    - **Recommended:** 2 GB VRAM for Wayland desktops and GPU-accelerated workloads.
- **3D acceleration**
    - **Recommended AMD:** RDNA or newer with working [Mesa GBM/KMS](https://docs.mesa3d.org/systems.html) support.
    - **Recommended Intel:** Intel Xe or Arc with working [Mesa GBM/KMS](https://docs.mesa3d.org/systems.html) support.
    - **Recommended NVIDIA:** Turing architecture or newer.
 
### Storage

- **Device type**
    - **Recommended:** NVMe SSD (PCIe 3.0 or newer) for optimal performance.
    - **Acceptable:** SATA SSD.
    - **Not recommended:** Mechanical hard drives (HDD) for daily use.
- **Capacity**
    - **Recommended:** 128 GB total storage.
 
### Network

- **Internet access**
    - **Optional for installation:** Nitrux can be installed without an active internet connection.
    - **Required for updates:** An internet connection is required to download and apply system updates.
 
### Audio

- **Audio hardware**
    - **Supported:** Any audio device supported by the Linux kernel included with Nitrux.
 
### Important Notes

 While we recommend 8 GB of RAM, our system optimizations are more impactful on systems with 16 GB of RAM or more, ensuring peak performance across modern, demanding workloads and aligning with our memory management philosophy. This capacity enables seamless multitasking in the Hyprland environment, providing headroom for multiple intensive development tasks, gaming, and high-performance virtualization without compromising system responsiveness. For more information, see [Troubleshooting → FAQ → Is Nitrux eating my RAM?](https://nxos.org/documentation/troubleshooting/faq/is-nitrux-eating-my-ram/)
