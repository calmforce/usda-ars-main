#!/bin/bash

## This script installs and configures Composer version 1.10.13.

# Exit immediately on errors.
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PROJECT_ROOT=`echo $SCRIPTPATH | rev | cut -d'/' -f2- | rev`
source $PROJECT_ROOT/.env

sudo rm -rf /root/.composer
sudo rm -f /usr/local/bin/composer
sudo rm -rf /usr/src/vendor/composer

echo "*** Installing Composer ***"

EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

sudo php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer --version=1.10.19
RESULT=$?
rm composer-setup.php

exit $RESULT
