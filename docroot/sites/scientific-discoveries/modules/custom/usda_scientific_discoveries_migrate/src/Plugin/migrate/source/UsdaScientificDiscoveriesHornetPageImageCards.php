<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Defines UsdaScientificDiscoveriesHornetPageImageCards migrate source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_scientific_discoveries_hornet_page_image_cards". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_scientific_discoveries_hornet_page_image_cards",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesHornetPageImageCards extends UsdaArsSource {

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
    // OR group: home page or Asian Hornet Central page (node 6026).
    $condition = $query->orConditionGroup()
      ->condition('ct.alias', 'homePage')
      ->condition('c.nodeId', 6026);
    $query->condition($condition);
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
    foreach ($it as $row) {
      if (empty($row['content'])) {
        continue;
      }
      $components = $this->getContentComponents($row['content']);
      foreach ($components as $index => $component) {
        // Only one set of Image Cards we have here.
        $imageCards = $component['imageCards'];
        $cardType = $component['cardTypeAlias'];
        foreach ($imageCards as $imageCard) {
          $new_row = $row;
          $new_row['cardType'] = $cardType;
          $new_row['cardUuid'] = $imageCard['cardUuid'];
          $new_row['title'] = $imageCard['title'];
          $new_row['text'] = $imageCard['text'];
          $new_row['image'] = $imageCard['image'];
          $new_row['actionLinkUrl'] = $imageCard['actionLinkUrl'];
          $new_row['actionLinkName'] = $imageCard['actionLinkName'];
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
   *
   * @return array
   *   The decoded Home Page Content Components returned as Properties.
   */
  private function getContentComponents(string $json_content) {
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
        $content_type = $area['value']['dtgeContentTypeAlias'];
        if (in_array($content_type, ['blImageCards', 'blIconRow'])) {
          $imageCards = $content_type == 'blImageCards' ? $area["value"]["value"]["imageCards"] : $area["value"]["value"]["iconItems"];
          $properties[$index]['cardTypeAlias'] = $content_type;
          foreach ($imageCards as $delta => $imageCard) {
            $properties[$index]['imageCards'][$delta]['cardUuid'] = $imageCard['key'];
            $properties[$index]['imageCards'][$delta]['text'] = $imageCard['text'];
            $properties[$index]['imageCards'][$delta]['title'] = $imageCard['title'];
            $properties[$index]['imageCards'][$delta]['image'] = $imageCard['image'];
            $link_url = $imageCard['actionLink'][0]['url'];
            if ($link_url[0] == '/') {
              $link_url = 'internal:' . $link_url;
            }
            $properties[$index]['imageCards'][$delta]['actionLinkUrl'] = $link_url;
            $properties[$index]['imageCards'][$delta]['actionLinkName'] = $imageCard['actionLink'][0]['name'];
          }
        }
      }
    }
    return $properties;
  }
}
