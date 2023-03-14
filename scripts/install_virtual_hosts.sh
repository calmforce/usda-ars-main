#!/bin/bash

## This script installs and configures VirtualHost items in Apache container.

# Exit immediately on errors.
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PROJECT_ROOT=`echo $SCRIPTPATH | rev | cut -d'/' -f2- | rev`
source $PROJECT_ROOT/.env

echo "*** Installing VirtualHost items ***"
# Listen 80

#<VirtualHost *:80>
#    ServerAdmin webmaster@localhost
#    DocumentRoot ${DOCROOT}
#    ErrorLog ${APACHE_LOG_DIR}/error.log
#    CustomLog ${APACHE_LOG_DIR}/access.log combined
#</VirtualHost>

cat << EOT > /etc/httpd/conf.d/000-virtual-hosts.conf

Listen 8080
Listen 8181
Listen 8282
Listen 44300

<VirtualHost *:8080>
    DocumentRoot ${DOCROOT}
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
<VirtualHost *:8181>
    DocumentRoot ${DOCROOT}
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
<VirtualHost *:8282>
    DocumentRoot ${DOCROOT}
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
<VirtualHost *:44300>
    DocumentRoot ${DOCROOT}
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

EOT

exit 0
