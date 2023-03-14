<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Defines UsdaArsUserLinkToProfile source plugin.
 *
 * @MigrateSource(
 *   id = "usda_ars_user_profile_link",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsUserLinkToProfile extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('umbracoUser', 'u');
    $query->fields('u', ['id', 'userEmail']);
    $query->condition('u.id',['0', '1'],'NOT IN');
    $query->condition('u.userDisabled',0);
    // $query->condition('u.id',['4415', '1005', '2094'],'IN');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('User ID'),
      'userEmail' => $this->t('EMail'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'u',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeId() {
    return 'user';
  }

}
