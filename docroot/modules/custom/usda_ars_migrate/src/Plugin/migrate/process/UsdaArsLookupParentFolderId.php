<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Row;

/**
 * Process plugin for lookup parent folder ID.
 *
 * Parent node could be migrated earlier as Top Level Folder or Subfolder.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_lookup_parent_folder_id"
 * )
 */
class UsdaArsLookupParentFolderId extends MigrationLookup {

  /**
   * {@inheritdoc}
   *
   * @throws MigrateSkipRowException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $migrations = [
      'usda_ars_migrate_site_folders',
      'usda_ars_migrate_site_subfolders',
      'usda_ars_migrate_content_pages',
      'usda_ars_migrate_person_site_pages',
    ];
    $parent_id = NULL;
    foreach ($migrations as $migration) {
      $this->configuration['migration']  = $migration;
      if ($parent_id = parent::transform($value, $migrate_executable, $row, $destination_property)) {
        break;
      }
    }
    return $parent_id;
  }

}
