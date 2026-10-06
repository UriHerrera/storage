---
title: "Language Stratification"
eazydocs_id: 15428
---

Nitrux follows a deliberate language stratification model when developing native components. This model reflects the system's architectural priorities and ensures that each platform layer uses the language best suited to its role and responsibilities, based on architectural weight, performance sensitivity, and system depth.

This approach improves maintainability, preserves performance where it matters most, and keeps system components aligned with their intended scope.

The three primary languages used for Nitrux-developed components are:

- **C++**
- **Python**
- **Shell Script**
 
Each occupies a distinct layer in the system.

### Architectural Layers

**Performance-Critical Core**: C++ defines the performance-critical layer of Nitrux, where C++-implemented components handle functionality that requires high execution efficiency, predictable behavior, or tight integration with system-level services. These components typically form the structural backbone of Nitrux-specific tooling and subsystems.

Typical characteristics of this layer include:

- Performance-sensitive workloads.
- Long-lived system components.
- Deep integration with operating system facilities.
- Native user interface applications built with MauiKit and Qt.
 
**Structured Orchestration**: Python serves as the platform's orchestration layer, and we use it for components where clarity and maintainability matter more than raw execution speed. Python keeps complex workflows readable and adaptable while still supporting robust system interaction.

Typical characteristics of this layer include:

- System tooling and orchestration utilities.
- Automation tasks and helper services.
- Infrastructure that coordinates system components.
 
**Lifecycle and System Glue**: Shell scripting sits in the lifecycle layer when simplicity is required. These scripts typically manage system initialization, lifecycle hooks, packaging workflows, and other tasks that interact closely with the underlying environment.

Typical characteristics of this layer include:

- Boot and initialization logic.
- Lifecycle and system integration hooks.
- Packaging tooling and system maintenance tasks.
 
### Graphical Interfaces

While system components follow the language stratification described above, graphical interfaces follow a separate rule:

- **All native graphical interfaces specifically built for Nitrux should use MauiKit.**
 
MauiKit provides a consistent interface framework across the platform and lets applications integrate naturally with the Nitrux environment, ensuring graphical tools remain visually coherent and aligned with the system's design principles. In this case, "native" is a strict architectural label (not a quality judgment).
