<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Defines UsdaScientificDiscoveriesTopicsImageCards migrate source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_scientific_discoveries_topics_image_cards". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_scientific_discoveries_topics_cards_refs",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesTopicsCardsRefs extends UsdaArsSource {

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
    $query->addField('ct','alias', 'content_type');
    $query->addField('cv','versionDate', 'versionDate');
    $query->addField('cv','userId', 'nodeUser');
    $query->condition('doc.published', 1);
    $query->condition('cv.current', 1);
    // Property id for 'content'.
    $query->condition('pt.Alias', 'content');
    // Topics Image Cards are in Homepage JSON.
    $query->condition('ct.alias', 'homePage');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Node ID'),
      'versionDate' => $this->t('Date Changed'),
      'nodeUser' => $this->t('User'),
      'cardUuid' => $this->t('Section UUID'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'cardUuid' => [
        'type' => 'string',
        'alias' => 'cuuid',
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
   * Prepares one row per content section JSON row in the source SQL row.
   *
   * @param \Iterator $it
   *   The source Iterator object.
   *
   * @codingStandardsIgnoreStart
   *
   * @return \Generator
   *   A new row, one for each content section in the source JSON data.
   *
   * @codingStandardsIgnoreEnd
   */
  public function getYield($it) {
    $db = $this->configuration['key'];
    foreach ($it as $row) {
      if (empty($row['content'])) {
        continue;
      }
      $components = $this->getContentComponents($row['content'], $db);
      foreach ($components as $index => $component) {
        // Only one set of Image Cards we have here.
        $imageCards = $component['imageCards'];
        foreach ($imageCards as $imageCard) {
          $new_row = $row;
          $new_row['cardUuid'] = $imageCard['cardUuid'];
          $new_row['topicUuid'] = $imageCard['topicUuid'];
          yield ($new_row);
        }
      }
    }
  }

  /**
   * Gets Home Page Main Content Components.
   *
   * @param string $json_content
   *   The Json string.
   * @param string $db
   *   The source DB name.
   *
   * @return array
   *   The decoded Home Page Content Components returned as Properties.
   */
  private function getContentComponents(string $json_content, string $db) {
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
    // Extract properties.
    $properties = [];
    if (!empty($areas = $content_raw["sections"][0]["rows"][0]["areas"][0]["controls"])) {
      foreach ($areas as $index => $area) {
        if ($area['value']['dtgeContentTypeAlias'] == 'blIconRow') {
          $imageCards = $area["value"]["value"]["iconItems"];
          foreach ($imageCards as $delta => $imageCard) {
            $properties[$index]['imageCards'][$delta]['cardUuid'] = $imageCard['key'];
            $topic_page_uuid = $this->formatUuid(substr($imageCard['actionLink'][0]['udi'],15));
            $topic_page_nid = $this->getNodeIdByUuid($topic_page_uuid, $db);
            $topic_page_properties = $this->umbracoDbQueryService->getSDNodeProperties($topic_page_nid);
            $topic = $topic_page_properties['topic']->textValue;
            $properties[$index]['imageCards'][$delta]['topicUuid'] = $topic;
          }
        }
      }
    }
    return $properties;
  }
}
