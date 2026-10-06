---
title: "Is Nitrux right for me?"
eazydocs_id: 15921
---

Nitrux is a highly ***opinionated*** (i.e., building-focused software, strict boundaries, and removing variables) specialized workstation distribution. It is engineered around an immutable root, OpenRC, and a custom MauiKit-based user space. Because it intentionally breaks from traditional Linux paradigms to achieve its design goals, it is not a general-purpose distribution for everyone.

Nitrux is **not** the right fit for you if:

**You require a traditionally mutable system:**

 While root modifications are technically possible, they must be executed strictly through [NX Overlayroot](https://nxos.org/documentation/system-management/administration/nx-overlayroot/). If you expect to modify the root filesystem on the fly or reject the concept of an immutable base entirely, this architecture will frustrate your workflow. **You rely on host-level package managers:**

The root-level package manager is absent by design. You manage software via rootless or containerized solutions. If you dislike using NX AppHub (AppBoxes), Flatpak, or Distrobox containers, you will not enjoy managing software here.

**You prefer monolithic desktop environments:**

If your daily usage depends on GNOME, KDE Plasma, or a traditional Windows-like layout, using Nitrux will feel foreign.

**You require a `systemd` host:**

The base system utilizes OpenRC. Host-level services, background tasks, or tutorials relying on `systemctl` commands will not function here. While you can run `systemd` isolated within a Distrobox container, the core operating system strictly does not use it.

**You expect Debian behaviors:**

While built from a Debian rootfs, the package manager and core paradigms are removed during installation. Treating Nitrux like Debian will result in system conflicts.
