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
 *   id = "usda_tellus_external_video_caption",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusExternalVideoCaption extends ProcessPluginBase {

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

    foreach ( $main_content_raw as $index => $section) {
      if (!empty($section['externalVideo']) && !empty($section['externalVideoCaption'])) {
        $caption = $section['externalVideoCaption'];
        break;
      }
    }
    return $caption ?? NULL;
  }

}
