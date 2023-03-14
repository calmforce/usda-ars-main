<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for Video List Items.
 *
 * @MigrateSource(
 *   id = "usda_aglab_video_list_items",
 *   source_module = "usda_aglab_migrate"
 * )
 */
class UsdaAglabVideoListItems extends UsdaArsSource {

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
    $query->condition('pd.textValue', '%blVideoList%', 'LIKE');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Node ID'),
      'createDate' => $this->t('Date Created'),
      'nodeUser' => $this->t('User'),
      'videoUuid' => $this->t('Video UUID'),
    ];
    return $fields;
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
      'videoUuid' => [
        'type' => 'string',
        'alias' => 'vuuid',
      ],
    ];
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
   * {@inheritdoc}
   * Gets the source rows count using Iterator.
   */
  protected function doCount() {
    return iterator_count($this->initializeIterator());
  }

  /**
   * Prepares one row per ncVideoMediaItem JSON row in the source SQL row.
   *
   * @param \Iterator $it
   *   The source Iterator object.
   *
   * @codingStandardsIgnoreStart
   *
   * @return \Generator
   *   A new row, one for each 'image with link' row in the source JSON data.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield($it) {
    foreach ($it as $row) {
      if (empty($row['content'])) {
        continue;
      }
      $videoItems = $this->getVideoItems($row['content']);
      foreach ($videoItems as $videoItem) {
        $new_row = $row;
        $new_row['videoUuid'] = $videoItem['mediaUUID'];
        $new_row['mediaName'] = $videoItem['mediaName'];
        $new_row['videoUrl'] = $videoItem['videoUrl'];
        $new_row['videoAltText'] = $videoItem['videoAltText'];
        $new_row['textBelowVideo'] = $videoItem['textBelowVideo'];
        $new_row['imageUmbMediaId'] = $videoItem['imageUmbMediaId'];
        yield ($new_row);
      }
    }
  }

  /**
   * Gets Remote Video Properties.
   *
   * @param string $json_content
   *   The Json string.
   *
   * @return array|null
   *   The decoded Video Properties or NULL.
   */
  private function getVideoItems(string $json_content) {
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
        if ($area["value"]["value"]["hidden"] == '1') {
          continue;
        }
        if ($area['value']['dtgeContentTypeAlias'] == 'blVideoList') {
          $listTitle = $area['value']['value']['title'];
          $videoItems = $area['value']['value']['videos'];
          foreach ($videoItems as $index => $videoItem) {
            $itemTitle = empty($videoItem['videoAltText']) ? $listTitle . ' ' . $videoItem['name'] : $videoItem['videoAltText'];
            $properties[$index]['mediaUUID'] = $videoItem['key'];
            $properties[$index]['mediaName'] = $itemTitle;
            $properties[$index]['videoUrl'] = $videoItem['youTubeVideo'];
            $properties[$index]['videoAltText'] = $videoItem['videoAltText'];
            $properties[$index]['textBelowVideo'] = $videoItem['text'];
            $properties[$index]['imageUmbMediaId'] = $videoItem['image'];
          }
        }
      }
    }
    return $properties;
  }
}
