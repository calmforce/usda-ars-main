@ECHO OFF

:: Execute 'drush cex' inside the apache Docker container.

docker exec -it -w /var/www/public usda_ars_apache drush cex
