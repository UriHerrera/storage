---
title: "AppImage Information"
eazydocs_id: 15459
---

Regarding AppImages in Nitrux, AppImages traditionally rely on a FUSE 2 runtime (`libfuse.so.2`). However, **FUSE 2 is considered obsolete and unmaintained**, and some distributions no longer ship it by default as the ecosystem transitions toward FUSE 3. While Nitrux supports AppImages that use FUSE 3, i.e., they *will run*, starting with Nitrux 5.0.0, we removed the AppImage integration daemon (*appimaged*). That said, **AppImages are not the only, nor the primary, means of software acquisition in Nitrux***.* As of Nitrux 6.0.0, we continue to include FUSE 2 to maintain compatibility with external AppImages in Nitrux. Nonetheless, we do not guarantee that FUSE 2 support will remain in future releases. With support removed, users who want to run AppImages that still depend on FUSE 2 can do so in a container environment.
