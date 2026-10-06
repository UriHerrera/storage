---
title: "Desktop Defaults"
eazydocs_id: 15094
---

Nitrux assembles its desktop environment from individual Wayland-native components rather than using a traditional integrated desktop like GNOME or KDE Plasma.

### Components

- **Window manager** → Hyprland (tiling Wayland compositor).
- **Session manager** → Nitrux Workspace Session Manager.
- **Status bar** → Valenz.
- **Dock** → Marina.
- **Login manager** → greetd + QMLGreet.
- **Lock screen** → Desklock.
- **Idle daemon** → Hypridle.
- **Wallpaper** → Hyprpaper.
- **Application launcher/Clipboard manager** → Vicinae.
- **Notifications** → Valenz.
- **Session menu** → QMLogout.
- **Display configuration** → Hyprscreend (hyprscreend-cpp).
- **On-screen display** → NudgeOSD.
 
### Design Choices

 This approach provides: - **Modularity**: Each component handles a single responsibility.
- **Wayland-native**: No X11 dependencies or compatibility layers.
- **Consistency**: Unified appearance through the Catppuccin Mocha color scheme.
- **Performance**: Lightweight components with minimal resource overhead.
 
### Important Notes

 X11 sessions are not supported. Custom Wayland sessions are technically supported; however, they depend on the user configuring them.
