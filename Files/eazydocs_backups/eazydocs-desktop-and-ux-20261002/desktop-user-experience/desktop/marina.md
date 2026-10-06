---
title: "Marina"
eazydocs_id: 16112
---

Marina is a QML-based workspace dock designed for Nitrux. Built with MauiKit and LayerShell-Qt, it provides application launchers, window tracking, and workspace integration for Hyprland.

### Appearance

- **Position** → Bottom of the screen.
- **Icon size** → 40px.
- **Edge margin** → 5px.
- **Width and height** → Adjusted automatically.
 
### Window Controls

- **Left-click** → Launch an application or focus its window.
- **Left-click on an application with multiple windows** → Cycle through its windows.
- **Middle-click** → Open a new window.
- **Right-click** → Open the application menu.
- **Drag a pinned launcher** → Reorder the launcher.
 
### Launcher Menu

 The launcher's contextual menu provides the following actions: - **Open new window** → Launch another instance of the application.
- **Pin or unpin launcher** → Add or remove the application from the dock.
- **Tile or untile window** → Toggle the active window between tiled and floating.
- **Close** → Close all windows belonging to the application.
 
### Indicators

- **Running indicator** → Shows when an application is running.
- **Window count** → Displays the number of open windows when more than three are running.
- **Unread messages** → Displays launcher-entry message counts when supported by the application.
- **Launch indicator** → Shows while an application is starting.
 
### Usage

 Launcher shortcuts are disabled by default. When enabled, the following Hyprland keybinds are available: - `Super + F12` → Hold to activate launcher shortcut mode.
- `Super + Ctrl + 1–0` → Launch pinned launcher 1–10.
 
 The default configuration uses a 2.3-second hold delay and keeps launcher shortcut mode active for 995ms. The default keybind for restarting Marina is `Super + Shift + D`. ### Behavior

- **Auto-hide** → Disabled.
- **Auto-hide delay** → 500ms when enabled.
- **Fullscreen behavior** → Marina is hidden while a window is fullscreen.
- **Window tracking** → Updates automatically when applications, windows, workspaces, or displays change.
 
### Configuration

 Marina stores its user configuration in: `~/.config/marina/marina.conf` Marina monitors its configuration file and automatically applies configuration changes. ### Information

 Marina is developed by Nitrux and released under the BSD-3-Clause license.
