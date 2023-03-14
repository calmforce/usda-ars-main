<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\node\NodeInterface;

/**
 * Process plugin: node ID for email.
 *
 * @MigrateProcessPlugin(
 *   id = "usda_ars_user_profile_ref",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsUserProfileRef extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   * @throws MigrateSkipProcessException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $nid = $this->getPersonIdByEmail($value);
    if (!$nid) {
      throw new MigrateSkipProcessException();
    }
    return $nid;
  }

  /**
   * Gets Person Profile node ID by Email Address.
   *
   * @param string $email
   *  Email Address.
   *
   * @return int
   *  The Person Profile node ID.
   */
  protected function getPersonIdByEmail(string $email) {
    $query = \Drupal::entityQuery('node');
    $result = $query
      ->condition('type', 'person_profile')
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('field_email', $email)
      ->execute();
    return reset($result);
  }

}
