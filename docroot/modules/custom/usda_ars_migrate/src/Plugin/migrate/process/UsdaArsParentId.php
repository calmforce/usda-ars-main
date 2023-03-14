<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Umbraco Node ParentID type.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_ars_parent_id",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsParentId extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value == 31699) {
      // NationalProgramMain (id = 31699)
      // redirected to NationalProgramsPage, 1105.
      $value = 1105;
    }
    // ID 1111 is "ARS Locations" node and 1075 is Homepage
    // and -1 is top of everything.
    return $value == 1111 || $value == -1 ? 0 : $value;
  }

}
