#! /bin/bash
# shellcheck disable=SC2034
PREREQS=""

# set -x

# -- Find devices based on what driver they are using.

nv_drv=pci:nvidia
amd_drv=pci:amdgpu

find_deviceDriver () {
    if find /sys/bus/pci/devices/*/driver/module/drivers/ | grep -q 'nvidia'; then
        echo "$nv_drv"
            else
                find /sys/bus/pci/devices/*/driver/module/drivers/ | grep -q 'amdgpu'
        echo "$amd_drv"
    fi
}

dev_drv=$(find_deviceDriver)

# -- Use the resulting value to look for the correct devices.

vga_dev_drv=$(find /sys/bus/pci/devices/*/driver/module/drivers/"$dev_drv")

# -- Get Domain:Bus:Device.Function of the secondary adapter.
# -- FIXME Need to find a a better way to filter the Domain:Bus:Device.Function from the string.
# -- However, to properly use this feature the system needs to have two individual graphics cards.

vga_dev_drv_id=$(echo "$vga_dev_drv" | cut -c22- | rev | cut -c101- | rev)
echo "$vga_dev_drv_id"

# -- Check wether the device is the secondary adapter.

boot_vga=$(find /sys/devices/pci* -name boot_vga)
boot_vga_id=$(echo "$boot_vga" | grep -o "$vga_dev_drv_id")

# -- Unbind the secondary adapter.
# -- MUST BE RUN DURING INIT.

dev_drv_id=$(echo "$dev_drv" | cut -c5- | rev | cut -c1- | rev)
echo "$boot_vga_id" > /sys/bus/pci/drivers/"$dev_drv_id"/unbind

# -- Alternatively, end user session

# pkill -u "$USER"
