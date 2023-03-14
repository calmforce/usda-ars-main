#!/bin/bash

## This script is a wrapper for run_update.sh.
## This script is to be executed on the host system, and it
## runs the run_update.sh script inside the apache Docker container.

# Exit immediately on errors.
set -e

docker exec -it -w /var/www/public/scripts usda_ars_apache ./run_update.sh
