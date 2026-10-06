---
title: "QMLogout"
eazydocs_id: 16115
---

QMLogout is a session management menu for Nitrux built with MauiKit.

### Appearance

- **Overlay opacity** → 76% by default.
- **Icon mode** → System icons by default, with optional Nerd Font symbols.
- **Avatar** → Uses the configured avatar, the user's `~/.face` or `~/.face.icon`, AccountsService, or a built-in fallback image.
 
### Session Actions

- **Logout** → Ends the graphical session through nwsm.
- **Lock** → Starts Desklock.
- **Reboot** → Restarts the system.
- **Suspend** → Suspends the system when suspend is available.
- **Hibernate** → Hibernates the system when sufficient swap space is available.
- **Shutdown** → Powers off the system.
 
 Suspend is available when active swap space is detected. Hibernate requires swap space at least twice the size of system memory. ### Configuration

 QMLogout stores its user configuration in: `~/.config/qmlogout/qmlogout.conf`### Usage

 The default Hyprland keybind for opening QMLogout is: `Super + Shift + P`- **Arrow keys** → Move between actions.
- **Enter, Return, or Space** → Activate the selected action.
- **Escape** → Cancel and close QMLogout.
 
### Information

 QMLogout is licensed under the BSD-3-Clause license.
