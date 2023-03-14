<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Sci-Disc Paragraph References.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_aglab_paragraph_refs",
 *   source_module = "usda_aglab_migrate"
 * )
 */
class UsdaAglabParagraphRefs extends ProcessPluginBase {

  /**
   * Flag indicating whether there are multiple values.
   *
   * @var bool
   */

  /**
   * {@inheritdoc}
   * @throws MigrateSkipProcessException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value) || empty($value[0])) {
      throw new MigrateSkipProcessException("No valid Paragraph References provided, skipping.");
    }
    $new_value = [];
    $new_value['target_id'] = $value[0];
    $new_value['target_revision_id'] = $value[1];
    return $new_value;
  }

}
