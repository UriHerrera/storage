---
title: "Session Daemons"
eazydocs_id: 15164
---

Nitrux includes background daemons and services that run during the user session to automate system behavior.

### NX Power Daemon

 `nx-powerd` combines Nitrux's dynamic power-profile management and battery notifications. - Uses the performance profile when connected to AC power.
- Uses power-saver, balanced, or performance profiles based on remaining battery capacity.
- Adjusts brightness when the power profile changes.
- Provides notifications for AC and battery transitions, critical battery levels, charging status, and battery-care reminders.
 
 The default profile thresholds are power-saver at 20% or below, balanced from 21% to 59%, and performance at 60% or above. The configuration file is `~/.config/nx-powerd/nx-powerd.conf`. ### Hyprscreend

 `hyprscreend` is a daemon developed by Nitrux that manages Hyprland monitor modes, scaling, power-dependent refresh rates, and external display hotplugging. - **Internal displays** → Selects the preferred monitor mode, adjusts scaling, and changes the refresh rate according to the power source.
- **External displays** → Detects connected displays and configures their preferred mode, position, and scaling automatically.
 
 The former `hyprextscreend` daemon is now integrated into `hyprscreend`. The configuration file is `~/.config/hyprscreend/hyprscreend.conf`. ### NX AppHub Daemon

 `nx-apphubd` integrates AppBoxes generated with the NX AppHub CLI into the desktop environment. It watches `~/.local/bin/nx-apphub`, validates AppBoxes, creates desktop entries, installs application icons, and creates shell aliases for command-line applications. ### KDE and Nitrux Integration Daemons

 For integration with Qt and Maui applications, Nitrux starts the following services: - **`kded6`** → KDE background services daemon.
- **`ksecretd`** → Secret Service API provider and KWallet integration.
- **`polkit-nx-agent`** → Graphical authentication prompts.
- **`MauiManServer`** → Synchronizes global desktop settings used by Maui and Qt applications.
- **`maui-bluetooth-obex-agent`** → Provides Bluetooth file transfers through OBEX.
 
 `kwalletd6` is not started as a standalone user service. KWallet is initialized through the PAM and D-Bus integration. ### Hyprland Session Daemons

- **`hypridle`** → Dims the screen, locks the session, turns off the display, and suspends the system according to the configured idle timers.
- **`hyprpaper`** → Displays the desktop wallpaper.
- **`hyprsunset`** → Provides the blue light filter. The default profile uses the normal display mode at 07:00 and a 5800K color temperature with 0.8 gamma at 19:00.
 
 The Hyprland daemon configuration files are stored in `~/.config/hypr/`. ### NudgeOSD

 `nudge-osd` is a QML-based on-screen display for keyboard shortcuts and system feedback. It provides volume, mute, and brightness notifications and listens for commands through D-Bus. The configuration file is `~/.config/nudge-osd/nudge-osd.conf`. ### dmemcg-booster

 `dmemcg-booster` manages device-memory cgroup settings to prioritize VRAM protection for selected applications. In Nitrux, it runs in agent mode and uses Hyprland as its focus provider to track the currently focused application. ### PipeWire and WirePlumber

- **`pipewire`** → Main multimedia and audio server.
- **`pipewire-pulse`** → PulseAudio-compatible PipeWire server.
- **`wireplumber`** → Session and policy manager for PipeWire.
 
 Nitrux also provides low-latency PipeWire settings and Bluetooth audio policies. ### XDG Desktop Portal

 `xdg-desktop-portal` provides desktop integration for sandboxed applications, including file selection, screen sharing, and other portal interfaces. It starts after PipeWire and WirePlumber. ### GameMode

 `gamemoded` applies temporary system optimizations when applications request GameMode, primarily for games. ### OpenRazer Daemon

 `openrazer-daemon` provides userspace support for compatible Razer peripherals. ### Information

 These services are registered as user-level OpenRC services and start automatically with the workspace session. Most users do not need to change their configuration.
