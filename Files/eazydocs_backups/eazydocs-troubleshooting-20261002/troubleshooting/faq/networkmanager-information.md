---
title: "NetworkManager Information"
eazydocs_id: 14996
---

To add a wireless connection using the GUI (**recommended**), do the following steps:

- Open System Settings→KDE Wallet→click the button Launch Wallet Manager, or New in Automatic Wallet Selection.
- Enter a name for the *wallet* and select an encryption type.
- Enter a password for the *wallet* and click OK, then reboot.
- Log in and open System Settings→Wi-FI and Internet→Wi-Fi &amp; Networking, or left-click the network module in Waybar.
- Double left-click or right-click the wireless network, select Connect, and enter the wireless password.
- Enter the *wallet* password when prompted to open it and connect to the wireless network.
 
 Alternatively, users can use the command `nmtui` in the terminal (**fallback, not recommended**). Using the command `nmtui` will skip the KDE Wallet integration. Skipping KDE Wallet to store the network password will be significantly less secure, as NetworkManager stores the password in plain text. - Select "Activate a connection."
- Navigate to the chosen network using the arrow and tab keys, and select "&lt;Activate&gt;" or press Enter.
- Enter the password for the selected network, and select "&lt;OK&gt;" or press Enter.
 
 NetworkManager automatically configures Ethernet connections, and no user action is required. ### Important Notes

 Entering an incorrect password prevents the system from connecting to the network. As of Nitrux 6.0.0, the user must unlock the wallet manually; wireless networks do not connect automatically at login. If no wallet exists before adding a wireless network, the notification "*Failed to get secrets for &lt;Network&gt; No agents were available for this request.*" will appear; **this is not a bug in Nitrux**.  The notification only indicates that there is no encrypted database (i.e., *no wallet)* to store the secrets (i.e., *the password)*.
