<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Defines UsdaTellusMediaFiles source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_tellus_media_files". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_tellus_media_files",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusMediaFiles extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('cmsMedia', 'm');
    $query->innerJoin('umbracoNode', 'n', 'n.id = m.nodeId');
    $query->fields('m', ['nodeId', 'mediaPath']);
    $query->addField('n', 'text', 'mediaName');
    $query->addField('n','uniqueID', 'uuid');
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path', 'nodeObjectType']);
    $query->isNotNull('m.mediaPath');
    $query->orderBy('n.sortOrder', 'ASC');
    return $query;
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
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Node ID'),
      'sortOrder' => $this->t('Sort Order'),
      'createDate' => $this->t('Date Created'),
      'nodeUser' => $this->t('Node User'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
      $filepath = urldecode($row->getSourceProperty('mediaPath'));
      $row->setSourceProperty('filepath', $filepath);
      $row->setSourceProperty('fid', $row->getSourceProperty('nodeId'));
      $row->setSourceProperty('createDate', strtotime($row->getSourceProperty('createDate')));
    }
    $result = parent::prepareRow($row);
    return $result;
  }

}
