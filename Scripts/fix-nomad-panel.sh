#!/bin/bash
#
#   Copyright 2018 Uri Herrera <uri_herrera@nxos.org>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3,
#   or (at your option) any later version, as published by the Free
#   Software Foundation
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU LESSER GENERAL PUBLIC LICENSE for more details
#
#   You should have received a copy of the GNU LESSER GENERAL PUBLIC LICENSE
#   along with this program; if not, write to the
#   Free Software Foundation, Inc.,
#   51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
echo ''
echo '#####################################################################'
echo '# This Script will fix the bug with the top panel in Nomad Desktop. #'
echo '# Do not close the terminal until this process is completed.        #'
echo '#####################################################################'
echo ''

set -eu -o pipefail

sudo -n true
test $? -eq 0 || exit 1 'Please run this script as sudo.'

if dpkg -l | grep -E 'nomad-plasma-look-and-feel|nomad-desktop-settings' | awk '{print $2}' | xargs -n1 dpkg --purge > /dev/null; then
    rm ~/.config/plasma-org.kde.plasma.desktop-appletsrc ~/.config/latte/nomad.layout.latte 2> /dev/null && killall plasmashell latte-dock && rm -R ~/.cache/plasma*
    echo 'Success. Old packages and files have been removed.'
    echo ''
else
    echo 'Fail. Old packages and files were not removed.'
    echo ''
fi

if mkdir -p ~/apt_download && cd ~/apt_download
    wget -q -P ~/apt_download http://repo.nxos.org/development/pool/main/n/nomad-plasma-look-and-feel/nomad-plasma-look-and-feel_1.6.11_amd64.deb
    wget -q -P ~/apt_download http://repo.nxos.org/development/pool/main/n/nomad-desktop-settings/nomad-desktop-settings_1.4.7_amd64.deb; then
    dpkg -i -- *.deb > /dev/null 
    cd ../ && rm -R ~/apt_download
    echo 'Success. New packages have been installed.'
    echo ''
else
    echo 'Fail. New packages could not be installed.'
    echo ''
fi

cd /home
    for folder in *; do
    getent passwd $folder > /dev/null
        if [ $? -eq 0 ]; then
            sudo cp /etc/skel/.config/latte/nomad-top-panel.layout.latte "$folder/.config/latte/"
            sudo chown -hR $folder:$folder "$folder/.config/latte"
            sudo sed -i '/currentLayout=/c\currentLayout=nomad-top-panel' "$folder/.config/lattedockrc"
            sudo sed -i '/lastNonAssignedLayout=/c\lastNonAssignedLayout=nomad-top-panel' "$folder/.config/lattedockrc"
            echo 'Success. New layout is installed.'
            echo ''
        else
            echo 'Fail. New layout could not be installed.'
            echo ''
        fi
done

echo ''
printf "Do you want to reboot now? [Y/n] "
read r

[ $r = Y -o $r = y ] &&
	reboot
echo ''
