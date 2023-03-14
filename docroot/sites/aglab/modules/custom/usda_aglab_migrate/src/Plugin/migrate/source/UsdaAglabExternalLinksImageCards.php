<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for External Links Image Cards.
 *
 * @MigrateSource(
 *   id = "usda_aglab_external_links_image_cards",
 *   source_module = "usda_aglab_migrate"
 * )
 */
class UsdaAglabExternalLinksImageCards extends UsdaArsSource {

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
    $query->condition('ct.alias', ['articlePage', 'primeDiscovery', 'generalContentPage', 'topicPage', 'regionalPage'], 'IN');
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
      'cardUuid' => [
        'type' => 'string',
        'alias' => 'cuuid',
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
      'cardUuid' => $this->t('Card UUID'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\migrate\MigrateException
   */
  public function initializeIterator() {
    $it = parent::initializeIterator();
    return $this->getYield($it);
  }

  /**
   * Gets the source rows count using Iterator.
   */
  protected function doCount() {
    return iterator_count($this->initializeIterator());
  }

  /**
   * Prepares one row per 'imageCards' JSON row in the source SQL row.
   *
   * @param \Iterator $it
   *   The source Iterator object.
   *
   * @codingStandardsIgnoreStart
   *
   * @return \Generator
   *   A new row, one for each 'imageCards' row in the source JSON data.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield($it) {
    $db = $this->configuration['key'];
    foreach ($it as $row) {
      $cards = $this->getExternalImageCards($row['content'], $db);
      foreach ($cards['imageCards'] as $card) {
        $new_row = $row;
        $new_row['cardUuid'] = $card['cardUuid'];
        $new_row['cardTitle'] = $card['title'];
        $new_row['cardBody'] = $card['body'];
        $new_row['cardImageUuid'] = $card['image_uuid'];
        $new_row['actionLinkUrl'] = $card['actionLinkUrl'];
        $new_row['actionLinkName'] = $card['actionLinkName'];
        yield($new_row);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    return parent::prepareRow($row);
  }

}
