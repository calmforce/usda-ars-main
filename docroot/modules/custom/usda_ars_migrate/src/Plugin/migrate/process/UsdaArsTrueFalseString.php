<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for true/false string.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_ars_true_false_string",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsTrueFalseString extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    return $value != 'false';
  }

}
