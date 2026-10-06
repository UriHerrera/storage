---
title: "Nitrux Workspace Session Manager"
eazydocs_id: 16125
---

Nitrux Workspace Session Manager, a.k.a. nwsm, manages the Hyprland graphical session and OpenRC user services.

### Session Management

- **Wayland readiness** → Waits for a verified Wayland socket before starting desktop services.
- **Hyprland readiness** → Can require a valid `HYPRLAND_INSTANCE_SIGNATURE`.
- **Session D-Bus** → Starts the Wayland session inside its own D-Bus session.
- **Environment management** → Publishes Wayland, Hyprland, D-Bus, XDG, toolkit, and cursor environment variables.
- **Session recovery** → Detects compositor replacement and refreshes the graphical session environment.
- **Session restart** → Restarts the supplied Wayland session command when it exits or the compositor becomes unavailable.
 
### OpenRC User Services

 nwsm activates the OpenRC user `desktop` runlevel after the compositor is ready. Service definitions are discovered from: - `/etc/user/init.d` → System-provided user services.
- `~/.config/rc/init.d` → User-provided services.
 
 nwsm automatically registers newly installed services with the desktop runlevel and records registered services in: `~/.config/rc/.nwsm-services-registered` Services removed by the user are not automatically re-enrolled. ### Environment

 After the compositor becomes ready, nwsm publishes the session environment through `dbus-update-activation-environment`. This allows OpenRC user services and applications to access the current graphical session. The managed environment includes variables such as: - `DBUS_SESSION_BUS_ADDRESS`
- `DISPLAY`
- `WAYLAND_DISPLAY`
- `XDG_CURRENT_DESKTOP`
- `XDG_SESSION_DESKTOP`
- `XDG_SESSION_TYPE`
- `HYPRLAND_INSTANCE_SIGNATURE`
- `HYPRLAND_CMD`
- `QT_QPA_PLATFORM`
- `GTK_USE_PORTAL`
 
### Session Startup

 Nitrux starts Hyprland through nwsm with the following command: `NWSM_REQUIRED_VARS=HYPRLAND_INSTANCE_SIGNATURE nwsm -- /usr/bin/start-hyprland` After Hyprland starts, the session configuration runs: `nwsm finalize` This publishes compositor-provided environment variables that were not available when nwsm initially started the session. ### Commands

- `nwsm -- <wayland-session-command>` → Start and supervise a Wayland session.
- `nwsm finalize` → Publish the current compositor environment.
- `nwsm check` → Check whether an nwsm session is active.
- `nwsm status` → Display the current session status.
- `nwsm reconcile` → Discover newly installed services and reactivate the desktop runlevel.
- `nwsm stop` → Stop the active nwsm session.
 
### Configuration

 nwsm is configured through environment variables: - `NWSM_READY_TIMEOUT` → Maximum time to wait for the Wayland and compositor sockets. The default is 60 seconds.
- `NWSM_FINALIZE_GRACE` → Grace period for `nwsm finalize`. The default is 1 second.
- `NWSM_REQUIRED_VARS` → Comma- or space-separated environment variables that must be available before the session is considered ready.
- `NWSM_RESTART_ON_EXIT` → Controls whether the supplied Wayland session command is restarted after exiting. Restarting is enabled by default.
 
 Set `NWSM_RESTART_ON_EXIT=0` to disable automatic session-command restarts. ### Session Exit

 When the session ends, nwsm stops the OpenRC user `desktop` runlevel, restores the previous D-Bus activation environment, removes temporary compositor handoff data, and terminates the remaining session processes. The default Hyprland keybind for stopping the session is `Super + M`. ### Information

 Nitrux Workspace Session Manager is licensed under the BSD-3-Clause license.
