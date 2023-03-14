<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Aglab Main Content in JSON format.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_aglab_main_content_data",
 *   handle_multiples = TRUE,
 *   source_module = "usda_aglab_migrate"
 * )
 */
class UsdaAglabMainContentData extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($main_content_data, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $sections = [];
    $media_idx = 0;
    $media_ids = $row->getDestinationProperty('field_main_content_images');
    $video_done = FALSE;

    foreach ( $main_content_data as $section) {
      $section_type = $section['section_type'];
      if (in_array($section_type, ['blContentWithImage', 'blImageGalleryCarousel', 'blVideoSlider', 'blLargeVideo'])) {

        if ($section_type == 'blLargeVideo') {
          if ($video_done) {
            continue;
          }
          $video_done = TRUE;
        }

        $html = $this->getSectionTag($section_type, $section['backgroundColor'], $section['leafMotif']) . '<div class="grid-container">';

        if (!empty($section["title"]) && !$section["hideTitle"] && $section_type != 'blLargeVideo') {
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
        if (!empty($section['slides'])) {
          $node_id = $row->getDestinationProperty('nid');
          if ($section_type == 'blVideoSlider') {
            $html .= "<p class='slideshow'>[view:slideshow_content=slideshow_content_video=$node_id]</p>";
          }
          else {
            $html .= "<p class='slideshow'>[view:slideshow=embedded_slick_block=$node_id]</p>";
          }
        }
        $html .= '</div>' . '</section>';
        $sections[] = $html;
      }
    }
    $body = empty($sections) ? '' : implode($sections);
    return $body;
  }

  /**
   * Provides <section> tag based on bkg color and leafMotif setting.
   *
   * @param string $section_type
   * @param string $background_color
   * @param int $leaf_motif
   *
   * @return string
   */
  protected function getSectionTag($section_type, $background_color, $leaf_motif) {
    if (empty($background_color) && empty($leaf_motif)) {
      return '<section class="' . $section_type . '">';
    }
    elseif (!empty($background_color) && empty($leaf_motif)) {
      return '<section class="bg-' . $background_color . ' ' . $section_type .'">';
    }
    elseif (empty($background_color) && !empty($leaf_motif)) {
      return '<section class="leaf-motif ' . $section_type . '">';
    }
    else {
      // Both not empty.
      return '<section class="bg-' . $background_color . ' leaf-motif ' . $section_type . '">';
    }
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
