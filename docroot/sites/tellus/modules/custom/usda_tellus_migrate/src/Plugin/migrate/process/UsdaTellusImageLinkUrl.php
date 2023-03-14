<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Tellus External Video embedded in Main Content JSON data.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_tellus_image_link_url",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusImageLinkUrl extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   * @throws MigrateSkipProcessException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value[0])) {
      return $value[0];
    }
    elseif (is_numeric($value[1])) {
      return 'internal:/node/' . $value[1];
    }
    throw new MigrateSkipProcessException("No valid image link URL provided, skipping.");
  }

}
