<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Tellus Main Content in JSON format.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_tellus_main_content_data",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusMainContentData extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $main_content_raw = json_decode($value, TRUE);
    // If json_decode() has returned NULL, it might be that the data isn't
    // valid utf8 - see http://php.net/manual/en/function.json-decode.php#86997.
    if (is_null($main_content_raw)) {
      $utf8response = utf8_encode($value);
      $main_content_raw = json_decode($utf8response, TRUE);
    };
    if (empty($main_content_raw)) {
      // The node Body cannot be NULL.
      return '';
    }
    $sections = [];
    $media_idx = 0;
    $media_ids = $row->getDestinationProperty('field_main_content_images');
    $slides_sections = [];
    foreach ( $main_content_raw as $index => $section) {
      if (!empty($section['content']) || !empty($section['featuredImage']) || !empty($section['videoFile']) || !empty($section['slides']) || !empty($section['imageColumns'])) {
        $html = '<section>';
        if (!empty($section['featuredImage']) || !empty($section['videoFile'])) {
          $media_id = $media_ids[$media_idx];
          if ($section['icContentTypeGuid'] == '449736cb-5f82-4556-a253-758b21bcc967') {
            // Infographics section. Has an image, but may have nothing else;
            $image_position = 'center';
          }
          else {
            $image_position = strtolower($section['imageLocation']);
          }
          $media_tag = $this->getDrupalMediaTag($media_id, $image_position);
          if (!empty($media_tag)) {
            $html .= $media_tag;
          }
          $media_idx++;
        }
        if (!empty($section['content'])) {
          $this->replaceInlineStyles($section['content']);
          // Add Subtitle as H2.
          $subtitle = trim(strip_tags($section['title']));
          if (!empty($subtitle)) {
            $html .= "<h2 class='subtitle'>$subtitle</h2>";
          }
          // Extract embedded media from 'content' and/or 'sidebarContent'.
          if (strpos($section['content'], 'data-udi=') !== FALSE) {
            $this->replaceMediaUuids($section['content'], $media_ids, $media_idx, '700px_wide');
          }
          if (!empty($section['sidebarContent'])) {
            $this->replaceInlineStyles($section['sidebarContent']);

            if (strpos($section['sidebarContent'], 'data-udi=') !== FALSE) {
              $this->replaceMediaUuids($section['sidebarContent'], $media_ids, $media_idx, '360px_wide');
            }
            $html .= '<div class="sidebar-grid grid-container"><div class="grid-row grid-gap">';
            if (!empty($section['sidebarLocation']) && $section['sidebarLocation'] == 'Right') {
              $html .= '<div class="tablet:grid-col-8">' . $section['content'] . '</div><div class="tablet:grid-col-4 sidebar">' . $section['sidebarContent'] . '</div></div></div>';
            }
            else {
              $html .= '<div class="tablet:grid-col-4 sidebar">' . $section['sidebarContent'] . '</div><div class="tablet:grid-col-8">' . $section['content'] . '</div></div></div>';
            }
          }
          else {
            $html .= $section['content'];
          }
        }
        if (!empty($section['slides'])) {
          $node_id = $row->getDestinationProperty('nid');
          $html .= "<p class='slideshow'>[view:slideshow=embedded_slick_block=$node_id]</p>";
          $slides_sections[] = $index;
        }
        if (!empty($section['imageColumns'])) {
          // imageColumns require second json_decode().
          $columns = json_decode($section['imageColumns'], TRUE);
          $column_num = count($columns);
          if ($column_num) {
            $col_class = $column_num == 2 ? 'tablet:grid-col-6' : 'tablet:grid-col-4';
            $view_mode = $column_num == 2 ? '555px_wide' : '360px_wide';
            // Open Image Row.
            $html .= '<div class="image-columns-grid grid-container"><div class="grid-row grid-gap">';
            foreach ($columns as $column) {
              $media_id = $media_ids[$media_idx];
              $media_tag = $this->getDrupalMediaTag($media_id, 'center', $view_mode);
              if (!empty($media_tag)) {
                $html .= "<div class='$col_class'>" . $media_tag . '</div>';
              }
              $media_idx++;
            }
            // Close Image Row.
            $html .= '</div></div>';
          }
        }
        $html .= '</section>';
        $sections[] = $html;
      }
    }
    if (!empty($slides_sections) && count($slides_sections) > 1) {
      // We allow only one Slideshow on a node.
      array_pop($slides_sections);
      foreach ($slides_sections as $section_index) {
        unset($sections[$section_index]);
      }
    }
    $body = empty($sections) ? '' : implode($sections);
    if (strpos ($body, 'href="/media/') !== FALSE) {
      // Replace inline references to media files.
      $body = str_replace('href="/media/', 'href="/sites/tellus/files/media/', $body);
    }
    return $body;
  }

  /**
   * Returns formatted drupal-media tag for media ID.
   *
   * @param int $media_id
   *   Media ID.
   * @param $image_align
   *   Image alignment.
   *
   * @return string
   *   Drupal media tag.
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  protected function getDrupalMediaTag($media_id, $image_align, $view_mode = '') {
    $entity = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
    if ($entity) {
      $uuid = $entity->uuid();
      $image_align = $image_align ? : 'left';
      if (empty($view_mode)) {
        $view_mode = $image_align == 'center' ? '700px_wide' : '440px_wide';
      }
      $media_tag = '<drupal-media data-align="' . $image_align . '" data-entity-type="media" data-entity-uuid="' . $uuid . '" data-view-mode="' . $view_mode . '"></drupal-media>';
      return $media_tag;
    }
    return '';
  }

  /**
   * Replaces Media <img> tag with UUID by Drupal media tag.
   *
   * @param string $content
   *   The content string.
   * @param array $media_ids
   *   The media IDs array.
   * @param int $media_idx
   *   The current media ID
   * @param $view_mode
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function replaceMediaUuids(string &$content, array &$media_ids, &$media_idx, $view_mode) {
    $content = preg_replace_callback(
      '/<img.*"(umb:\/\/media\/\w+)".*\/>/ui',
      function ($matches) use(&$media_ids, &$media_idx, $view_mode) {
        $media_id = $media_ids[$media_idx];
        $media_tag = $this->getDrupalMediaTag($media_id, 'center', $view_mode);
        $media_idx++;
        return $media_tag;
      },
      $content
    );
  }

  /**
   * Replaces some inline styles by USWDS classes.
   *
   * @param string $content
   */
  private function replaceInlineStyles(string &$content) {
    $content = preg_replace('/style="color:\s*([a-z]+);"/ui', 'class="text-$1"', $content);
  }

}
