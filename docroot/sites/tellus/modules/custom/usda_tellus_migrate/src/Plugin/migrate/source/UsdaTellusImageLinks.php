<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for Image Media links.
 *
 * @MigrateSource(
 *   id = "usda_tellus_image_links",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusImageLinks extends UsdaTellusSlidesJsonCaptions {

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
    $query->condition('pd.dataNtext', '%featuredImageLink":"%', 'LIKE');
//    $query->condition('n.id', 5239);
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
    $db = $this->configuration['key'];
    foreach ($it as $row) {
      if(!$this->getMainContent($row)) {
        continue;
      }
      $imageLinks = $this->getImageLinks($row['main_content'], $db);
      foreach ($imageLinks as $imageWithLink) {
        $new_row = $row;
        $new_row['imageUuid'] = $imageWithLink['image_uuid'];
        $new_row['imageLinkUrl'] = $imageWithLink['image_link_url'];
        $new_row['imageLinkName'] = $imageWithLink['image_link_name'];
        if (!empty($imageWithLink['image_link_nid'])) {
          $new_row['imageLinkNodeId'] = $imageWithLink['image_link_nid'];
        }
        yield($new_row);
      }
    }
  }

  /**
   * Gets imageLinks Properties.
   *
   * @param string $json_content
   *   The Json string.
   * @param string $db
   *   The source DB name.
   *
   * @return array
   *   The decoded imageLinks Properties.
   */
  private function getImageLinks(string $json_content, string $db) {
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
      if (!empty($section['featuredImageLink']) && $section['featuredImageLink'] !== '[]') {
        $imageLink = json_decode($section['featuredImageLink'], true);
        if (!empty($imageLink[0]["url"]) || !empty($imageLink[0]["udi"])) {
          $image_uuid = $this->formatUuid(substr($section['featuredImage'],12));
          $properties[$index] = [
            'image_uuid' => $image_uuid,
            'image_link_url' => $imageLink[0]["url"],
            'image_link_name' => $imageLink[0]["name"],
          ];
          if (!empty($imageLink[0]["udi"])) {
            // Image link to a local node.
            $image_link_uuid = $this->formatUuid(substr($imageLink[0]["udi"],15));
            $properties[$index]['image_link_nid'] = $this->getNodeIdByUuid($image_link_uuid, $db);
          }
        }
      }
    }
    return $properties;
  }

}
