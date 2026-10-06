---
title: "ISO doesn't boot or System installs, but there&#039;s no GUI"
eazydocs_id: 14648
---

This problem generally indicates a mismatch between your hardware capabilities and the software requirements for Nitrux. Please verify if your hardware falls into one of these categories: **Unsupported CPU Instruction Set (AVX2)**: Nitrux requires a processor compatible with x86-64-v3 (AVX2 support) for the Kernel and some of the software we use; this naturally excludes legacy hardware, even before considering RAM. A CPU is likely too old if the logs mention `invalid opcode` or `illegal hardware instruction`.

1. **Intel**: Haswell (4th Gen) and newer support AVX2.
2. **AMD**: "Bristol Ridge" A-Series (9000-series), specific late "Excavator" models (FX-83xx refresh / later APUs), Ryzen (1st Gen), and newer support AVX2.
 
 **Legacy GPU Issues**: Any ATI Radeon and older AMD Radeon cards (e.g., R7 240/250, HD 7000 series) with GCN 1.0 architecture, and NVIDIA cards not supported by the Open Kernel Module, may fail to launch the Wayland compositor. 1. **ATI/AMD GPU Issues (GCN 1.0 and older)**: GCN 1.0 hardware needs compositor and driver workarounds to run under Wayland (e.g., forcing software cursors or disabling atomic modesetting). These adjustments degrade performance on modern GPUs, so Nitrux doesn't support them. Users who still wish to run such hardware may add these workarounds manually. Also, any ATI Radeon graphics cards, i.e, those requiring the radeon or r128 drivers, are unsupported in Nitrux.
2. **NVIDIA GPU Issues (Pascal, Volta, Maxwell, and older)**: NVIDIA only supports architectures from Turing onwards with the Open Kernel Module because older architectures lack a GSP; there are no workarounds.
 
 **Insufficient RAM**: Nitrux makes full use of modern memory capacities. While Nitrux itself can start with *as little* as 2 GB of RAM, the overall session may behave inconsistently on systems that fall short of our recommendations. In low-memory environments, Nitrux will still boot and run, and its performance optimizations—caching, compression, and memory tuning—help maintain usability even under pressure. However, the overall experience will not reflect the performance we designed Nitrux to deliver on modern hardware with more RAM. We define minimums to keep performance, stability, and support claims honest. ### Important Notes

 Ivy Bridge and older are unsupported. AMD FX, Phenom II, and older Socket FM2/FM2+ A-Series (Kaveri, Richland, Trinity) are unsupported.
