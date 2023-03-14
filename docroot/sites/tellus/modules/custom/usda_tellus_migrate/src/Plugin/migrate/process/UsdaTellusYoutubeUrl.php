<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Tellus External Video embedded in Main Content JSON data.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_tellus_youtube_url",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusYoutubeUrl extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (strpos($value, 'www.youtube.com/embed') !== FALSE) {
      $value = str_replace('www.youtube.com/embed', 'youtu.be', $value);
    }

    return $value;
  }

}
