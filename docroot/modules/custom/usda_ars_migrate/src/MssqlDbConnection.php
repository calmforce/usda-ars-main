<?php

namespace Drupal\usda_ars_migrate;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

/**
 * Base class for MSSQL DB Query Classes.
 */
class MssqlDbConnection {

  /**
   * The database object.
   *
   * @var Connection
   */
  protected $connection;

  /**
   * Gets the database connection object.
   *
   * @param string $db_name
       * The DB name.
   *
   * @return Connection
   *   The database connection.
   */
  public function getDatabase($db_name) {
    if (!isset($this->connection) || $this->connection->getKey() != $db_name) {
        $this->connection = $this->setUpDatabase(['key' => $db_name]);
    }
    return $this->connection;
  }

  /**
   * Gets a connection to the referenced database.
   *
   * This method will add the database connection if necessary.
   *
   * @param array $database_info
   *   Configuration for the source database connection. The keys are:
   *    'key' - The database connection key.
   *    'target' - The database connection target.
   *    'database' - Database configuration array as accepted by
   *      Database::addConnectionInfo.
   *
   * @return Connection
   *   The connection to use for this plugin's queries.
   *
   * @throws \Drupal\migrate\Exception\RequirementsException
   *   Thrown if no source database connection is configured.
   */
  protected function setUpDatabase(array $database_info) {
    $key = $database_info['key'];
    if (isset($database_info['target'])) {
      $target = $database_info['target'];
    }
    else {
      $target = 'default';
    }
    if (isset($database_info['database'])) {
      Database::addConnectionInfo($key, $target, $database_info['database']);
    }
    return Database::getConnection($target, $key);
  }

  /**
   * Returns connection member variable.
   *
   * @return Connection
   */
  protected function getConnection() {
    return $this->connection;
  }

}
