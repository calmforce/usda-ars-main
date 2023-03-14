#!/bin/bash

## This script exports the database and compresses it to a .gz archive.
##
## To run this script from the host machine, execute the command:
## docker exec -it -w /var/www/public/scripts usda_ars_apache ./run_db_export.sh

# Exit immediately on errors.
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PROJECT_ROOT=`echo $SCRIPTPATH | rev | cut -d'/' -f2- | rev`

echo "*** Rebuilding the cache ***"
drush cr
echo "*** Performing DB export ***"
drush sql-dump > $PROJECT_ROOT/db/snapshot.sql
gzip -f $PROJECT_ROOT/db/snapshot.sql
