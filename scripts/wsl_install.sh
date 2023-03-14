#!/bin/bash

## This script installs and configures Composer version 1.10.13.

# Exit immediately on errors.
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PROJECT_ROOT=`echo $SCRIPTPATH | rev | cut -d'/' -f2- | rev`
source $PROJECT_ROOT/.env

sudo apt-get update
echo "*** Installing Git, PHP, and Make ***"
sudo apt install -y git php-cli php-dom php-gd php-curl unzip make
sudo add-apt-repository "deb http://archive.ubuntu.com/ubuntu focal-security universe"
sudo apt-get install php-mbstring
bash $SCRIPTPATH/wsl_install_composer.sh

exit 0
