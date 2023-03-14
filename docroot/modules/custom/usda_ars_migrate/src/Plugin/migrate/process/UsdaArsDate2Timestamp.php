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
 *   id = "usda_ars_date2timestamp",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsDate2Timestamp extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    return $value ? strtotime($value) : NULL;
  }

}
