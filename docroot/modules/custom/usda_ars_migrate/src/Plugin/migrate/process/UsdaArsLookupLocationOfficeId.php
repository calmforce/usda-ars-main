<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Row;

/**
 * Process plugin for lookup term ID.
 *
 * Taxonomy term could be actually either Location or HQ Office, so we'll check both.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_lookup_term_id"
 * )
 */
class UsdaArsLookupLocationOfficeId extends MigrationLookup {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $migrations = [
      'usda_ars_migrate_locations',
      'usda_ars_migrate_top_site_pages',
    ];
    $term_id = NULL;
    foreach ($migrations as $migration) {
      $this->configuration['migration'] = $migration;
      if ($term_id = parent::transform($value, $migrate_executable, $row, $destination_property)) {
        break;
      }
    }
    return $term_id;
  }

}
