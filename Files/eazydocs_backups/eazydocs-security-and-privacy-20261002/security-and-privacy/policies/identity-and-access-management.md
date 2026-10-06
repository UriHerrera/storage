---
title: "Identity and Access Management"
eazydocs_id: 14803
---

Policies related to user accounts, passwords, and authentication. **Deactivated Root Account**: By default, the root account is disabled (has no password set) in both the Live session and the installed system to enforce accountability via `sudo`. [**NIST Special Publication 800-63B Revision 4**](https://pages.nist.gov/800-63-4/sp800-63b.html)-compliant password policies. **Brute-Force Protection**: The system includes active countermeasures against guessing attacks:

- **Mandatory Delays**: The authentication module enforces a 4-second delay after every failed attempt to slow down automated attacks.
- **Lockout Policy**: The system locks accounts for 15 minutes after five consecutive failed attempts. It tracks failures for 15 minutes before resetting the counter.
- **Silent Auditing**: Logs failures for auditing but suppresses specific error details to deny attackers information.
