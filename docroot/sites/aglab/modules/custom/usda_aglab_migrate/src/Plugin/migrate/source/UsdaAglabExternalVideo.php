<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\Query\Sql\Condition;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for External Video media.
 *
 * @MigrateSource(
 *   id = "usda_aglab_external_video",
 *   source_module = "usda_aglab_migrate"
 * )
 */
class UsdaAglabExternalVideo extends UsdaArsSource {

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
    $or = $query->orConditionGroup();
    $or->condition('pd.textValue', '%blVideoSlider%', 'LIKE');
    $or->condition('pd.textValue', '%blLargeVideo%', 'LIKE');
    $query->condition($or);

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
      'mediaUUID' => [
        'type' => 'string',
        'alias' => 'muuid',
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
      'mediaUUID' => $this->t('Media UUID'),
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
   * Prepares one row per 'blLargeVideo' JSON row in the source SQL row.
   *
   * @param \Iterator $it
   *   The source Iterator object.
   *
   * @codingStandardsIgnoreStart
   *
   * @return \Generator
   *   A new row, one for each 'blLargeVideo' row in the source JSON data.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield($it) {
    foreach ($it as $row) {
      $videos = $this->getVideoProperties($row['content']);
      foreach ($videos as $video) {
        $new_row = $row;
        $new_row['mediaUUID'] = $video['mediaUUID'];
        $new_row['mediaName'] = $video['mediaName'];
        $new_row['subTitle'] = $video['subTitle'];
        $new_row['videoUrl'] = $video['videoUrl'];
        $new_row['videoAltText'] = $video['videoAltText'];
        $new_row['textBelowVideo'] = $video['textBelowVideo'];
        $new_row['imageUmbMediaId'] = $video['imageUmbMediaId'];
        $new_row['hideTitle'] = $video['hideTitle'];
        $new_row['backgroundColor'] = $video['backgroundColor'];
        $new_row['leafMotif'] = $video['leafMotif'];
        yield($new_row);
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
  private function getVideoProperties(string $json_content) {
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
        if ($area['value']['dtgeContentTypeAlias'] == 'blLargeVideo' && $area["value"]["value"]["hidden"] != '1') {
          $properties[] = [
            'mediaUUID' => $area["value"]["id"],
            'mediaName' => $area['value']['value']['title'],
            'subTitle' => $area['value']['value']['subTitle'],
            'videoUrl' => $area["value"]["value"]["youTubeVideo"],
            'videoAltText' => $area["value"]["value"]["videoAltText"],
            'textBelowVideo' => $area["value"]["value"]["textBelowVideo"],
            'imageUmbMediaId' => $area["value"]["value"]["image"],
            'hideTitle' => $area["value"]["value"]["hideTitle"],
            'backgroundColor' => $area["value"]["value"]["backgroundColor"]['value'],
            'leafMotif' => $area["value"]["value"]["leafMotif"],
          ];
        }
        elseif ($area['value']['dtgeContentTypeAlias'] == 'blVideoSlider' && $area["value"]["value"]["hidden"] != '1') {
          $video_slides = $area['value']['value']['videoSlides'];
          $slider_title = $area['value']['value']['title'];
          foreach ($video_slides as $video_slide) {
            $properties[] = [
              'mediaUUID' => $video_slide["key"],
              'mediaName' => $slider_title . ' ' . $video_slide['name'],
              'videoUrl' => $video_slide["youTubeVideo"],
              'videoAltText' => $video_slide["videoAltText"],
              'textBelowVideo' => $video_slide["text"],
              'imageUmbMediaId' => $video_slide["image"],
              'hideTitle' => $area["value"]["value"]["hideTitle"],
              'backgroundColor' => $area["value"]["value"]["backgroundColor"]['value'],
              'leafMotif' => $area["value"]["value"]["leafMotif"],
            ];
          }
        }
      }
    }
    return $properties;
  }

}
