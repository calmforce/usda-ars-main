#!/bin/bash

## This script exports configuration changes to config sync directory.
##
## To run this script from the host machine, execute the command:
## docker exec -it -w /var/www/public/scripts usda_ars_apache ./run_export.sh

# Exit immediately on errors.
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PROJECT_ROOT=`echo $SCRIPTPATH | rev | cut -d'/' -f2- | rev`

echo "*** Performing configuration export ***"
drush cex --yes
cp $PROJECT_ROOT/docroot/config/default/config_ignore.settings.yml $PROJECT_ROOT/docroot/config/partial
cp $PROJECT_ROOT/docroot/config/default/core.extension.yml $PROJECT_ROOT/docroot/config/partial
cp $PROJECT_ROOT/docroot/config/default/lightning_core.versions.yml $PROJECT_ROOT/docroot/config/partial
bash $SCRIPTPATH/run_db_export.sh
