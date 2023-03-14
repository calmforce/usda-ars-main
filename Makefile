include .env

default: help

## help	:	Print help for make commands.
.PHONY: help
ifneq (,$(wildcard docker.mk))
help : docker.mk
	@sed -n 's/^##//p' $<
else
help : Makefile
	@sed -n 's/^##//p' $<
endif

## up	:	Start up containers.
.PHONY: up
up:
	@echo "Pulling latest container images and starting up containers for $(PROJECT_NAME)..."
	docker-compose pull
	docker-compose up -d --remove-orphans

## down	:	Stop containers.
.PHONY: down
down: stop

## start	:	Start containers without updating.
.PHONY: start
start:
	@echo "Starting containers for $(PROJECT_NAME) from where you left off..."
	@docker-compose start

## stop	:	Stop containers.
.PHONY: stop
stop:
	@echo "Stopping containers for $(PROJECT_NAME)..."
	@docker-compose stop

## prune	:	Remove containers and their volumes.
##		You can optionally pass an argument with the service name to prune single container.
##		prune mariadb	: Prune `mariadb` container and remove its volumes.
##		prune mariadb varnish	: Prune `mariadb` and `varnish` containers and remove their volumes.
.PHONY: prune
prune:
	@echo "Removing containers for $(PROJECT_NAME)..."
	@docker-compose down -v $(filter-out $@,$(MAKECMDGOALS))

## ps	:	List running containers.
.PHONY: ps
ps:
	@docker ps --filter name='$(PROJECT_NAME)*'

## shell	:	Access `apache` container via shell.
##		You can optionally pass an argument with a service name to open a shell on the specified container
.PHONY: shell
shell:
	docker exec -it $(PROJECT_NAME)_$(or $(filter-out $@,$(MAKECMDGOALS)), 'apache') bash

## composer :	Executes `composer` command in a specified `COMPOSER_ROOT` directory (default is `/var/www/public`).
##		To use "--flag" arguments include them in quotation marks.
##		For example: make composer "update drupal/core --with-dependencies"
.PHONY: composer
composer:
	docker exec -it $(PROJECT_NAME)_apache composer --working-dir=$(COMPOSER_ROOT) $(filter-out $@,$(MAKECMDGOALS))

## drush	:	Executes `drush` command in a specified `DOCROOT` directory (default is `/var/www/public/docroot`).
##		To use "--flag" arguments include them in quotation marks.
##		For example: make drush "watchdog:show --type=cron"
.PHONY: drush
drush:
	docker exec -it $(PROJECT_NAME)_apache drush -r $(DOCROOT) $(filter-out $@,$(MAKECMDGOALS))

## logs	:	View containers logs.
##		You can optionally pass an argument with the service name to limit logs
##		logs apache	: View `apache` container logs.
.PHONY: logs
logs:
	@docker-compose logs -f $(filter-out $@,$(MAKECMDGOALS))

## run-export :	Exports configuration changes to config sync directory.
## 		Exports the database and compresses it to a .gz archive.
.PHONY: run-export
run-export:
	docker exec -it -w /var/www/public/scripts usda_ars_apache ./run_export.sh

## run-update :	Updates site after git pull, specifically:
## 		- Applies any required database updates.
##		- Performs configuration import from config sync directory.
.PHONY: run-update
run-update:
	docker exec -it -w /var/www/public/scripts usda_ars_apache ./run_update.sh

## db-import :	Imports the database from the latest snapshot in the repository.
.PHONY: db-import
db-import:
	docker exec -it -w /var/www/public/scripts usda_ars_apache ./run_db_import.sh

## db-export :	Exports the database and compresses it to a .gz archive.
.PHONY: db-export
db-export:
	docker exec -it -w /var/www/public/scripts usda_ars_apache ./run_db_export.sh

## sniff :	Runs PHP Codesniffer on custom module and custom theme directories.
.PHONY: sniff
sniff:
	docker exec -it -w /var/www/public/scripts usda_ars_apache ./sniff.sh
