<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;
use Drupal\usda_tellus_migrate\Plugin\migrate\source\UsdaTellusSlidesJsonCaptions;

/**
 * Source plugin for Image Media links.
 *
 * @MigrateSource(
 *   id = "usda_scientific_discoveries_image_links",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesImageLinks extends UsdaArsSource {

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
    $query->addField('cv','versionDate', 'createDate');
    $query->addField('cv','userId', 'nodeUser');
    $query->condition('doc.published', 1);
    $query->condition('ct.alias', ['articlePage', 'primeDiscovery', 'generalContentPage'], 'IN');
    $query->condition('pd.textValue', '%imageLink":%', 'LIKE');
    $query->condition('cv.current', 1);

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
      'imageUuid' => [
        'type' => 'string',
        'alias' => 'iuuid',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Media ID'),
      'mediaName' => $this->t('Media Name'),
      'createDate' => $this->t('Date Created'),
      'nodeUser' => $this->t('Media User'),
      'imageUuid' => $this->t('Image UUID'),
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
   * {@inheritdoc}
   * Gets the source rows count using Iterator.
   */
  protected function doCount() {
    return iterator_count($this->initializeIterator());
  }

  /**
   * Prepares one row per 'image with link' JSON row in the source SQL row.
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
      $imageLinks = $this->getImageLinks($row['content']);
      foreach ($imageLinks as $imageWithLink) {
        $new_row = $row;
        $new_row['imageUuid'] = $imageWithLink['image_uuid'];
        $new_row['imageLinkUrl'] = $imageWithLink['image_link_url'];
        $new_row['imageLinkName'] = $imageWithLink['image_link_name'];
        yield($new_row);
      }
    }
  }

  /**
   * Gets imageLinks Properties.
   *
   * @param string $json_content
   *   The Json string.
   *
   * @return array
   *   The decoded imageLinks Properties.
   */
  private function getImageLinks(string $json_content) {
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
    $sections = $content_raw["sections"][0]["rows"][0]["areas"][0]["controls"];
    // Extract properties.
    foreach ( $sections as $index => $section) {
      if ($section['value']['dtgeContentTypeAlias'] == 'blContentWithImage') {
        $section_content = $section['value']['value'];
        if (!empty($section_content['imageLink'] )) {
          $imageLink = $section_content['imageLink'];
          if (strlen($imageLink[0]["url"]) > 1) {
            // At least one of them has dot "." for the link.
            $image_uuid = $this->formatUuid(substr($section_content['featuredImage'], 12));
            $properties[] = [
              'image_uuid' => $image_uuid,
              'image_link_url' => $imageLink[0]["url"],
              'image_link_name' => $imageLink[0]["name"],
            ];
          }
        }
      }
    }
    return $properties;
  }

}
