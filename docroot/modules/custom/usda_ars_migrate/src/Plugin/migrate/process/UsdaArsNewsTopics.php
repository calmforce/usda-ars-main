<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for news topics list in JSON format.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_ars_news_topics",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsNewsTopics extends ProcessPluginBase {

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

    $topics_array_raw = json_decode($value, TRUE);
    // If json_decode() has returned NULL, it might be that the data isn't
    // valid utf8 - see http://php.net/manual/en/function.json-decode.php#86997.
    if (is_null($topics_array_raw)) {
      $utf8response = utf8_encode($value);
      $topics_array_raw = json_decode($utf8response, TRUE);
    };
    if (empty($topics_array_raw['fieldsets'])) {
      return [];
    }
    $topics = [];
    foreach ( $topics_array_raw['fieldsets'] as $topic) {
      $topics[] = $topic['properties'][0]['value'];
    }
    $this->multiple = TRUE;
    return $topics;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

}
