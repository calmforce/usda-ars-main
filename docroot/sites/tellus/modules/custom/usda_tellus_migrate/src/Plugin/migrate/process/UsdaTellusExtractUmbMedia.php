<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Tellus Media embedded in Main Content JSON data.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_tellus_extract_umb_media",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusExtractUmbMedia extends ProcessPluginBase {

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

    $main_content_raw = json_decode($value, TRUE);
    // If json_decode() has returned NULL, it might be that the data isn't
    // valid utf8 - see http://php.net/manual/en/function.json-decode.php#86997.
    if (is_null($main_content_raw)) {
      $utf8response = utf8_encode($value);
      $main_content_raw = json_decode($utf8response, TRUE);
    };
    if (empty($main_content_raw)) {
      return NULL;
    }
    $media = [];
    foreach ( $main_content_raw as $index => $section) {
      if (!empty($section['featuredImage'])) {
        $media[] = $section['featuredImage'];
      }
      if (!empty($section['videoFile'])) {
        $media[] = $section['videoFile'];
      }
      if (!empty($section['imageColumns'])) {
        // imageColumns require second json_decode().
        $columns = json_decode($section['imageColumns'], TRUE);
        foreach ($columns as $column) {
          if (!empty($column['featuredImage'])) {
            $media[] = $column['featuredImage'];
          }
        }
      }
      // Extract embedded media from 'content' and/or 'sidebarContent'.
      if (strpos($section['content'], 'data-udi=') !== FALSE) {
        $this->extractMediaUuids($section['content'], $media);
      }
      if (strpos($section['sidebarContent'], 'data-udi=') !== FALSE) {
        $this->extractMediaUuids($section['sidebarContent'], $media);
      }
    }

    $this->multiple = empty($media) ? FALSE : TRUE;
    return $media ? : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

  /**
   * Extracts Media UUID from a string.
   *
   * @param string $content
   *   The content string.
   * @param array $media
   *   The media array.
   */
  private function extractMediaUuids(string $content, array &$media) {
    preg_match_all('/<img.*"(umb:\/\/media\/\w+)".*\/>/ui', $content, $matches);
    if (!empty($matches[1])) {
      $media = array_merge($media, $matches[1]);
    }
  }

}
