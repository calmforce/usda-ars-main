#!/bin/bash

## This script exports the Tellus database and compresses it to a .gz archive.
##
## To run this script from the host machine, execute the command:
## docker exec -it -w /var/www/public/scripts usda_ars_apache ./run_tellus_db_export.sh

# Exit immediately on errors.
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PROJECT_ROOT=`echo $SCRIPTPATH | rev | cut -d'/' -f2- | rev`

echo "*** Rebuilding the Tellus DB cache ***"
cd $PROJECT_ROOT/docroot/sites/tellus
drush cr
echo "*** Performing Tellus DB export ***"
drush sql-dump > $PROJECT_ROOT/db/tellus_snapshot.sql
gzip -f $PROJECT_ROOT/db/tellus_snapshot.sql
