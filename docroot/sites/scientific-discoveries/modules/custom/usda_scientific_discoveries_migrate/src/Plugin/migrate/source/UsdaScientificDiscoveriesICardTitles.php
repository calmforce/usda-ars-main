<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for Image Card Titles.
 *
 * @MigrateSource(
 *   id = "usda_scientific_discoveries_i_card_titles",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesICardTitles extends UsdaArsSource {

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
      'cardId' => [
        'type' => 'integer',
        'alias' => 'cid',
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
      'cardId' => $this->t('Image Card ID'),
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
      $cards = $this->getImageCards($row['content'], $db);
      foreach ($cards['imageCards'] as $card) {
        $new_row = $row;
        $new_row['cardId'] = $card['nid'];
        $new_row['cardTitle'] = $card['title'];
        $new_row['cardImageUuid'] = $card['image_uuid'];
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

  /**
   * Gets imageCards Properties.
   *
   * @param string $json_content
   *   The Json string.
   * @param string $db
   *   The source DB name.
   *
   * @return array
   *   The decoded imageCards Properties.
   */
   function getImageCards(string $json_content, string $db) {
    $content_raw = json_decode($json_content, TRUE);
    // If json_decode() has returned NULL, it might be that the data isn't
    // valid utf8 - see http://php.net/manual/en/function.json-decode.php#86997.
    if (is_null($content_raw)) {
      $utf8response = utf8_encode($json_content);
      $content_raw = json_decode($utf8response, TRUE);
    };
    if (empty($content_raw)) {
      return NULL;
    }
    $properties = [];
    // Extract properties.
    if (!empty($areas = $content_raw["sections"][0]["rows"][0]["areas"][0]["controls"])) {
      foreach ($areas as $area) {
        if ($area['value']['dtgeContentTypeAlias'] == 'blImageCards') {
          $image_cards = $area['value']['value']['imageCards'];
          foreach ($image_cards as $card) {
            $node_uuid = substr($card['actionLink'][0]['udi'],15);
            $image_uuid = substr($card['image'],12);
            if (!empty($node_uuid)) {
              $node_uuid = $this->formatUuid($node_uuid);
              $card_nid = $this->getNodeIdByUuid($node_uuid, $db);
              if ($card_nid) {
                $image_uuid = $this->formatUuid($image_uuid);
                //$image_mid = $this->getNodeIdByUuid($image_uuid);
                $properties['imageCards'][] = [
                  'title' => $card['title'],
                  'text' => $card['text'],
                  'node_uuid' => $node_uuid,
                  'nid' => $card_nid,
                  'image_uuid' => $image_uuid,
                ];
                $properties['imageCardsNodeIds'][] = $card_nid;
              }
            }
          }
          break;
        }
      }
    }
    return $properties;
  }

}
