---
title: "Valenz"
eazydocs_id: 16109
---

Valenz is a QML-based workspace shell bar designed for Nitrux. Built with MauiKit and LayerShell-Qt, it provides workspace navigation, window controls, system status, notifications, media controls, and quick settings for the Hyprland session.

### Bar Layout

 Valenz runs as an overlay above the desktop and can display on the active screen or on all screens. - **Left side** → Current workspace indicator, workspace navigation, and open-window tabs.
- **Center** → Focused window title, clock, date, weather, and calendar access.
- **Right side** → Media controls, system tray, screen capture, Vicinae, notifications, and Control Center.
 
 Valenz hides automatically when the focused window enters fullscreen mode. ### Workspace and Windows

- **Workspace indicator** → Displays the current workspace and total workspace count.
- **Workspace navigation** → Previous and next workspace buttons.
- **Window tabs** → Displays open windows with their title and icon.
- **Window focusing** → Clicking a window tab focuses that window.
 
### Clock, Weather, and Calendar

 The center of the bar displays the current time, date, weather icon, temperature, and location. Clicking the clock or weather display opens the calendar popup, which provides: - Monthly calendar navigation.
- Current weather details.
- Three-day weather forecast.
- Feels-like temperature, humidity, UV index, and wind speed.
- Optional calendar events when Agenda is installed.
 
 Weather data is retrieved from the Open-Meteo API. ### Media Controls

 Valenz uses MPRIS to display the currently active media player. - **Track information** → Displays the title, artist, timestamp, and album artwork.
- **Playback** → Previous track, play/pause, and next track.
- **Media sources** → Supports desktop MPRIS players and compatible Bluetooth media sources.
- **Source selection** → Allows switching between available media players.
 
 Media controls are hidden when no media player is active unless `Mpris/alwaysVisible` is enabled. ### System Tray

 Valenz includes a system tray for applications that provide StatusNotifierItem or legacy XEmbed tray icons. When available, Valenz starts `xembedsniproxy` to support legacy tray applications under Wayland. ### Notifications

 Valenz provides a desktop notification server through the `org.freedesktop.Notifications` D-Bus interface. - Displays notification bubbles.
- Stores notification history.
- Supports notification actions.
- Supports inline replies when provided by the application.
- Displays notification counts.
- Provides a Do Not Disturb mode.
 
### Launcher Integration

 Valenz includes buttons intended to launch a menu and a clipboard manager; by default, Valenz uses Vicinae, but this is configurable. - **Launcher** → `vicinae toggle`.
- **Clipboard** → `vicinae vicinae://launch/clipboard/history`.
 
### Control Center

 The Control Center provides quick access to system status and frequently used settings. - **Network** → Displays Wi-Fi status and network activity.
- **Bluetooth** → Displays Bluetooth status and connected devices.
- **Power profile** → Selects power-saver, balanced, or performance mode.
- **Volume** → Adjusts speaker volume and mute state.
- **Microphone** → Adjusts microphone volume when available.
- **Brightness** → Adjusts display brightness when supported.
- **Dark mode** → Toggles between the configured light and dark color schemes.
- **Camera** → Displays and controls camera privacy state when supported.
- **Night light** → Displays and controls the blue light filter when available.
- **Do Not Disturb** → Enables or disables notification suppression.
- **System resources** → Displays CPU, RAM, disk, and network usage.
- **Settings** → Opens Workspace Settings.
- **Shutdown** → Opens the session options provided by QMLogout.
 
### Screen Capture

 Valenz integrates with Toma for screenshots and screen recordings. - **Full screen** → Captures or records the entire screen.
- **Region** → Captures or records a selected region.
- **Window** → Captures or records a selected window.
 
### Appearance

- **Color schemes** → Catppuccin Latte Nitrux for light mode and Catppuccin Mocha Nitrux for dark mode by default.
- **Icon mode** → System icons at 16px.
- **Bar height** → 52px in the default configuration.
- **Screen placement** → All screens in the default configuration.
- **Layer spacing** → 5px top spacing and 9px left and right spacing.
 
### Configuration

 Valenz stores its user configuration in `~/.config/valenz/valenz.conf`. ### Information

 Valenz is developed by Nitrux and released under the BSD-3-Clause license.
