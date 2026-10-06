---
title: "Network Security and Privacy"
eazydocs_id: 14807
---

Policies that protect network identity and prevent spoofing.

- **ICMP Redirect Acceptance**: Prevent the system from modifying its routing table based on unauthenticated network messages.
- **MAC Address Randomization**: Enabled by default to enhance privacy by preventing public networks from tracking the device's unique hardware address.
- **IPv6 Privacy Extensions**: The kernel generates temporary, mangled addresses for outgoing connections, hiding the original auto-configured IPv6 address from remote servers. 
    - **Anti-Spoofing Measures**: 
        - **Reverse Path Filtering**: Enabled to prevent IP spoofing and reduce vulnerability to DDoS attacks.
        - **Disable Source Routing**: Blocks source-routed packets, preventing attackers from bypassing network security controls.
