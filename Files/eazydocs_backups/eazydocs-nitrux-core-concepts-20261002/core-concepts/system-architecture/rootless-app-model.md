---
title: "Rootless App Model"
eazydocs_id: 14757
---

Nitrux strictly separates the immutable root filesystem from user-installed software. Applications never modify the root—they exist entirely in user-writable locations, managed through declarative tooling rather than traditional package managers.

### **Decoupling Applications from System Integrity**

 In traditional distributions, applications and system components share the same hierarchy (e.g., `/usr`), creating a fragile dependency web in which a software update can destabilize the boot process. Nitrux eliminates this risk by enforcing a strict **Layered Architecture**. We treat the system not as a bucket for applications, but as an immutable chassis. Applications are modular payloads that run *on top* of this chassis, not *inside* it. ### The Architecture Stack

 We divide the system into three distinct zones of authority: - **Immutable Root (Tier 0)**: The OS core. It is read-only and managed strictly by the Nitrux Update Tool System. Nothing the user installs ever touches this layer.
- **Persistent System State (Tier 1)**: Located in /var/lib, this layer holds system-wide data that must change (Docker images, Bluetooth pairings, NetworkManager connections) but does not alter OS binaries.
- **User Space (Tier 2)**: Located in /home, this is your domain. AppBoxes and user configurations live here, completely isolated from the system core.
 
 \[divider line\_type="no-line" custom\_height="5"\] ```
┌────────────────────────────────────────────────────────────────┐
│                         USER SPACE                             │
│                          /home                                 │
│  ┌───────────────┐  ┌───────────────┐  ┌────────────────────┐  │
│  │   AppBoxes    │  │ Flatpak user  │  │ User configuration │  │
│  │  (NX AppHub)  │  │               │  │                    │  │
│  └───────────────┘  └───────────────┘  └────────────────────┘  │
├────────────────────────────────────────────────────────────────┤
│                   PERSISTENT SYSTEM STATE                      │
│                         /var/lib                               │
│  ┌───────────────┐  ┌───────────────┐  ┌────────────────────┐  │
│  │Flatpak system │  │   Distrobox   │  │   Service data     │  │
│  │               │  |    rootful    │  |  (NetworkManager,  │  │
│  │               │  │  containers   │  │  BlueZ, etc.)      │  │
│  └───────────────┘  └───────────────┘  └────────────────────┘  │
├────────────────────────────────────────────────────────────────┤
│                       IMMUTABLE ROOT                           │
│                            /                                   │
│                                                                │
│                    Read-only base system                       │
│                   (NX Overlayroot + NUTS)                      │
│                                                                │
│                  ┌──────────────────────┐                      │
│                  │   No writes allowed  │                      │
│                  └──────────────────────┘                      │
└────────────────────────────────────────────────────────────────┘

                SOFTWARE INSTALLATION FLOW

                       ┌──────────┐
                       │  User    │
                       │ installs │
                       │ software │
                       └────┬─────┘
                            │
               ┌────────────┼────────────┐
               ▼            ▼            ▼
          ┌─────────┐ ┌──────────┐ ┌───────────┐
          │AppBoxes │ │ Flatpak  │ │ Distrobox │
          └────┬────┘ └────┬─────┘ └─────┬─────┘
               │           │             │
               ▼           ▼             ▼
          ┌─────────┐ ┌──────────┐ ┌───────────┐
          │ /home   │ │ /home or │ │ /home or  │
          │         │ │ /var/lib │ │ /var/lib  │
          └─────────┘ └──────────┘ └───────────┘
               │           │             │
               └───────────┴─────────────┘
                           │
                           ▼
               ┌───────────────────────┐
               │ Immutable root        │
               │ remains untouched     │
               └───────────────────────┘
```

 \[divider line\_type="no-line" custom\_height="5"\] Users can install, uninstall, or modify applications in the *User Space* or *Persistent State* without compromising the *Immutable Root's bootability*. ### Why Rootless?

- **Preserves immutability.** Software installation cannot compromise the root filesystem's known state.
- **User autonomy.** Users manage their own applications without elevated privileges. NX AppHub manages AppBoxes entirely within the user space.
- **Reproducibility.** NX AppHub builds AppBoxes locally from YAML definitions and curated Debian-based repositories: no third-party binaries, deterministic results.
- **Sandboxing by default.** AppBoxes can include Firejail, AppArmor, or Bubblewrap profiles. Flatpaks run sandboxed. Distrobox isolates containers by design.
- **No package manager conflicts.** Traditional package managers scatter files across the filesystem and create dependency conflicts over time. The rootless model eliminates this.
 
### The Components

 **NX AppHub:** The orchestrator of the user layer. Unlike a traditional package manager that scatters files across the root filesystem, NX AppHub CLI manages applications as discrete, atomic units. It ensures that installing a calculator app never requires root privileges or risks breaking the display driver. **AppBoxes:** Our vision of the ideal format for Nitrux. A declarative workflow wraps AppBoxes. - **Reliability:** They are purpose-built for the Nitrux runtime, prioritizing native stability and optimization over universal compatibility. They integrate seamlessly into the desktop while remaining physically isolated from the core.
- **Isolation**: They run within **Bubblewrap** (GUI) or **AppArmor/Firejail** (CLI) environments.
- **Determinism:** Built locally from verifiable upstream packages, ensuring a transparent chain of trust free from opaque binaries.
- **Efficiency:** They function as a modular system overlay, bundling only what the host lacks to avoid redundancy and bloat.
 
 **Flatpak** and **Distrobox** are supported as a secondary universal standard for applications not yet available as AppBoxes, adhering to the same rootless, sandboxed principles. ### Information

 See [Software Management](https://nxos.org/documentation/software-management/) for detailed information.
