<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for External Video media.
 *
 * @MigrateSource(
 *   id = "usda_tellus_external_video",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusExternalVideo extends UsdaArsSource {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('cmsPropertyData', 'pd');
    $query->innerJoin('cmsPropertyType', 'pt', 'pd.propertytypeid = pt.id');
    $query->innerJoin('cmsDocument', 'doc', 'doc.nodeId = pd.contentNodeId');
    $query->innerJoin('umbracoNode', 'n', 'n.id = pd.contentNodeId');
    $query->addField('pd','contentNodeId', 'nodeId');
    $query->addField('n', 'text', 'mediaName');
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path', 'nodeObjectType']);
    $query->condition('doc.published', 1);
    $query->condition('pt.id', 107);
    $query->condition('pd.dataNtext', '%externalVideo%', 'LIKE');
    $query->groupBy('pd.contentNodeId');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nodeId' => [
        'type' => 'integer',
        'alias' => 'mid',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Media ID'),
      'mediaName' => $this->t('Media Name'),
      'createDate' => $this->t('Date Created'),
      'updateDate' => $this->t('Date Updated'),
      'nodeUser' => $this->t('Media User'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
      if (!$this->getTellusMainContent($row)) {
        return FALSE;
      }
    }
    return parent::prepareRow($row);
  }

}
