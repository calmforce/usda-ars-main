<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for media type.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_aglab_uuid",
 *   source_module = "usda_aglab_migrate"
 * )
 */
class UsdaAglabUuid extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (strpos($value, 'umb://media/') !== FALSE) {
      $uuid = substr($value, 12);
    }
    elseif (strpos($value, 'umb://document/') !== FALSE) {
      $uuid = substr($value,15);
    }
    else {
      return NULL;
    }
    return $this->formatUuid($uuid);
  }

  /**
   * Adds hyphens to hyphen-less UUID.
   *
   * @param string $value
   *   The UUID to be formatted.
   *
   * @return string
   *   The formatted UUID.
   */
  private function formatUuid($value) {
    $chunk_length = [8, 4, 4, 4, 12];
    $str_segments = [];
    $offset = 0;
    foreach ($chunk_length as $len) {
      $str_segments[] = substr($value, $offset, $len);
      $offset += $len;
    }
    $uuid = implode('-', $str_segments);
    return strtoupper($uuid);
  }

}
