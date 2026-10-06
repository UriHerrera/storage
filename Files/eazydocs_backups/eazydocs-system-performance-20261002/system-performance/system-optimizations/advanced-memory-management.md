---
title: "Advanced Memory Management"
eazydocs_id: 14873
---

Nitrux focuses on optimizing performance and responsiveness by implementing advanced memory management tweaks.

- **Reduced memory compaction overhead**. Reduced CPU overhead and improved overall system responsiveness, especially in memory-intensive applications, leading to more stable performance by avoiding sudden spikes in CPU usage caused by proactive compaction.
- **Improve performance in contended lock scenarios**. By allowing more aggressive page locking, we improve performance in scenarios with memory page contention, which can occur in applications with high memory usage and multi-threaded environments.
- **Panic prevention (OOM Killer)**. Instead of freezing the entire system when memory runs out, we configure the kernel to kill only the specific task causing the shortage surgically.
- **Enable THP (Transparent Hugepages) for all memory locations**. This change will improve performance for applications with considerable memory usage and access patterns that benefit from larger pages.
- **Disable memory page fragmentation**. In workloads like gaming, this change can provide benefits such as reduced CPU overhead, improved system responsiveness, and more predictable performance.
- **Improve system memory stability**. We significantly increased the memory map limit to 16 million, preventing crashes under heavy workloads, such as Star Citizen, Elasticsearch, and certain Wine/Proton games that require many memory mappings.
- **Handle sudden allocation reserves**. We reserve a dedicated block of RAM for atomic operations, ensuring the system can handle bursts of network traffic or hardware interrupts without stuttering.
- **Prioritize VRAM protection using cgroups**. Configure the DRM device memory cgroup (dmem) controllers and enable kernel-level VRAM protection to ensure foreground applications, such as games, have priority access to VRAM. At the same time, background processes are evicted first under pressure.
 
### Important Notes

 Nitrux uses dmemcg-booster, *our fork of Valve's [dmemcg-booster](https://gitlab.steamos.cloud/holo/dmemcg-booster) daemon*, adapted for OpenRC environments. To know more information about *our fork of dmemcg-booster*, [check out its wiki](https://github.com/Nitrux/dmemcg-booster/wiki).
