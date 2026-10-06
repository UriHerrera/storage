---
title: "NX AppHub"
eazydocs_id: 16167
---

NX AppHub is not a store or package manager; it is a software manufacturing system for users. It forms the foundation of Nitrux's user-level software management, employing a **reproducible**, **declarative**, and **rootless** model tailored to the distribution's immutable architecture. An AppBox is an **architectural policy** that dictates how an application component interacts with a specific, immutable host system, i.e., as an extension of the Nitrux root filesystem. AppBoxes use the AppImage format as a runtime and filesystem container (AppDir model). However, they intentionally do not follow the upstream portability-oriented best-practice profile (old-base build target, cross-distro universality, etc.). Operationally, in the NX AppHub ecosystem, a file is considered an AppBox only when all of the following are true:

- Sourced from a curated YAML definition in [NX AppHub Apps](https://github.com/Nitrux/nx-apphub-apps).
- That YAML targets the same Nitrux packaging baseline, rather than an arbitrary downgrade baseline for cross-distro universality.
- It uses trusted distribution repositories allowed by project policy (no third-party PPAs in curated definitions).
- It follows the AppBox runtime/sandbox policy documented for curated submissions.
 
### Features

- **Local, Deterministic Builds**: NX AppHub CLI builds the AppBoxes locally using curated YAML recipes. It does not download pre-compiled AppImages. Instead, it pulls verifiable `.deb` packages from trusted upstream repositories (Debian, Ubuntu, Devuan, Neon, and Nitrux). It assembles them into a self-contained binary, which ensures a verifiable chain of trust: you know exactly what is inside the box because your system built it.
- **Declarative Management**: We define applications by code (YAML) in the NX AppHub Apps repository. The system state is predictable because the recipe dictates the result.
- **FUSE 3 and Sandboxing**: All AppBoxes are built for modern FUSE 3 runtimes and enforce sandboxing by default. They leverage Firejail, AppArmor, or Bubblewrap to isolate the application from the host system, ensuring that user-space software cannot compromise the immutable core.
- **System Integration**: The NX AppHub Daemon watches the applications directory. When a new AppBox is built or installed, the daemon automatically integrates it into the desktop environment (menus, icons, and MIME type associations) without requiring root access or manual configuration.
- **Lifecycle Management**: AppBoxes intentionally do not support internal self-updates (e.g., AppImageUpdate). Instead, updates are handled centrally by the CLI, which "re-bakes" the AppBox using the latest upstream packages defined in the YAML recipe.
- **Flexibility**: Allows users to create custom local bundle builds.
- **64-bit Multi-Arch. Support**: NX AppHub CLI supports creating bundles for x86\_64 and aarch64 (ARM).
 
### Usage

 Run from the terminal: ```
nx-apphub-cli
```

### Commands

 ```
Commands                        Description
  
install                         Install one or more applications.
remove                          Remove one or more installed applications.
update                          Update one or more installed applications.
downgrade                       Downgrade one or more installed applications.
      --backup                  Non-interactive backup selection.
search                          Search for specific applications.
show                            Show installed applications.
build                           Build a bundle from a local YAML file.
      --appdir-lint             Optionally debug missing shared libraries in a bundle.
generate                        Generate YAML template from package metadata.
      --package                 Specify package name.
      --distro                  Choose the distribution from which to get metadata.
      --release                 The release of the selected distribution.
      --arch                    Specify the target architecture.
      --output                  The file name of the generated YAML file.
      --description-output      The file name of the generated metadata file.
```

 \[divider line\_type="no-line" custom\_height="5"\] ### Options

 ```
Options         Description
  
-h, --help      show this help message and exit
```

 \[divider line\_type="no-line" custom\_height="5"\] ### Documentation

 NX Apphub Wiki: <https://github.com/Nitrux/nx-apphub/wiki> NX AppHub Apps Wiki: <https://github.com/Nitrux/nx-apphub-apps/wiki> NX Apphub Daemon Wiki: <https://github.com/Nitrux/nx-apphubd/wiki>
