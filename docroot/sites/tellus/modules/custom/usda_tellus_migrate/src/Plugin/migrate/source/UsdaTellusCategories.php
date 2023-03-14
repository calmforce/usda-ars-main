<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Defines UsdaTellusCategories migrate source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_tellus_categories". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_tellus_categories",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusCategories extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('cmsDocument', 'd');
    $query->innerJoin('umbracoNode', 'n', 'n.id = d.nodeId');
    $query->innerJoin('cmsContent', 'c', 'c.nodeId = d.nodeId');
    $query->innerJoin('cmsContentType', 'ct', 'c.contentType = ct.nodeId');

    $query->fields('d', ['nodeId', 'documentUser', 'updateDate']);
    $query->addField('n', 'text', 'nodeName');
    $query->addField('n','uniqueID', 'uuid');
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path']);
    $query->condition('ct.alias', 'category');
    $query->condition('d.published', 1);
    $query->condition('d.newest', 1);
    $query->orderBy('n.sortOrder', 'ASC');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Node ID'),
      'sortOrder' => $this->t('Sort Order'),
      'createDate' => $this->t('Date Created'),
      'updateDate' => $this->t('Date Updated'),
      'nodeUser' => $this->t('Node User'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'uuid' => [
        'type' => 'string',
        'alias' => 'uuid',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // The source properties can be added or modified in prepareRow().
    if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
      $datetime = strtotime($row->getSourceProperty('updateDate'));
      $row->setSourceProperty('datetime', $datetime);
    }

    return parent::prepareRow($row);
  }
}
