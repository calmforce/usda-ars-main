<?php

// For remote server.
$databases['migrate']['default'] = array (
  'database'  => 'ARSUmbraco',
  'username'  => 'ARSUmbraco',
  'password'  => '',
  'host'      => '10.9.255.104',
  'port'      => '1433',
  'namespace' => 'Drupal\\sqlsrv\\Driver\\Database\\sqlsrv',
  'driver'    => 'sqlsrv',
);

// For local server running within Docker container.
$databases['migrate']['default'] = array (
  'database'  => 'ARSUmbraco',
  'username'  => 'SA',
  'password'  => 'Drupal123',
  'host'      => 'usda_ars_mssql',
  'port'      => '1433',
  'namespace' => 'Drupal\\sqlsrv\\Driver\\Database\\sqlsrv',
  'driver'    => 'sqlsrv',
);
