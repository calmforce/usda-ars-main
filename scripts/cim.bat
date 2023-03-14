@ECHO OFF

:: Execute 'drush cim' inside the apache Docker container.

docker exec -it -w /var/www/public usda_ars_apache drush cim
