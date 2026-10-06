---
title: "F2FS Features in Nitrux"
eazydocs_id: 14852
---

Since Nitrux 2.8.0, the `/home` and `/var/lib` partitions use F2FS. These F2FS-formatted partitions will also use the following additional filesystem features.

- Compression using zstd with compression at level 6. The primary goal is to reduce writes to extend the flash lifetime and, potentially, to achieve a slight performance increase.
- Verification of compressed blocks using a checksum to avoid corruption.
- Enable a better garbage collector and asynchronous garbage collection.
- Avoid synchronously updating access or modification times, which improves I/O performance and flash durability.
- The filesystem applies encryption at the directory level, and different directories can use different encryption keys.
 
### Important Notes

 Since Nitrux 5.1.0, we provide our own implementation of `mkfs.f2fs` so that new F2FS partitions can support compression. However, existing F2FS partitions cannot be "made" to support it, unless the user recreates them.
