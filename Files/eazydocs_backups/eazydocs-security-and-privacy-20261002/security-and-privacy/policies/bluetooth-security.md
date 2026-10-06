---
title: "Bluetooth Security"
eazydocs_id: 14822
---

Policies that balance strict pairing security.

- **Privacy &amp; Anti-Tracking**: We enable kernel-side Resolvable Private Address (RPA) resolution to mask the device's identity over time. Additionally, the system enforces strict timeouts that automatically stop broadcasting after a few minutes, reducing the attack surface.
- **Pairing Hardening**: The configuration enforces Secure Connections (SC) mode, which rejects weak, legacy pairing methods. It also blocks silent "Just Works" pairing attempts, forcing explicit user confirmation to prevent unauthorized connections.
