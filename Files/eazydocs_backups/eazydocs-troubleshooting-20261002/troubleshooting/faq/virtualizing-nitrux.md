---
title: "Virtualizing Nitrux"
eazydocs_id: 14775
---

Starting with Nitrux 5.0.0, we have decided to discontinue integration and support features intended for running Nitrux as a guest OS. While Nitrux will continue to run in most hypervisors, components such as SPICE integration, the QXL X.Org driver, and the Hyper-V modules, daemons, and OpenRC services are no longer included. Despite our previous efforts to provide guidance, we repeatedly encountered users applying incorrect configurations, further limiting performance in already-constrained virtualized environments, among other issues stemming from hypervisor quirks. **We never intended Virtual Machines to be the platform for using Nitrux**. Nitrux is tuned and optimized for physical systems, where users can experience its design, performance, and intent as we built it.

### Important Notes

 Bug reports regarding running Nitrux as a guest VM will be closed.
