#!/bin/bash

## This script exports the Scientific Discoveries database and compresses it to a .gz archive.
##
## To run this script from the host machine, execute the command:
## docker exec -it -w /var/www/public/scripts usda_ars_apache ./run_sci_disc_db_export.sh

# Exit immediately on errors.
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PROJECT_ROOT=`echo $SCRIPTPATH | rev | cut -d'/' -f2- | rev`

echo "*** Rebuilding the Scientific Discoveries DB cache ***"
cd $PROJECT_ROOT/docroot/sites/scientific-discoveries
drush cr
echo "*** Performing Scientific Discoveries DB export ***"
drush sql-dump > $PROJECT_ROOT/db/scientific_discoveries_snapshot.sql
gzip -f $PROJECT_ROOT/db/scientific_discoveries_snapshot.sql
