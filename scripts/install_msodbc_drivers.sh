#!/bin/bash

## This script installs all the Microsoft drivers for PHP for SQL Server
## to enable the Drupal application to connect to an external MSSQL database.
##
## To run this script from the host machine, execute the command:
## docker exec -it -w /var/www/public/scripts usda_ars_apache ./install_msodbc_drivers.sh

# Exit immediately on errors.
set -e

echo "*** Installing Microsoft drivers for PHP for SQL Server ***"

yum install -y https://rpms.remirepo.net/enterprise/remi-release-7.rpm
yum install -y yum-utils
yum-config-manager --enable remi-php74
yum update -y
yum install -y php php-pdo php-xml php-pear php-devel re2c gcc-c++ gcc

curl https://packages.microsoft.com/config/rhel/7/prod.repo > /etc/yum.repos.d/mssql-release.repo
ACCEPT_EULA=Y yum install -y msodbcsql17
ACCEPT_EULA=Y yum install -y mssql-tools
yum install -y unixODBC-devel

pecl install sqlsrv
pecl install pdo_sqlsrv
echo extension=pdo_sqlsrv.so >> /etc/php.d/30-pdo_sqlsrv.ini
echo extension=sqlsrv.so >> /etc/php.d/20-sqlsrv.ini

exit 0
