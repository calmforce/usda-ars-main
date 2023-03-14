@ECHO OFF

:: Start up docker containers.

docker-compose pull
docker-compose up -d --remove-orphans
