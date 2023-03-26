<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for node type.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_ars_fix_pub_title",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsFixPubTitle extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (empty($value)) {
      $SEQ_NO_115 = $row->getSourceProperty('SEQ_NO_115');
      $value = 'Publication seqNo115 = ' . $SEQ_NO_115;
    }

    return $value;
  }

}
