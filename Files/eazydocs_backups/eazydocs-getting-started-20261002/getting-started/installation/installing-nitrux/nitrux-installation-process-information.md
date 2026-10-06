---
title: "Nitrux Installation Process Information"
eazydocs_id: 14632
---

Below is the information we consider necessary for users to read and understand before installing Nitrux on their computers.

1. The default username and password for the Live session are both: `nitrux`
2. When adding a username, i.e., "a name for the user," do not include spaces, special characters, or numbers.
3. When adding a hostname, i.e., "a name for the computer," do not include spaces or special characters.
4. Our Calamares configuration enforces strict password quality checks using libpwquality to ensure high account security.
5. When creating the system administrator account, users must define a password that is at least 12 characters long and includes a mix of four character classes: uppercase letters, lowercase letters, numbers, and symbols. Additionally, the system rejects passwords that contain common patterns, username substrings, or banned words (e.g., "nitrux," "calamares," or "password").
6. We recommend using a password generator application (mobile or desktop) or a website. **Please avoid using simple passwords**, such as 1234567890aB!, as they will not pass the password quality checks (they fail the dictionary check). Alternatively, run the following command to generate a usable password directly from the terminal.
 
 ```
pwgen -ys 15 1
```

 \[divider line\_type="no-line" custom\_height="5"\] To view Calamares's verbose output during installation, click the icon next to the progress bar or start Calamares from the Terminal. To start Calamares from the Terminal, run the following command. ```
sudo -E start-calamares
```

 \[divider line\_type="no-line" custom\_height="5"\] ### Important Notes

 Users can bypass the password quality check during installation by unchecking "Enable password strength validation." However, **we strongly recommend leaving this enabled**. Please be aware that this is a one-time exception. Once installation is complete, the system's high-security requirements enforce strong passwords for all new accounts. Valid hostname characters are ASCII letters a-z, digits 0-9, and the hyphen (-). Neither a hostname nor a username may start with a hyphen.
