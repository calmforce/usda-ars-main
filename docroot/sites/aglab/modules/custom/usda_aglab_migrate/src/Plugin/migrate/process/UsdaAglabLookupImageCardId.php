<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Row;

/**
 * Process plugin for lookup node ID.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_lookup_image_card_id"
 * )
 */
class UsdaAglabLookupImageCardId extends MigrationLookup {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      $this->configuration['migration'] = 'usda_aglab_migrate_external_links_image_cards';
      $nid = parent::transform($value, $migrate_executable, $row, $destination_property);
    }
    else {
      $this->configuration['migration'] = 'usda_aglab_migrate_articles';
      $nid = parent::transform($value, $migrate_executable, $row, $destination_property);
    }
    if (!$nid) {
      // Throw exception, so entire node is skipped.
      $message = 'No nid found for image card in both node migrations';
      throw new MigrateSkipRowException($message);
    }
    return $nid;
  }

}
