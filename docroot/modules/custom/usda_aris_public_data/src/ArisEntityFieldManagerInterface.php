<?php

namespace Drupal\usda_aris_public_data;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\search_api\IndexInterface;

/**
 * Defines an interface for a Solr field manager.
 */
interface ArisEntityFieldManagerInterface {

  /**
   * Gets the field definitions for a Solr server.
   *
   * * @param \Drupal\search_api\IndexInterface $index
   *   The Search Api index.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The Search Api index.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   The array of field definitions for the server, keyed by field name.
   */
  public function getFieldDefinitions(IndexInterface $index, MigrationInterface $migration): array;

}
