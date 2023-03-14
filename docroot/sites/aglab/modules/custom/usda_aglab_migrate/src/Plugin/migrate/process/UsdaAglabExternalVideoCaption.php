<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Tellus External Video embedded in Main Content JSON data.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_aglab_external_video_caption",
 *   source_module = "usda_aglab_migrate"
 * )
 */
class UsdaAglabExternalVideoCaption extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Restrict the length to 1024 chars, to avoid exceptions.
    return !empty($value) ? substr($value, 0, 1024) : NULL;
  }

}
