<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for Region Tiles fields.
 *
 * @MigrateSource(
 *   id = "usda_scientific_discoveries_region_tiles",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesRegionTiles extends UsdaArsSource {

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
    // Looking for 'content' property.
    $query->condition('pd.propertytypeid', 116);
    $query->condition('c.nodeId', 1187);
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
      'regionId' => [
        'type' => 'integer',
        'alias' => 'rid',
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
      'regionId' => $this->t('Region ID'),
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
   * Prepares one row per 'Region Tiles' JSON row in the source SQL row.
   *
   * @param \Iterator $it
   *   The source Iterator object.
   *
   * @codingStandardsIgnoreStart
   *
   * @return \Generator
   *   A new row, one for each 'Region Tiles' row in the source JSON data.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield($it) {
    $db = $this->configuration['key'];
    foreach ($it as $row) {
      $tiles = $this->getRegionTiles($row['content'], $db);
      foreach ($tiles['regionTiles'] as $tile) {
        $new_row = $row;
        $new_row['regionId'] = $tile['nid'];
        $new_row['regionSummary'] = $tile['text'];
        $new_row['regionImageUuid'] = $tile['image_uuid'];
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
   * Gets regionTiles Properties.
   *
   * @param string $json_content
   *   The Json string.
   * @param string $db
   *   The source DB name.
   *
   * @return array
   *   The decoded regionTiles Properties.
   */
  private function getRegionTiles(string $json_content, string $db) {
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
        if ($area['value']['dtgeContentTypeAlias'] == 'blRegionTiles') {
          $region_tiles = $area['value']['value']['panels'];
          foreach ($region_tiles as $tile) {
            $region_uuid = substr($tile['actionLink'][0]['udi'],15);
            $image_uuid = substr($tile['image'],12);
            if (!empty($region_uuid)) {
              $region_uuid = $this->formatUuid($region_uuid);
              $region_nid = $this->getNodeIdByUuid($region_uuid, $db);
              if ($region_nid) {
                $image_uuid = $this->formatUuid($image_uuid);
                $properties['regionTiles'][] = [
                  'title' => $tile['title'],
                  'text' => $tile['text'],
                  'nid' => $region_nid,
                  'image_uuid' => $image_uuid,
                ];
                $properties['regionTilesNodeIds'][] = $region_nid;
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
