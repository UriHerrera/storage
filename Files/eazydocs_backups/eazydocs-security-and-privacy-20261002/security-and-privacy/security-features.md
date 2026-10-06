---
title: "Security Features"
eazydocs_id: 14842
---

**Application Sandboxing**- **AppBoxes &amp; Flatpaks**: AppBoxes (CLI and GUI) and Flatpaks are isolated with [Bubblewrap](https://github.com/containers/bubblewrap) or [Firejail](https://firejail.wordpress.com/), providing lightweight namespace sandboxes that prevent unauthorized access to user data and system files.
- **Other Executables**: We utilize [AppArmor](https://apparmor.net/) and [Firejail](https://firejail.wordpress.com/) to restrict the capabilities of standard executables. This way ensures they operate with the principle of least privilege.
 
 **Network Security**- **Firewall Management**: Nitrux includes [Firewalld](https://firewalld.org/), which is managed via **Cinderward**, making it easy to configure traffic rules.
- **VPN Support**: NetworkManager comes pre-configured with plugins for [OpenVPN](https://openvpn.net/), [OpenConnect](https://www.infradead.org/openconnect/), and [OpenFortiVPN](https://github.com/adrienverge/openfortivpn).
- **WireGuard**: Nitrux supports [WireGuard](https://www.wireguard.com/) for high-performance encrypted tunnels, which are managed via **Wirecloak** or **wg-quick**, making it easy to select tunnels.
- **Encrypted DNS**: Nitrux uses [dnscrypt-proxy](https://github.com/DNSCrypt/dnscrypt-proxy) by default, which encrypts DNS queries between your machine and the DNS resolver.
 
 **Filesystem Integrity**- **Immutable Root**: The system core is read-only by default to prevent tampering and ensure stability. However, users can still perform persistent modifications when necessary.
 
 **Password Management**- **KWalletManager**: We use it to securely store and manage system credentials.
