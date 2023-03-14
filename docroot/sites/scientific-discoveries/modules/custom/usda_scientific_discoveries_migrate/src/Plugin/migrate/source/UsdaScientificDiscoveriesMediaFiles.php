<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Defines UsdaScientificDiscoveriesMediaFiles source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_scientific_discoveries_media_files". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_scientific_discoveries_media_files",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesMediaFiles extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('umbracoMediaVersion', 'mv');
    $query->innerJoin('umbracoContentVersion', 'cv', 'cv.id = mv.id');
    $query->innerJoin('umbracoNode', 'n', 'n.id = cv.nodeId');
    $query->addField('mv', 'path', 'mediaPath');
    $query->addField('mv', 'id', 'fid');
    $query->addField('n', 'id', 'mid');
    $query->addField('n', 'text', 'mediaName');
    $query->addField('n','uniqueID', 'uuid');
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path', 'nodeObjectType']);
    $query->isNotNull('mv.path');
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
      'fid' => $this->t('File ID'),
      'sortOrder' => $this->t('Sort Order'),
      'createDate' => $this->t('Date Created'),
      'nodeUser' => $this->t('User'),
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
      $row->setSourceProperty('createDate', strtotime($row->getSourceProperty('createDate')));
    }
    $result = parent::prepareRow($row);
    return $result;
  }

}
