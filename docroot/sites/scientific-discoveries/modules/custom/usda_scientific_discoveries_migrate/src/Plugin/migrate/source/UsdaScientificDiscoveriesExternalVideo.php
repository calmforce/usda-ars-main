<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for External Video media.
 *
 * @MigrateSource(
 *   id = "usda_scientific_discoveries_external_video",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesExternalVideo extends UsdaArsSource {

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
    $query->condition('pd.textValue', '%blLargeVideo%', 'LIKE');
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
        $properties = $this->getVideoProperties($row->getSourceProperty('content'));
        if (!empty($properties)) {
          $row->setSourceProperty('uuid', $properties['mediaUUID']);
          $row->setSourceProperty('mediaName', $properties['mediaName']);
          $row->setSourceProperty('videoUrl', $properties['videoUrl']);
          $row->setSourceProperty('videoAltText', $properties['videoAltText']);
          $row->setSourceProperty('textBelowVideo', $properties['textBelowVideo']);
          $row->setSourceProperty('imageUmbMediaId', $properties['imageUmbMediaId']);
          $row->setSourceProperty('hideTitle', $properties['hideTitle']);
          $row->setSourceProperty('backgroundColor', $properties['backgroundColor']);
          $row->setSourceProperty('leafMotif', $properties['leafMotif']);
        }
      } catch (\Exception $e) {
        return FALSE;
      }
    }

    return parent::prepareRow($row);
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
        if ($area['value']['dtgeContentTypeAlias'] == 'blLargeVideo') {
          $properties['mediaUUID'] = $area["value"]["id"];
          $properties['mediaName'] = $area['value']['value']['title'];
          $properties['videoUrl'] = $area["value"]["value"]["youTubeVideo"];
          $properties['videoAltText'] = $area["value"]["value"]["videoAltText"];
          $properties['textBelowVideo'] = $area["value"]["value"]["textBelowVideo"];
          $properties['imageUmbMediaId'] = $area["value"]["value"]["image"];
          $properties['hideTitle'] = $area["value"]["value"]["hideTitle"];
          $properties['backgroundColor'] = $area["value"]["value"]["backgroundColor"]['label'];
          $properties['leafMotif'] = $area["value"]["value"]["leafMotif"];
          break;
        }
      }
    }
    return $properties;
  }

}
