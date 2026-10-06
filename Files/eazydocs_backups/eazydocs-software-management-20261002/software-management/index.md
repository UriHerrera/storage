---
title: "Software Management"
eazydocs_id: 14562
---

Nitrux does not revolve around a package manager**.** Unlike conventional distributions, where a package manager (*apt*, *dnf*, *pacman*, etc.) serves as the central mechanism for managing software (*installing, updating, and removing*), Nitrux places **software management entirely outside the root filesystem**. Conventional package managers modify the root directly, making the system mutable. Nitrux deliberately avoids this model. Allowing a traditional package manager to alter the root defeats the purpose of immutability and reintroduces the unpredictability that immutability eliminates. By separating the base system from user-installed applications, Nitrux ensures that system upgrades remain deterministic and reliable. Even though we use `.deb` packages to build the root filesystem during development, the host system includes no *apt*, *dpkg*, or equivalent package manager. Users who attempt to install software the traditional way will find no mechanism to do so. Nitrux enforces this intentionally.

### Deliberate Pathways

 The architecture in Nitrux channels users toward methods that preserve system integrity: self-contained applications that live in user space, sandboxed runtimes that cannot modify the root, and containers that provide full package manager access without compromising the host. The officially supported methods for software management are: - **NX AppHub**: Nitrux's user-level software management system, with a reproducible, declarative, and rootless model designed specifically for our immutable architecture.
- **Flatpak**: Sandboxed applications distributed through Flatpak repositories.
- **Distrobox containers**: Full Linux environments that provide access to traditional package managers without modifying the host system.
 
 These pathways allow users to install and manage software while preserving the immutability of the base system. ### Important Notes

 For information about using AppImage in Nitrux, see [Troubleshooting → FAQ → AppImage Information](https://nxos.org/documentation/troubleshooting/faq/appimage-information/).
