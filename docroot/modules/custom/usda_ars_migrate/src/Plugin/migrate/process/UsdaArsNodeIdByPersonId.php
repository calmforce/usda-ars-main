<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\node\NodeInterface;

/**
 * Process plugin for person_profile node ID.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_ars_person_profile_node_id",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsNodeIdByPersonId extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   * @throws MigrateSkipProcessException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    return $this->getPersonProfileNodeIdByPersonId($value);
  }

  /**
   * Gets Person Profile Node ID by ARIS Person.
   *
   * @param string $person_id
   *  ARIS/REE PersonID.
   *
   * @return int
   *  The Profile ID.
   */
  protected function getPersonProfileNodeIdByPersonId(string $person_id) {
    $query = \Drupal::entityQuery('node');
    $result = $query
      ->condition('type', 'person_profile')
      ->condition('field_aris_person_id', $person_id)
      ->execute();
    return reset($result);
  }

}
