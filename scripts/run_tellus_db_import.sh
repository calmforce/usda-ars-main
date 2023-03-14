#!/bin/bash

## This script imports the Tellus database from the latest snapshot in the repository.
##
## To run this script from the host machine, execute the command:
## docker exec -it -w /var/www/public/scripts usda_ars_apache ./run_tellus_db_import.sh

# Exit immediately on errors.
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PROJECT_ROOT=`echo $SCRIPTPATH | rev | cut -d'/' -f2- | rev`

# Import DB
gunzip -cf $PROJECT_ROOT/db/tellus_snapshot.sql.gz > $PROJECT_ROOT/db/tellus_snapshot.sql
echo "*** Performing Tellus DB import ***"
cd $PROJECT_ROOT/docroot/sites/tellus
drush sql-drop -y
drush sql-cli -y < $PROJECT_ROOT/db/tellus_snapshot.sql
rm $PROJECT_ROOT/db/tellus_snapshot.sql
echo "*** Checking Tellus Drupal site status ***"
drush status
