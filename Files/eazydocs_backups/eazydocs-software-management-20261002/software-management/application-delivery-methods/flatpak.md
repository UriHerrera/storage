---
title: "Flatpak"
eazydocs_id: 16168
---

Flatpaks provide access to the wider Linux ecosystem through **Flathub**. They operate entirely in user space, store system-wide installations in `/var/lib`, respect the immutable root, and are the recommended path for general-purpose GUI applications that may not yet have a native AppBox recipe.

### Documentation

 Flatpak Documentation: <https://docs.flatpak.org/en/latest/>### Information

 Users who want to use bleeding-edge Flatpaks can enable the Flathub-beta channel. **Do it at your own risk**. ```
flatpak remote-add --user flathub-beta https://flathub.org/beta-repo/flathub-beta.flatpakrepo
```
