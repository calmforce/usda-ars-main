<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Sci-Disc Video List IDs.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_scientific_discoveries_video_list_ids",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesVideoListIds extends ProcessPluginBase {

  /**
   * Flag indicating whether there are multiple values.
   *
   * @var bool
   */
  protected $multiple = TRUE;

  /**
   * {@inheritdoc}
   * @throws MigrateSkipProcessException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value) || empty($value[1])) {
      throw new MigrateSkipProcessException("No valid video list source IDs provided, skipping.");
    }
    $node_id = $value[0];
    $video_list = $value[1];
    $new_value = [];
    foreach ($video_list as $index => $item) {
      $new_value[$index][0] = $node_id;
      $new_value[$index][1] = $item;
    }
    return $new_value;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

}
