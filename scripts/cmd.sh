#!/bin/bash

## This script is configured to be executed by an override to the default
## startup command for the apache container. It executes custom scripts and
## commands and then triggers the default startup command for the container.

# Exit immediately on errors.
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PROJECT_ROOT=`echo $SCRIPTPATH | rev | cut -d'/' -f2- | rev`

# Install useful packages.
yum -y install nano

# Check if install_msodbc_drivers.sh was already executed in this container.
if [ ! -f /etc/php.d/20-sqlsrv.ini ] ; then
  bash $SCRIPTPATH/install_msodbc_drivers.sh
fi

# Check if install_xdebug.sh was already executed in this container.
if [ ! -f /etc/php.d/xdebug.ini ] ; then
  bash $SCRIPTPATH/install_xdebug.sh
fi

rm -f /etc/httpd/conf.d/000-virtual-hosts.conf

# Check if install_virtual_hosts.sh was already executed in this container.
if [ ! -f /etc/httpd/conf.d/000-virtual-hosts.conf ] ; then
  bash $SCRIPTPATH/install_virtual_hosts.sh
fi

# Install additional useful packages
bash /run-httpd.sh    # Run default command for container
