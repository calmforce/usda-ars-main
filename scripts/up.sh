#!/bin/bash

## Start up docker containers.

# Exit immediately on errors.
set -e

docker-compose pull
docker-compose up -d --remove-orphans
