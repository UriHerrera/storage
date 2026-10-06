---
title: "Hyprland"
eazydocs_id: 14863
---

Nitrux ships with a preconfigured Hyprland environment.

### Appearance

- **Gaps** → 4px inner, 8px outer.
- **Border** → 1px; active gradient from cyan to green at 45°, gray inactive.
- **Corner rounding** → 16px.
- **Opacity** → 100% active, 80% inactive.
- **Blur** → Enabled, size 6, three passes, with opacity ignored.
- **Animations** → Enabled for windows, borders, fading, workspaces, and monitor changes.
- **Layout** → Dwindle with split preservation enabled.
 
### Input

- **Keyboard layout** → US.
- **Mouse sensitivity** → Default (0).
- **`epic-mouse-v1` Sensitivity** → -0.5.
- **Touchpad natural scroll** → Disabled.
- **Focus follows mouse** → Enabled.
- **Touchpad gestures** → Three-finger horizontal swipes switch workspaces.
 
### Idle and Lock (hypridle)

- **5 minutes (300s)** → Screen dims.
- **5 minutes 50 seconds (350s)** → Desklock activates.
- **8 minutes 20 seconds (500s)** → Display turns off.
- **10 minutes (600s)** → System suspends.
 
 The lock screen activates automatically before sleep and when the lid is closed. The display turns back on after resume. ### Wallpaper (hyprpaper)

 Default wallpaper: `/usr/share/wallpapers/Blossom/contents/images/4096x2304.png`### Autostart

 The default configuration provides the following user services and applications: - **Display** → Hyprscreend, Hyprpaper.
- **Desktop shell** → Marina dock, Valenz panel, and notification interface.
- **Session and locking** → Hypridle, Desklock.
- **Launcher and clipboard** → Vicinae.
- **Notifications and OSD** → Valenz, NudgeOSD.
- **Audio** → PipeWire, PipeWire Pulse, WirePlumber.
- **Portals** → XDG Desktop Portal.
- **Power and hardware** → NX Powerd, OpenRazer daemon, MauiManServer, Maui Bluetooth OBEX agent.
- **Background services** → NX AppHub daemon, GameMode, dmemcg-booster, KDED6, KSecretD, and the Nitrux PolicyKit agent.
- **Blue light filter** → Hyprsunset.
 
 Hyprland also runs `nwsm finalize`, `brightnessctl -s set 48000`, and `xdg-user-dirs-update` when the session starts. ### Keybinds

 These are the default keybinds in the Hyprland configuration. Nitrux uses `Super` (Windows key) as the main modifier. ### Applications

- `Super + Q` → Terminal (Station).
- `Super + E` → File manager (Index).
- `Super + W` → Web browser (Fiery).
- `Super + R` → Application launcher (Vicinae).
 
### Windows

- `Super + C` → Close window.
- `Super + V` → Toggle floating.
- `Super + F` → Toggle fullscreen.
- `Super + P` → Pseudotile.
- `Super + J` → Toggle split.
- `Super + Arrow keys` → Move focus.
- `Super + LMB drag` → Move window.
- `Super + RMB drag` → Resize window.
 
### Workspaces

 Hyprland does not use traditional maximize or minimize actions. Windows can be tiled, floating, or fullscreen. To hide a window, move it to another workspace. - `Super + 1–0` → Switch to workspace 1–10.
- `Super + Shift + 1–0` → Move the window to workspace 1–10.
- `Super + Ctrl + 1–0` → Activate Marina launcher 1–10.
- `Super + F12` → Hold the Marina launcher.
 
### Session

- `Super + L` → Lock the screen with Desklock.
- `Super + M` → Terminate the session.
- `Super + Shift + P` → Session options (QMLogout).
 
### Screenshots

- `Print` → Full screenshot.
- `Super + Print` → Selection screenshot.
- `Shift + Print` → Window screenshot.
 
 Toma handles screenshots. ### Clipboard

- `Super + Shift + V` → Show Vicinae clipboard history.
 
### Media Keys

- `XF86AudioRaiseVolume` → Volume up.
- `XF86AudioLowerVolume` → Volume down.
- `XF86AudioMute` → Toggle mute.
- `XF86AudioPlay` → Play/pause.
- `XF86AudioNext` → Next track.
- `XF86AudioPrev` → Previous track.
- `XF86MonBrightnessUp` → Brightness up.
- `XF86MonBrightnessDown` → Brightness down.
 
### Utilities

- `Super + Shift + D` → Restart Marina.
- `Super + Shift + W` → Restart Valenz.
- `Super + Shift + N` → Restart NudgeOSD.
 
### Configuration Files

- `~/.config/hypr/hyprland.lua` → Main Hyprland configuration.
- `~/.config/hypr/hypridle.conf` → Idle and lock behavior.
- `~/.config/hypr/hyprpaper.conf` → Wallpaper.
- `~/.config/hypr/hyprsunset.conf` → Blue light filter.
 
### Important Notes

 Hyprland Documentation: <https://wiki.hypr.land/>
