<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for Slides JSON Captions.
 *
 * @MigrateSource(
 *   id = "usda_tellus_slides_json_links",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusSlidesJsonLinks extends UsdaArsSource {

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
    $query->condition('pd.dataNtext', '%slides%', 'LIKE');
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
        'alias' => 'nid',
      ],
      'slideUuid' => [
        'type' => 'string',
        'alias' => 'suuid',
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
      'slideUuid' => $this->t('Slide UUID'),
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
   * Prepares one row per 'slide' JSON row in the source SQL row.
   *
   * @param \Iterator $it
   *   The source Iterator object.
   *
   * @codingStandardsIgnoreStart
   *
   * @return \Generator
   *   A new row, one for each 'slide' row in the source JSON data.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield($it) {
    foreach ($it as $row) {
      if(!$this->getMainContent($row)) {
        continue;
      }
      $slides = $this->getSlideLinks($row['main_content']);
      foreach ($slides as $slide) {
        if (!empty($slide['slide_link_url'])) {
          $new_row = $row;
          $new_row['slideUuid'] = $slide['slide_uuid'];
          $new_row['slideLinkUrl'] = $slide['slide_link_url'];
          $new_row['slideLinkName'] = $slide['slide_link_name'];
          yield($new_row);
        }
      }
    }
  }

  /**
   * Gets Slides Properties.
   *
   * @param string $json_content
   *   The Json string.
   *
   * @return array
   *   The decoded Slides Properties.
   */
  private function getSlideLinks(string $json_content) {
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
    foreach ( $content_raw as $index => $section) {
      if (!empty($section['slides'])) {
        $slides_decoded = json_decode($section['slides'], true);
        foreach ($slides_decoded as $slide) {
          if (!empty($slide['link'])) {
            $slide_uuid = $this->formatUuid(substr($slide['featuredImage'],12));
            $link = json_decode($slide['link'], true);
            $properties[] = [
              'slide_uuid' => $slide_uuid,
              'slide_link_url' => empty($link[0]["url"]) ? NULL : $link[0]["url"],
              'slide_link_name' => empty($link[0]["name"]) ? NULL : $link[0]["name"],
            ];
          }
        }
      }
    }
    return $properties;
  }

  /**
   * Gets Main HTML Content for Tellus DB Row.
   *
   * @param array $row
   *   Row array.
   *
   * @return bool
   *   TRUE if no exceptions, FALSE otherwise.
   */
  protected function getMainContent(array &$row) {
    try {
      $this->umbracoDbQueryService->getDatabase('tellus');
      $properties = $this->umbracoDbQueryService->getNodeProperties($row['nodeId']);
      $main_content = $properties['mainContent']->dataNtext;
      if (!empty($main_content)) {
        $row['main_content'] = $main_content;
        return TRUE;
      }
      return FALSE;
    } catch (\Exception $e) {
      return FALSE;
    }
  }

}
