<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Sci-Disc Media embedded in Main Content JSON data.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_scientific_discoveries_extract_umb_media",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesExtractUmbMedia extends ProcessPluginBase {

  /**
   * Flag indicating whether there are multiple values.
   *
   * @var bool
   */
  protected $multiple = FALSE;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return empty($value['contentImage']) ? NULL: $value['contentImage'];
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

}
