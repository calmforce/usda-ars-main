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
 *   id = "usda_tellus_uuid",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusUuid extends ProcessPluginBase {

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

    if (empty($value)) {
      return NULL;
    }
    $values_array = explode(',', $value);
    $uuids = [];
    foreach ($values_array as $value) {
      if (strpos($value, 'umb://media/') !== FALSE) {
        $uuid = substr($value, 12);
      }
      elseif (strpos($value, 'umb://document/') !== FALSE) {
        $uuid = substr($value, 15);
      }
      else {
        return NULL;
      }
      $uuids[] = $this->formatUuid($uuid);
    }
    if (count($uuids) > 1) {
      $this->multiple = TRUE;
      return $uuids;
    }
    else {
      $this->multiple = FALSE;
      return $uuids[0];
    }
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

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

}
