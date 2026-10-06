---
title: "Can&#039;t install due to mounted Swap partitions"
eazydocs_id: 14652
---

In certain circumstances, such as virtualized environments, the system may automatically mount Swap partitions during a Live session boot. Calamares does not unmount Swap partitions automatically unless it's started like this:

```
sudo -E start-calamares
```

 \[divider line\_type="no-line" custom\_height="5"\] Note that running Calamares directly, i.e., `sudo calamares` will not unmount Swap partitions, and if the target device has mounted partitions, the installation will not continue.
