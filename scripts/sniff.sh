#!/bin/bash

## This script runs PHP Code Sniffer on project directories.

# Exit immediately on errors.
set -e

SCRIPT_PATH="$( cd "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
PROJECT_ROOT=`echo $SCRIPT_PATH | rev | cut -d'/' -f2- | rev`

$PROJECT_ROOT/vendor/bin/phpcs \
  --standard="Drupal,DrupalPractice" -n \
  --extensions="php,module,inc,install,test,profile,theme" \
  $PROJECT_ROOT/docroot/modules/custom \
  $PROJECT_ROOT/docroot/themes/custom
