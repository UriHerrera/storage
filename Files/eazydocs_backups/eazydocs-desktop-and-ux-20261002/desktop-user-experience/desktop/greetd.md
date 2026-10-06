---
title: "greetd + QMLGreet"
eazydocs_id: 15111
---

Nitrux uses greetd as the login daemon and QMLGreet as the graphical greeter.

### What is greetd?

 greetd is a minimal, agnostic login daemon. It handles user authentication and session startup without being tied to a specific display server or desktop environment. ### What is QMLGreet?

QMLGreet is a modern, lightweight greeter (login screen) designed for greetd and built with **[MauiKit](https://mauikit.org/)** to deliver a polished, consistent user interface.

QMLGreet runs natively on Wayland compositors (such as Hyprland or Sway) using the Layer Shell protocol.

### Session support

 Nitrux only supports Wayland sessions (Hyprland). X11 sessions are not available, even though greetd technically supports them. ### Configuration

 The greetd configuration file is located at `/etc/qtgreet/config.ini`. The QMLGreet configuration file is located at `/etc/qmlgreet/qmlgreet.conf`. ### Important Notes

 greetd Documentation:[ https://man.sr.ht/~kennylevinsen/greetd/](https://man.sr.ht/~kennylevinsen/greetd/)
