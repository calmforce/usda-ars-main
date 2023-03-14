<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for Image Cards (Explore Other Discoveries field).
 *
 * @MigrateSource(
 *   id = "usda_aglab_image_cards",
 *   source_module = "usda_aglab_migrate"
 * )
 */
class UsdaAglabImageCards extends UsdaArsSource {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('umbracoPropertyData', 'pd');
    $query->innerJoin('cmsPropertyType', 'pt', 'pd.propertytypeid = pt.id');
    $query->innerJoin('umbracoContentVersion', 'cv', 'pd.versionId = cv.id');
    $query->innerJoin('umbracoDocument', 'doc', 'doc.nodeId = cv.nodeId');
    $query->innerJoin('umbracoContent', 'c', 'c.nodeId = cv.nodeId');
    $query->innerJoin('cmsContentType', 'ct', 'c.contentTypeId = ct.nodeId');
    $query->addField('pd','textValue', 'content');
    $query->addField('c','nodeId', 'nodeId');
    $query->addField('pt','Alias', 'content_type');
    $query->addField('cv','versionDate', 'createDate');
    $query->addField('cv','userId', 'nodeUser');
    $query->condition('doc.published', 1);
    $query->condition('cv.current', 1);
    $query->condition('ct.alias', ['articlePage', 'primeDiscovery', 'generalContentPage'], 'IN');
    $query->condition('pd.textValue', '%blImageCards%', 'LIKE');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nodeId' => [
        'type' => 'integer',
        'alias' => 'nid',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Node ID'),
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
      try {
        $properties = $this->getImageCards($row->getSourceProperty('content'), $row->getSourceProperty('nodeId'), $this->configuration['key']);
        if (!empty($properties)) {
          $row->setSourceProperty('imageCards', $properties['imageCards']);
          $row->setSourceProperty('imageCardsSourceIds', $properties['imageCardsSourceIds']);
          $row->setSourceProperty('imageCardsSectionTitle', $properties['imageCardsSectionTitle']);
        }
      } catch (\Exception $e) {
        return FALSE;
      }
    }

    return parent::prepareRow($row);
  }

}
