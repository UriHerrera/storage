---
title: "Immutable Architecture"
eazydocs_id: 14343
---

Nitrux has used an immutable root filesystem since version 2.6.0 (January 2023); this change lets us provide new distribution versions with greater certainty that root modifications won't cause conflicts. It also prevents issues caused by traditional package managers installing or upgrading packages from third-party sources, which we cannot control and ***which conflict with Nitrux's immutable architecture and our intended system configuration***. NX Overlayroot implements this with OverlayFS, a union filesystem that presents a unified view by overlaying a writable layer on a read-only base. Changes appear to persist during a session but do not alter the underlying root.

### Why Immutability Matters?

1. **System integrity and certainty.** The immutable root guarantees the base system remains in a known state. No package upgrade, user error, or malware can silently alter core system files. When troubleshooting, the root is never the variable—eliminating an entire class of unknowns.
2. **Reproducible updates.** Immutability enables the OTA-style update model used by the Nitrux Update Tool System (NUTS).
3. **Validation of software philosophy.** Software installation occurs entirely outside the immutable root, using AppBoxes (stored in user space), Flatpaks, and Distrobox containers.
 
### Architecture Diagram

 This diagram shows the separation between the immutable base, the ephemeral overlay, and persistent user data. ```
┌─────────────────────────────────────────────────────────────────────────┐
│                          USER SESSION VIEW                              │
│  (The unified file system the user interacts with)                      │
└───────┬───────────────────────────────┬─────────────────┬───────────────┘
        │                               │                 │
  ┌─────▼────────────────────────┐  ┌───▼──────────┐  ┌───▼──────────┐
  │      VIRTUAL ROOT (/)        │  │    /home     │  │   /var/lib   │
  │     (Overlay Filesystem)     │  │              │  │              │
  └─────┬────────────────┬───────┘  └──────────────┘  └──────────────┘
        │                │                  ▲                 ▲
┌───────▼──────┐  ┌──────▼───────┐          │                 │
│  UPPER DIR   │  │  LOWER DIR   │          │                 │
│   (tmpfs)    │  │    (XFS)     │          │                 │
│   Writable   │  │  Read-Only   │          │                 │
│   IN RAM     │  │   ON DISK    │          │                 │
└──────────────┘  └──────────────┘          │                 │
        │                │                  │                 │
        │                │                  │                 │
  ┌─────▼────────────────▼──────────────────┴─────────────────┴─────┐
  │                     PHYSICAL STORAGE LAYER                      │
  │  ┌────────────┐   ┌────────────┐   ┌────────────┐               │
  │  │  Root "/"  │   │   /home    │   │  /var/lib  │               │
  │  │ Partition  │   │ Partition  │   │ Partition  │               │
  │  └────────────┘   └────────────┘   └────────────┘               │
  └─────────────────────────────────────────────────────────────────┘
```

 \[divider line\_type="no-line" custom\_height="20"\] ### User-writable Locations

 While the root is immutable, user data remains writable: - `/home` → User home directories.
- `/var/lib` → Persistent system state.
 
 `/var/lib` serves as the writable location for system services and runtimes that need to persist data across reboots. NetworkManager stores connection profiles here instead of `/etc`; BlueZ stores paired Bluetooth devices; Flatpak uses it for system-wide installations; and Distrobox stores rootful container data here as well. Administrative modifications to the root require either `sudo` and `overlayroot-chroot` or temporarily disabling the overlay. ### Information

 For detailed information on NX Overlayroot, see [System Management → System Administration → NX Overlayroot](https://nxos.org/documentation/system-management/administration/nx-overlayroot/). For detailed information on the Nitrux Update Tool System, see [System Management → System Administration → Nitrux Update Tool System](https://nxos.org/documentation/system-management/administration/nuts/).
