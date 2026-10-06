---
title: "Kernel and Memory Hardening"
eazydocs_id: 14805
---

Policies that mitigate low-level exploits and memory corruption vulnerabilities.

- **Memory Sanitization**: The system immediately fills freed memory pages and heap objects with zeroes, preventing use-after-free attacks and data leaks from uninitialized memory.
- **Kernel Exploit Mitigation**: 
    - **Disable Slab Merging**: Prevents the kernel from merging memory slabs of similar size, making heap-corruption exploits significantly harder to exploit.
    - **Page Table Isolation (PTI)**: Mitigates speculative execution attacks such as Meltdown.
    - **Randomize Kernel Stack Offset**: Introduces randomness to the kernel stack offset on every system call to thwart memory prediction attacks.
    - **Restrict Kernel Pointers**: Hides kernel pointer addresses from non-privileged processes to prevent information leaks.
- **Attack Surface Reduction**: 
    - **Disable Virtual Syscalls**: Disables vsyscalls to reduce the attack surface for specific exploits and improve performance.
    - **Enable BPF JIT Hardening**: Hardens the Berkeley Packet Filter (BPF) Just-in-Time (JIT) compiler to mitigate JIT spraying attacks.
    - **Restrict SysRq Key**: We restrict the SysRq key to allow only safe reboot sequences (REISUB) while blocking dangerous debugging commands.
    - **Restrict Process Tracing**: Disables unprivileged users from tracing or attaching to other running processes.
- **Disable Obscure Network Protocols**: 
    - We actively blacklist over 15 rare or legacy network protocols, such as DCCP, SCTP, RDS, TIPC, AppleTalk, and IPX, to prevent the kernel from loading them. Attackers frequently target these unused modules due to their lower code quality and historical vulnerabilities.
- **Disable Vulnerable Kernel Modules**: 
    - We actively blacklist specific user-space cryptographic interfaces (algif\_\*) and network protocols (esp4, esp6, rxrpc) to prevent the kernel from loading them.
- **Enable multiple LSMs (Linux security modules)**, such as Capability, AppArmor, Yama, BPF, and Landlock.
