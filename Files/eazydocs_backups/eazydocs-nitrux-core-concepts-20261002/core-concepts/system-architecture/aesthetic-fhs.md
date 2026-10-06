---
title: "Aesthetic FHS"
eazydocs_id: 14883
---

The traditional Filesystem Hierarchy Standard uses abbreviated, historically derived names (`/etc`, `/usr`, `/var`, `/sbin`) that obscure their purpose. Aesthetic FHS replaces this structure with human-readable paths that communicate intent directly. This tree diagram is an initial implementation of a proposed filesystem hierarchy for Nitrux.

```
/Applications               System-wide bundles

/System                     System components
├── Binaries                Non-AppImage system-wide binaries
│   ├── Administrative      For administrative tasks (openrc, agetty, init, haveged)
│   ├── Optional            User-installed system-wide binaries (VirtualBox, DisplayLink)
│   └── Standard            For non-administrative tasks (pix, index, tree, rm, top)
├── Boot                    Kernel, initramfs, and GRUB files
├── Configuration           System-wide configuration files
├── Devices                 Device nodes
├── Filesystems             Mount points for storage devices
│   ├── Internal            Internal storage devices
│   └── External            External storage devices
├── Libraries               System-wide libraries
├── Shareable               System-wide resources
└── Variable                Variable system data

/Users                      User home directories
```

### Rationale

 A user encountering `/System/Configuration` immediately understands its purpose. A user encountering `/etc` must already know Unix conventions. Aesthetic FHS prioritizes legibility over tradition. ### Relationship to Immutability and the Rootless App Model

 Aesthetic FHS reinforces the boundaries established by the immutable root-and-rootless app model. `/System` contains the OS; this is the immutable layer. Everything under `/System` belongs to Nitrux and remains read-only under normal operation. The naming makes this relationship explicit: modifying `/System` means modifying the root itself, which requires deliberate action via `overlayroot-chroot` `/Users` sits outside `/System`. It is the writable layers where user activity happens. AppBoxes installed via NX AppHub, Flatpaks, and Distrobox containers land in the user space. The hierarchy visually separates what Nitrux controls from what users control: ```
/System → Immutable (Nitrux)
/Users  → Writable (user space)
```

 Traditional FHS scatters these concerns across `/usr`, `/opt`, `/home`, `/etc`, and `/var` without clear boundaries. Aesthetic FHS makes the architecture legible.
