<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Umbraco National Programs Path.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_ars_np_path",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsNatProgramPath extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // NationalProgramMain (id = 31699)
    // redirected to NationalProgramsPage, id = 1105.
    // Replacing '31699' id in the path by '1105'.
    if (strpos($value, '31699') !== FALSE) {
      // There is the token in HTML, replacing it.
      $value = str_replace('31699', '1105', $value);
    }
    return $value;
  }

}
