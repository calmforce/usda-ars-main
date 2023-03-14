<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Card Link boolean flag.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_scientific_discoveries_card_link",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesExternalImageCardLink extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Make whole card link 'true' for Topic cards.
    return $value == 'blIconRow' ? 1 : 0;
  }

}
