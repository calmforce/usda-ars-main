<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for File Destination URI.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_scientific_discoveries_dest_uri",
 *   source_module = "file"
 * )
 */
class UsdaScientificDiscoveriesDestinationUri extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $scheme = $this->configuration['scheme'];
    return $scheme . ':/' .  $value;
  }

}
