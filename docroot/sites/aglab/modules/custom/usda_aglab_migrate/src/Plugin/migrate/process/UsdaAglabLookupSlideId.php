<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Row;

/**
 * Process plugin for lookup media ID.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_lookup_slide_id"
 * )
 */
class UsdaAglabLookupSlideId extends MigrationLookup {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->configuration['migration'] = 'usda_aglab_migrate_media';
    $mid = parent::transform($value, $migrate_executable, $row, $destination_property);
    if (!$mid) {
      $this->configuration['migration'] = 'usda_aglab_migrate_external_video';
      $mid = parent::transform($value, $migrate_executable, $row, $destination_property);
    }
    return $mid;
  }

}
