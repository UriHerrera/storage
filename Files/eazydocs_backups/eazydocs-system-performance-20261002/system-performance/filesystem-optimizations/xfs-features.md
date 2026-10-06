---
title: "XFS Features in Nitrux"
eazydocs_id: 14850
---

Since [Nitrux 2.6.0 (January 2023)](https://nxos.org/changelog/release-announcement-nitrux-2-6-0/), the root directory is immutable and uses the XFS filesystem. This XFS-formatted partition will also use the following additional filesystem features.

- Allow the filesystem to place inodes anywhere in itself. Storing a file's inode at the same location as its data improves performance.
