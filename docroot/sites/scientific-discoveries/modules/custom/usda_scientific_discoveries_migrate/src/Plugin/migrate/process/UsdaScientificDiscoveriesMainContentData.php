<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Scientific Discoveries Main Content in JSON format.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_scientific_discoveries_main_content_data",
 *   handle_multiples = TRUE,
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesMainContentData extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($main_content_data, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $sections = [];
    $media_idx = 0;
    $media_ids = $row->getDestinationProperty('field_main_content_images');

    foreach ( $main_content_data as $section) {
      if (!empty($section['richContent']) || !empty($section['contentImage']) || !empty($section['videoFile'])) {
        $html = '<section>';
        if (!empty($section["title"]) && !$section["hideTitle"]) {
          $html .= '<h2 class="block-element-title">' . $section["title"] . '</h2>';
        }
        if (!empty($section['contentImage']) || !empty($section['videoFile'])) {
          $media_id = $media_ids[$media_idx];
          $media_tag = $this->getDrupalMediaTag($media_id, strtolower($section['imageOnLeft']), $section['imageCaption']);
          if (!empty($media_tag)) {
            $html .= $media_tag;
          }
          $media_idx++;
        }
        if (!empty($section['richContent'])) {
          $html .= $section['richContent'];
        }
        $html .= '</section>';
        $sections[] = $html;
      }
    }
    $body = empty($sections) ? '' : implode($sections);
    return $body;
  }

  /**
   * Returns formated drupal-media tag for media ID.
   *
   * @param int $media_id
   *   Media ID.
   * @param $image_left
   *   Image alignment.
   * @param $image_caption
   *   Image caption.
   *
   * @return string
   *   Drupal media tag.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getDrupalMediaTag($media_id, $image_left, $image_caption) {
    $entity = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
    if ($entity) {
      $uuid = $entity->uuid();
      $image_align = $image_left ? 'left': 'right';
      $media_tag = '<drupal-media data-align="' . $image_align . '" data-entity-type="media" data-entity-uuid="' . $uuid;
      if (!empty($image_caption)) {
        $media_tag .= '" data-caption="' . $image_caption . '" data-view-mode="embed_no_caption"></drupal-media>';
      }
      else {
        $media_tag .= '" data-view-mode=""></drupal-media>';
      }
      return $media_tag;
    }
    return '';
  }
}
