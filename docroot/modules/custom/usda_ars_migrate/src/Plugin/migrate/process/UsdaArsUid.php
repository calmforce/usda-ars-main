<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for node type.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_ars_user",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsUid extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    return $value > 0 ? $value : 1;
  }

}
