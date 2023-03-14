<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Block Content Type.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_ars_block_content_type",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesBlockContentType extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $block_types_map = [
      'blInfographic' => 'infographic',
      'blLargeVideo' => 'large_video',
      'blTwoColumnMediaSection' => 'two_column_media_section',
      'blVideoList' => 'video_list',
      'blContentWithImage' => 'content_with_image',
      'blImageGalleryCarousel' => 'image_gallery',
      'blImageCards' => 'image_cards',
      ];
    if (!in_array($value, array_keys($block_types_map))) {
      throw new MigrateSkipRowException("No valid component type provided, skipping the row.");
    }
    else {
      return $block_types_map[$value];
    }
  }

}
