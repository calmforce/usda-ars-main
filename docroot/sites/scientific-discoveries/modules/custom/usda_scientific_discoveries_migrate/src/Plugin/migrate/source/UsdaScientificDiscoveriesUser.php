<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Defines UsdaScientificDiscoveriesUser source plugin.
 *
 * @MigrateSource(
 *   id = "usda_scientific_discoveries_user",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesUser extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('umbracoUser', 'u');
    $query->fields('u', $this->baseFields());
    $query->condition('u.id',[0, 1, -1],'NOT IN');
    $query->condition('u.userDisabled',0);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('User ID'),
      'userName' => $this->t('Username'),
      'userPassword' => $this->t('Password'),
      'userEmail' => $this->t('Mail'),
      'lastLoginDate' => $this->t('Last Login Date'),
      'createDate' => $this->t('Create Date'),
      'updateDate' => $this->t('Update Date'),
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
  public function prepareRow(Row $row) {

    $last_login = strtotime($row->getSourceProperty('lastLoginDate'));
    $create = strtotime($row->getSourceProperty('createDate'));
    $update = strtotime($row->getSourceProperty('updateDate'));
    if ($last_login) {
      $row->setSourceProperty('lastLoginDate', $last_login);
    }
    $row->setSourceProperty('createDate', $create);
    $row->setSourceProperty('updateDate', $update);

    return parent::prepareRow($row);
  }

  /**
   * Returns the user base fields to be migrated.
   *
   * @return array
   *   Associative array having field name as key and description as value.
   */
  protected function baseFields() {
    return [
      'id',
      'userName',
      'userPassword',
      'userEmail',
      'lastLoginDate',
      'createDate',
      'updateDate',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeId() {
    return 'user';
  }

}
