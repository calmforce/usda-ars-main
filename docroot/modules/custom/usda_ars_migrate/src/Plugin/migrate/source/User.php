<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Defines UsdaArsMigrateRegions source plugin.
 *
 * @MigrateSource(
 *   id = "usda_ars_user",
 *   source_module = "usda_ars_migrate"
 * )
 */
class User extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('umbracoUser', 'u');
    $query->join('umbracoUserType', 'umbracoUserType', 'umbracoUserType.id = u.userType');
    $query->fields('umbracoUserType', array('userTypeName'));
    $query->fields('u', $this->baseFields());
    $query->condition('u.id',['0', '1'],'NOT IN');
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
      'lastLoginDate' => $this->t('Last access timestamp'),
      'lastPasswordChangeDate' => $this->t('Last access timestamp'),
      'userType' => $this->t('User Type Name'),

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
    $user_role = $row->getSourceProperty('userTypeName');
    if($user_role == "Editors") {
      $row->setSourceProperty('userType', array('editor'));
    } elseif ($user_role == "Administrators") {
      $row->setSourceProperty('userType', array('administrator'));
    } elseif ($user_role == "Writers") {
      $row->setSourceProperty('userType', array('writer'));
    }

    $datetime = $row->getSourceProperty('lastLoginDate');
    $timestamp = strtotime($datetime);
    $last_datetime = $row->getSourceProperty('lastPasswordChangeDate');
    $last_timestamp = strtotime($last_datetime);
    $row->setSourceProperty('lastLoginDate', $timestamp);
    $row->setSourceProperty('lastPasswordChangeDate', $last_timestamp);

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
      'lastPasswordChangeDate',
      'userType',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeId() {
    return 'user';
  }

}
