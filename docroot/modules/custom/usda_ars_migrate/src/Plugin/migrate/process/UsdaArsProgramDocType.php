<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Umbraco Nat Program Doc type.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_ars_np_doc_type",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsProgramDocType extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!in_array($value, ['NPProgramInputs', 'NPProgramPlanning', 'NPProgramReports'])) {
      return 'NPProgramInputs';
    }
    return $value;
  }

}
