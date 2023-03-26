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
 *   id = "usda_ars_fix_url",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsFixUrl extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (!empty($value) && !str_contains($value, 'http')) {
      if (str_starts_with(strtolower($value), 'doi:')) {
        $value = 'doi.org/' . explode(':', $value)[1];
      }
      elseif (str_starts_with(strtolower($value), 'doi ')) {
        $value = 'doi.org/' . explode(' ', $value)[1];
      }
      $value = 'https://' . $value;
    }
    elseif ($pos = strpos($value, 'http')) {
      // If there is some junk before 'http', we need to skip it.
      $value = substr($value, $pos);
    }
    // Remove blanks, if any.
    return str_replace(' ', '', $value);
  }

}
