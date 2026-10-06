---
title: "On-Screen Display (NudgeOSD)"
eazydocs_id: 15504
---

NudgeOSD is a QML-based on-screen display for keyboard shortcuts and system notifications designed for Wayland compositors and built with C++, MauiKit, and LayerShell-Qt.

### Usage

 NudgeOSD automatically appears on the monitor(s) when you press the volume or brightness keys, and uses arguments to use the system icon theme or Nerd Fonts for UI icons. ```
nudge-osd                           Daemon mode. Uses the configured icon mode.

nudge-osd --volume-down             Adjust volume (up or down) in steps.
          --volume-up
          --volume-mute             Mutes volume.
          --brightness-up           Adjust brightness (up or down) in steps.
          --brightness-down
```

### Information

 NudgeOSD is licensed under the BSD-3-Clause license.
