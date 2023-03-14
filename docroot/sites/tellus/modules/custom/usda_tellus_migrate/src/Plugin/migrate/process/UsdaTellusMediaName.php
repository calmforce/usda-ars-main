<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for media type.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_tellus_media_name",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusMediaName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property)
  {
    // Making it the same as filename w/o extension.
    $name = explode('.', $value);
    if ($name[0] <= 255) {
      return $name[0];
    } else {
      return substr($name[0], 0, 255);
    }
  }

}
