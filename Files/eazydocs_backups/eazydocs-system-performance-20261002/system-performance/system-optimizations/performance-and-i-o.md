---
title: "Performance and I/O"
eazydocs_id: 14877
---

Nitrux enables multiple changes in the kernel I/O and memory systems.

- We use **asynchronous garbage collection** by default to avoid synchronous updates to access or modification times.
- **Modify the rate at which the kernel reclaims VFS caches to enable asynchronous, non-blocking I/O** and **reduce the kernel's aggressiveness in swapping out anonymous memory relative to pagecache and other caches**.
- **Increase Linux autotuning TCP buffer limits**. These TCP buffer settings enhance high-speed network performance by dynamically adjusting buffer sizes to improve throughput, reduce packet loss, prevent congestion, and ensure smooth data transfers, all while efficiently managing system memory.
- **Increase the maximum number of packets queued for processing before dropping**. It improves handling of bursty traffic, reduces packet drops, and enhances performance and stability on high-speed networks under heavy load.
- **Reuse Time-Wait TCP connections to reduce the number of open TCP connections**. Efficiently manage network connections, reducing open TCP connections, freeing up system resources, and improving performance.
- **Control the boost applied to the watermark (the minimum number of free pages the kernel tries to maintain)**. Maintain higher free memory to prevent the system from stalling due to memory shortages.
- **Change the dirty page settings to reduce disk I/O**. Allow for efficient memory usage without frequent disk access.
- **Disable the 32-bit VDSO** (Virtual Dynamic Shared Object) on 64-bit systems.
- **Enable AMD Preferred Core** functionality to ensure the CPU's most performant cores run scheduled tasks.
- **SCX scheduler** based on whether the system is running on AC or battery power. The SCX framework uses dispatch queues (DSQs) to manage task queuing and execution, including a global FIFO queue (SCX\_DSQ\_GLOBAL) and per-CPU local queues (SCX\_DSQ\_LOCAL).
- **Enable the AMD 3D V-Cache Optimizer** on supported systems to prioritize the CCD with the larger L3 cache, improving application performance.
- **Configure GameMode to use its heuristics to pin and park cores** on AMD 3D V-Cache processors and Intel processors with P/E cores, avoiding performance loss from suboptimal core scheduling.
- **ADIOS (Adaptive Deadline I/O Scheduler)**. A block-layer I/O scheduler for the Linux kernel, designed for modern multi-queue block devices (blk-mq), providing low-latency disk access while maintaining fairness across concurrent workloads.
- **Expanded file monitoring**. Increasing the inotify watch limit prevents "limit reached" errors in applications that monitor vast numbers of files, such as IDEs (VS Code, IntelliJ) and file synchronization tools.
- **Enable TCP Fast Open**. We enable TCP Fast Open to eliminate the initial handshake delay for supported applications, speeding up repetitive data connections.
- **Enable zswap**. Compresses memory pages before writing to swap, reducing disk I/O. Zswap is preferred over zram because it retains disk-backed swap as a fallback, providing both performance and stability under heavy memory pressure.
- **Eliminate the wake-up timeout and drastically reduce boot time** by preventing the NVMe drive from entering the deepest power-saving states.
- **Optimized RCU settings**. 
    - Enable expedited RCU grace periods to improve system responsiveness in specific scenarios.
    - Offload all RCU (Read-Copy-Update) callbacks to kernel threads, reducing interrupt contention on CPUs.
    - Enable "lazy" RCU mode to delay some operations and optimize performance.
    - Enable additional security checks for user copy operations.
