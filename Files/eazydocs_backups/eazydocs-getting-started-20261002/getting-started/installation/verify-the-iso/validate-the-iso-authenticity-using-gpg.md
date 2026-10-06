---
title: "Validate the ISO Authenticity using GPG"
eazydocs_id: 14698
---

Since Nitrux 3.2.1, our ISO images have been signed with a GPG key to ensure their authenticity. To verify the ISO's authenticity using GPG, do the following.

- Download the ISO and the signature file.
- Import the latest public key from the [changelog for the new release](http://nxos.org/changelog).
- Verify the signature using the command below.
 
 ```
gpg --verify path_to_sig_file.sig path_to_iso_file.iso
```

 Once you have verified the integrity and authenticity of the ISO file, flash it to a USB.
