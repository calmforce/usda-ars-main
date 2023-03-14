<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for Umbraco Node Body HTML Tokens.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_ars_html_body_tokens",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsBodyTokens extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Replacing {{HTML-CODE}} token with its value.
    if (strpos($value, '{{HTML-CODE}}') !== FALSE) {
      // There is the token in HTML, replacing it.
      $html_code = $row->getSourceProperty('htmlCode');
      $value = str_replace('{{HTML-CODE}}', $html_code, $value);
    }
    // Replacing {{USAJOBS_URLS}} token with its value.
    if (strpos($value, '{{USAJOBS_URLS}}') !== FALSE) {
      // There is the token in HTML, replacing it.
      $usa_job_url = $row->getSourceProperty('usajobUrl');
      if ($usa_job_url) {
        $usa_job_url_link = '<a href = "' . $usa_job_url . '" target = "_blank">' . $usa_job_url . '</a>';
      }
      $value = str_replace('{{USAJOBS_URLS}}', $usa_job_url_link, $value);
    }
    return $value;
  }

}
