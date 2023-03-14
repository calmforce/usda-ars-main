<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for YouTube URL.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_scientific_discoveries_youtube_url",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesYoutubeUrl extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (strpos($value, 'https') === FALSE) {
      $value = 'https://youtu.be/' . $value;
    }
    elseif (strpos($value, 'www.youtube.com/embed') !== FALSE) {
      $value = str_replace('www.youtube.com/embed', 'youtu.be', $value);
    }

    return $value;
  }

}
