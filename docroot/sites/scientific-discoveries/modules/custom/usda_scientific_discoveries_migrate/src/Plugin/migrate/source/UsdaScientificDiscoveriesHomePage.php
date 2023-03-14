<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Defines UsdaScientificDiscoveriesHomePage migrate source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_scientific_discoveries_homepage". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_scientific_discoveries_homepage",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesHomePage extends UsdaArsSource {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('[umbracoContent]', 'c');
    $query->innerJoin('cmsContentType', 'ct', 'c.contentTypeId = ct.nodeId');
    $query->innerJoin('umbracoNode', 'n', 'n.id = c.nodeId');
    $query->innerJoin('umbracoDocument', 'd', 'c.nodeId = d.nodeId');

    $query->fields('d', ['nodeId']);
    $query->addField('n', 'text', 'nodeName');
    $query->addField('n','uniqueID', 'uuid');
    $query->fields('n', ['nodeUser', 'createDate']);
    $query->condition('ct.alias', 'homePage');
    $query->condition('d.published', 1);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Node ID'),
      'nodeName' => $this->t('Title'),
      'createDate' => $this->t('Date Created'),
      'updateDate' => $this->t('Date Updated'),
      'nodeUser' => $this->t('Node User'),
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
    ];
  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function prepareRow(Row $row) {
    // The source properties can be added or modified in prepareRow().
    if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
      // The row has not been imported yet.
      // We have to check because the function prepareRow() called for each row
      // in each migration batch POST request.
      $this->umbracoDbQueryService->getDatabase('scientific_discoveries');
      $properties = $this->umbracoDbQueryService->getSDNodeProperties($row->getSourceProperty('nodeId'));
      if (!empty($properties['updateDate'])) {
        $row->setSourceProperty('updateDate', $properties['updateDate']);
      }
      else {
        $row->setSourceProperty('updateDate', $row->getSourceProperty('createDate'));
      }
      // Get properties from JSON content.
      $content = $properties['content']->textValue;
      $properties = $this->getContentComponents($content, $properties);
      if (!empty($properties['heroSlideUmbMediaId'])) {
        $row->setSourceProperty('hero_image', $properties['heroSlideUmbMediaId']);
        $row->setSourceProperty('hero_image_overlay_image', $properties['heroOverlayImageUmbMediaId']);
      }
      if (!empty($properties['main_content'])) {
        $row->setSourceProperty('main_content', $properties['main_content']);
      }
      // SEO metatags.
      $keywords = $properties['metaKeywords']->varcharValue;
      $html_title = $properties['browserTitle']->varcharValue;
      $page_description = $properties['metaDescription']->textValue;
      // Add non-empty to the Row.
      if (!empty($keywords)) {
        $row->setSourceProperty('keywords', $keywords);
      }
      if (!empty($html_title)) {
        $row->setSourceProperty('htmlTitle', $html_title . ' | [site:name]');
      }
      if (!empty($page_description)) {
        $row->setSourceProperty('pageDescription', $page_description);
      }
    }
    return parent::prepareRow($row);
  }

  /**
   * Gets Home Page Main Content Components.
   *
   * @param string $json_content
   *   The Json string.
   * @param array $properties
   *   Array of Article properties.
   *
   * @return array
   *   The decoded Article Main Content Components added to Properties.
   */
  private function getContentComponents(string $json_content, &$properties) {
    $content_raw = json_decode($json_content, TRUE);
    // If json_decode() has returned NULL, it might be that the data isn't
    // valid utf8 - see http://php.net/manual/en/function.json-decode.php#86997.
    if (is_null($content_raw)) {
      $utf8response = utf8_encode($json_content);
      $content_raw = json_decode($utf8response, TRUE);
    };
    if (empty($content_raw)) {
      return $properties;
    }
    // Extract properties.
    if (!empty($areas = $content_raw["sections"][0]["rows"][0]["areas"][0]["controls"])) {
      foreach ($areas as $area) {
        if ($area['value']['dtgeContentTypeAlias'] == 'blHeroSlides') {
          $properties['heroSlideUmbMediaId'] = $area["value"]["value"]["slides"][0]["image"];
          $properties['heroOverlayImageUmbMediaId'] = $area["value"]["value"]["slides"][0]["slideOverlayImage"];
        }
        if ($area['value']['dtgeContentTypeAlias'] == 'blLargeVideo') {
          $properties['external_video'] = $area["value"]['id'];
        }
      }
    }
    return $properties;
  }
}
