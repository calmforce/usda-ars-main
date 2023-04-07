<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for node type.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_ars_format_zip",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsFormatZip extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (strlen($value) > 5) {
      $value = trim($value);
      if (strlen($value) == 9) {
        $value = substr($value, 0, 5) . '-' . substr($value, 5);
      }
    }
    return $value;
  }

}
