---
title: "Session Hardening"
eazydocs_id: 14819
---

Policies that enforce secure environments for user processes and resource usage.

- **Default Permissions (Umask)**: The system enforces strict file creation masks, preventing users from creating world-writable files by default.
- **Resource Limits**: We strictly enforce system limits during login to prevent Denial of Service (DoS) attacks caused by resource exhaustion.
- **Keyring Management**: The system initializes session keyrings and forces revocation upon logout, which prevents cryptographic keys from leaking between sessions.
- **Session Tracking**: We register user sessions with the login manager, ensuring proper seat management without relying on systemd.
