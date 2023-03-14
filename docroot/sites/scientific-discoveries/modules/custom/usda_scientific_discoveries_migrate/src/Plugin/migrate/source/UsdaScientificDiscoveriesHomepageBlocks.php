<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Defines UsdaScientificDiscoveriesHomepageBlocks migrate source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_scientific_discoveries_homepage_blocks". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_scientific_discoveries_homepage_blocks",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesHomepageBlocks extends UsdaArsSource {

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
    // OR group: home page or Asian Hornet Central page (node 4711).
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
      'sectionUuid' => $this->t('Section UUID'),
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
      'sectionUuid' => [
        'type' => 'string',
        'alias' => 'suuid',
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
      foreach ($components as $component) {
        $new_row = $row;
        $new_row['sectionUuid'] = $component['section_uuid'];
        $new_row['title'] = $component['title'];
        $new_row['subTitle'] = $component['subTitle'];
        $new_row['hideTitle'] = $component['hideTitle'];
        $new_row['dtgeContentTypeAlias'] = $component['dtgeContentTypeAlias'];
        $new_row['leafMotif'] = $component['leafMotif'];
        $new_row['backgroundColor'] = $component['backgroundColor'];
        if ($component['dtgeContentTypeAlias'] == 'blInfographic') {
          $new_row['infographicMediaId'] = $component['infographicMediaId'];
          $new_row['infographicCaption'] = $component['infographicCaption'];
          if (!empty($component['infographicLinkUrl'])) {
            $new_row['infographicLinkUrl'] = $component['infographicLinkUrl'];
            $new_row['infographicLinkText'] = $component['infographicLinkText'];
          }
        }
        elseif ($component['dtgeContentTypeAlias'] == 'blTwoColumnMediaSection') {
          $new_row['leftItem'] = $component['leftItem'];
          $new_row['rightItem'] = $component['rightItem'];
        }
        elseif ($component['dtgeContentTypeAlias'] == 'blVideoList') {
          $new_row['videoList'] = $component['videoList'];
        }
        elseif ($component['dtgeContentTypeAlias'] == 'blImageGalleryCarousel') {
          $new_row['imageSlides'] = $component['imageSlides'];
        }
        elseif ($component['dtgeContentTypeAlias'] == 'blImageCards') {
          $new_row['imageCards'] = $component['imageCards'];
        }
        elseif ($component['dtgeContentTypeAlias'] == 'blContentWithImage') {
          $new_row['main_content'] = $component['main_content'];
          $new_row['main_content_images'] = $component['main_content_images'];
          $new_row['featuredImage'] = $component['featuredImage'];
        }
        yield ($new_row);
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
    $component_types = [
      'blInfographic',
      'blLargeVideo',
      'blTwoColumnMediaSection',
      'blVideoList',
      'blContentWithImage',
      'blImageGalleryCarousel',
      'blImageCards',
      ];
    // Extract properties.
    $properties = [];
    if (!empty($areas = $content_raw["sections"][0]["rows"][0]["areas"][0]["controls"])) {
      foreach ($areas as $index => $area) {
        $componentType = $area['value']['dtgeContentTypeAlias'];
        if (in_array($componentType, $component_types)) {
          $properties[$index] = [
            'dtgeContentTypeAlias' => $componentType,
            'section_uuid' => $area['value']['id'],
            'title' => trim($area["value"]["value"]["title"]),
            'hideTitle' => $area["value"]["value"]['hideTitle'],
            'subTitle' => $area["value"]["value"]['subTitle'],
          ];
          // Specific content types.
          if ($componentType == 'blInfographic') {
            $properties[$index]['infographicMediaId'] = $area["value"]["value"]["infographic"];
            $properties[$index]['infographicCaption'] = $area["value"]["value"]["infographicCaption"];
            $link_url = $area["value"]["value"]["infographicLink"][0]['url'];
            if (!empty($link_url)) {
              if ($link_url[0] == '/') {
                if (strpos($link_url,'/media/') === 0) {
                  $link_url = 'base:/' . $link_url;
                }
                else {
                  $link_url = 'internal:' . $link_url;
                }
              }
              $properties[$index]['infographicLinkUrl'] = $link_url;
              $properties[$index]['infographicLinkText'] = $area["value"]["value"]["infographicLink"][0]['name'];
            }
          } elseif (in_array($componentType, ['blLargeVideo', 'blTwoColumnMediaSection', 'blImageGalleryCarousel', 'blContentWithImage'])) {
            $properties[$index]['leafMotif'] = $area["value"]["value"]['leafMotif'];
            $properties[$index]['backgroundColor'] = $area["value"]["value"]['backgroundColor']["value"];
            if ($componentType == 'blTwoColumnMediaSection') {
              $properties[$index]['leftItem'] = $area["value"]["value"]['leftItem'][0]['image'];
              $properties[$index]['rightItem'] = $area["value"]["value"]['rightItem'][0]['image'];
            }
          }
          if ($componentType == 'blVideoList') {
            $videoItems = $area["value"]["value"]["videos"];
            foreach ($videoItems as $delta => $videoItem) {
              $properties[$index]['videoList'][$delta] = $videoItem['key'];
            }
          }
          elseif ($componentType == 'blImageGalleryCarousel') {
            $imageSlides = $area["value"]["value"]["imageSlides"];
            foreach ($imageSlides as $delta => $imageSlide) {
              $properties[$index]['imageSlides'][$delta] = $imageSlide['image'];
            }
          }
          elseif ($componentType == 'blContentWithImage') {
            $featured_image = $area["value"]["value"]["featuredImage"];
            $content_with_image = [
              'richContent' => $area["value"]["value"]["richContent"],
              'contentImage' => $featured_image,
              'imageOnLeft' => $area["value"]["value"]["imageOnLeft"],
              'imageCaption' => $area["value"]["value"]["imageCaption"],
            ];
            $properties[$index]['main_content'] = [$content_with_image];
            $properties[$index]['featuredImage'] = $area["value"]["value"]["featuredImage"];
            $properties[$index]['main_content_images'] = [$featured_image];
          }
          elseif ($componentType == 'blImageCards') {
            $imageCards = $area["value"]["value"]["imageCards"];
            foreach ($imageCards as $delta => $imageCard) {
              $properties[$index]['imageCards'][$delta] = $imageCard['key'];
            }
          }
        }
      }
    }
    return $properties;
  }
}
