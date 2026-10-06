---
title: "KDE Wallet Information"
eazydocs_id: 15482
---

Nitrux uses KDE Wallet as the system secret storage service. KDE Wallet securely stores sensitive information, including network credentials, application tokens, and other authentication data. Applications access stored secrets through a standard interface instead of saving credentials in plain-text configuration files. KDE Wallet protects stored credentials, but it does not replace full disk encryption. Disk encryption protects the entire filesystem when the system is powered off, while KDE Wallet protects application secrets within the user session.

### Important Notes

 As of Nitrux 6.0.0, the user must unlock the wallet manually. Automatic unlocking requires that the wallet password match the user's login password, which removes the separation between system authentication and access to stored secrets. Requiring manual unlocking preserves that separation and ensures that access to credentials requires explicit user consent, so applications that store credentials may prompt for the wallet password the first time they need access to stored secrets during a session. After the wallet is unlocked, applications can retrieve stored credentials normally. KDE Wallet offers a legacy encryption option called **Classic (Blowfish)**, which is considered legacy cryptography. Modern wallets use stronger encryption, such as integration with **GnuPG**.

- *Nitrux recommends avoiding the Classic (Blowfish) wallet format when possible*.
 
After creating a wallet, we recommend **rebooting**. Otherwise, *kwalletd6* will fail to open the wallet; logging in and out is not enough; **this seems to be a bug in kwalletd6**.

For a detailed tutorial about using KWalletManager, see [Resources → Tutorials → Desktop](https://nxos.org/documentation/resources/tutorials/).
