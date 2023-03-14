#!/bin/bash

## This script installs and configures Xdebug in the Apache container.

# Exit immediately on errors.
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PROJECT_ROOT=`echo $SCRIPTPATH | rev | cut -d'/' -f2- | rev`
source $PROJECT_ROOT/.env

echo "*** Installing Xdebug ***"

pecl install xdebug

# /usr/lib64/php/modules/xdebug.so
cat << EOT > /etc/php.d/xdebug.ini
zend_extension="/usr/lib64/php/modules/xdebug.so"
xdebug.mode=debug
xdebug.client_host=${XDEBUG_REMOTE_HOST}
xdebug.client_port=9000
xdebug.remote_log="/tmp/xdebug.log"'
EOT

#echo 'xdebug.idekey="PHPSTORM"' >> /etc/php.d/xdebug.ini

exit 0
