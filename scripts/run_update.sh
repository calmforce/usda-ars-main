#!/bin/bash

## This script updates site after git pull, specifically:
## Applies any required database updates.
## Performs configuration import from config sync directory.
##
## To run this script from the host machine, execute the command:
## docker exec -it -w /var/www/public/scripts usda_ars_apache ./run_update.sh

# Exit immediately on errors.
set -e

# Read environment variables
SCRIPTPATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PROJECT_ROOT=`echo $SCRIPTPATH | rev | cut -d'/' -f2- | rev`
source $PROJECT_ROOT/.env

drush cr
echo "*** Applying any required database updates ***"
drush updb --yes
echo "*** Performing configuration import ***"
drush cim --partial --yes --source=config/partial
drush cim --yes
echo "*** Rebuilding the cache ***"
drush cr
