---
title: "Init System"
eazydocs_id: 15010
---

Nitrux uses OpenRC as its init system instead of systemd.

### Why OpenRC?

 Nitrux follows the Unix philosophy: "do one thing and do it well." Systemd is more than an init system; **it's a software suite** that includes a device manager, login manager, network manager, logging daemon, and more. This scope conflicts with Nitrux's preference for focused, single-purpose tools. OpenRC is a process spawner that: - Utilizes the system-provided init.
- Provides process isolation.
- Supports parallelized startup.
- Handles service dependencies.
 
 It does what an init system should do without absorbing unrelated functionality. ### Documentation

 For usage and configuration, see the [OpenRC documentation](https://github.com/OpenRC/openrc/blob/master/user-guide.md). ### Information

 For a detailed tutorial on the service creation process, see [Resources → Tutorials → Services](https://nxos.org/documentation/resources/tutorials/).
