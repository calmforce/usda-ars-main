<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Defines UsdaAglabArticles migrate source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_aglab_articles". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_aglab_articles",
 *   source_module = "usda_aglab_migrate"
 * )
 */
class UsdaAglabArticles extends UsdaArsSource {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('[umbracoContent]', 'c');
    $query->innerJoin('cmsContentType', 'ct', 'c.contentTypeId = ct.nodeId');
    $query->innerJoin('umbracoNode', 'n', 'n.id = c.nodeId');
    $query->innerJoin('umbracoDocument', 'd', 'c.nodeId = d.nodeId');
    // Add the parent tables.
    $query->innerJoin('umbracoNode', 'pn', 'pn.id = n.parentID');
    $query->innerJoin('umbracoContent', 'pc', 'pn.id = pc.nodeId');
    $query->innerJoin('cmsContentType', 'pct', 'pct.nodeId = pc.contentTypeId');

    $query->fields('d', ['nodeId']);
    $query->addField('n', 'text', 'nodeName');
    $query->addField('n','uniqueID', 'uuid');
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path']);
    $query->addField('pct','alias', 'parentNodeType');
    $query->condition('ct.alias', ['articlePage', 'primeDiscovery', 'generalContentPage'], 'IN');
    $query->condition('d.published', 1);
    // $query->condition('d.nodeId', [4430, 4882, 6103, 6146, 7148, 7236], 'IN');
    $query->orderBy('n.sortOrder', 'ASC');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Node ID'),
      'nodeName' => $this->t('Title'),
      'sortOrder' => $this->t('Sort Order'),
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
      $this->umbracoDbQueryService->getDatabase('aglab');
      $properties = $this->umbracoDbQueryService->getSDNodeProperties($row->getSourceProperty('nodeId'));
      if (!empty($properties['updateDate'])) {
        $row->setSourceProperty('updateDate', $properties['updateDate']);
      }
      else {
        $row->setSourceProperty('updateDate', $row->getSourceProperty('createDate'));
      }
      $body_summary = strip_tags($properties['abstract']->textValue);
      if (!empty($body_summary)) {
        $row->setSourceProperty('body_summary', $body_summary);
      }
      $publish_date = $properties['publishedDate']->dateValue;
      if (!empty($publish_date)) {
        $row->setSourceProperty('publish_date', $publish_date);
      }
      $category = $properties['topic']->textValue;
      if (!empty($category)) {
        $row->setSourceProperty('category', $category);
      }
      $tags = $properties['tags']->textValue;
      if (!empty($tags)) {
        $row->setSourceProperty('tags', $tags);
      }
      $featured_image = $properties['featuredImage']->textValue;
      if (!empty($featured_image)) {
        $row->setSourceProperty('featured_image', $featured_image);
      }
      if (!empty($short_name = $properties['shortName']->varcharValue)) {
        $row->setSourceProperty('shortName', $short_name);
      }
      if (!empty($hide_page_title = $properties['hidePageTitle']->intValue)) {
        $row->setSourceProperty('hidePageTitle', $hide_page_title);
      }
      if ($row->getSourceProperty('parentNodeType') == 'regionalPage') {
        $row->setSourceProperty('regionId', $row->getSourceProperty('parentID'));
      }
      // Get more properties from JSON content.
      $content = $properties['content']->textValue;
      $properties = $this->getArticleContentComponents($content, $properties);
      if (!empty($properties['heroSlideUmbMediaId'])) {
        $row->setSourceProperty('hero_image', $properties['heroSlideUmbMediaId']);
        $row->setSourceProperty('heroSlideOverlayTitle', $properties['heroSlideOverlayTitle']);
      }
      if (!empty($properties['main_content'])) {
        $row->setSourceProperty('main_content', $properties['main_content']);
      }
      if (!empty($properties['main_content_images'])) {
        $row->setSourceProperty('main_content_images', $properties['main_content_images']);
      }
      if (!empty($properties['external_video'])) {
        $row->setSourceProperty('external_video', $properties['external_video']);
      }
      if (!empty($properties['gallery_carousel'])) {
        $row->setSourceProperty('gallery_carousel', $properties['gallery_carousel']);
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
   * Gets Article Main Content Components.
   *
   * @param string $json_content
   *   The Json string.
   * @param array $properties
   *   Array of Article properties.
   *
   * @return array
   *   The decoded Article Main Content Components added to Properties.
   */
  private function getArticleContentComponents(string $json_content, &$properties) {
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
    $content_with_image = [];
    $main_content_images = [];
    $gallery_carousel = [];
    if (!empty($areas = $content_raw["sections"][0]["rows"][0]["areas"][0]["controls"])) {
      foreach ($areas as $area_id => $area) {
        if ($area["value"]["value"]["hidden"] == '1') {
          continue;
        }
        $section_type = $area['value']['dtgeContentTypeAlias'];
        if (in_array($section_type, ['blContentWithImage', 'blImageGalleryCarousel', 'blVideoSlider', 'blLargeVideo'])) {
          $content_with_image[$area_id] = [
            'section_type' => $section_type,
            'title' => $area["value"]["value"]["title"],
            'hideTitle' => $area["value"]["value"]["hideTitle"],
            'backgroundColor' => $area["value"]["value"]["backgroundColor"]['value'],
            'leafMotif' => $area["value"]["value"]["leafMotif"],
          ];
        }
        if ($section_type == 'blHeroSlides') {
          $properties['heroSlideUmbMediaId'] = $area["value"]["value"]["slides"][0]["image"];
          $overlay_title = $area["value"]["value"]["slides"][0]["slideOverlayTitle"];
          $overlay_text = $area["value"]["value"]["slides"][0]["slideOverlayText"];
          $properties['heroSlideOverlayTitle'] = empty($overlay_text) ? $overlay_title : $overlay_text;
        }
        elseif ($section_type == 'blFullScreenImage') {
          $properties['heroSlideUmbMediaId'] = $area["value"]["value"]["fullScreenImage"];
        }
        elseif ($section_type == 'blLargeVideo') {
          $properties['external_video'][] = $area["value"]['id'];
        }
        elseif ($section_type == 'blContentWithImage') {
          $content_with_image[$area_id] += [
            'richContent' => $area["value"]["value"]["richContent"],
            'contentImage' => $area["value"]["value"]["featuredImage"],
            'imageOnLeft' => $area["value"]["value"]["imageOnLeft"],
            'imageCaption' => $area["value"]["value"]["imageCaption"],

          ];
          if (!empty($area["value"]["value"]["featuredImage"])) {
            $main_content_images[] = $area["value"]["value"]["featuredImage"];
          }
        }
        elseif (in_array($section_type, ['blImageGalleryCarousel', 'blVideoSlider'])) {
          $gallery_slides = $area["value"]["value"]["imageSlides"] ?: $area["value"]["value"]["videoSlides"];
          if (!empty($gallery_slides)) {
            $content_with_image[$area_id]['slides'] = TRUE;
            foreach ($gallery_slides as $slide) {
              if ($section_type == 'blVideoSlider') {
                $gallery_carousel[] = $slide['key'];
              }
              else {
                $gallery_carousel[] = $this->formatUuid(substr($slide['image'], 12));
              }
            }
          }
        }
      }
    }
    if (!empty($content_with_image)) {
      $properties['main_content'] = $content_with_image;
    }
    if (!empty($main_content_images)) {
      $properties['main_content_images'] = $main_content_images;
    }
    if (!empty($gallery_carousel)) {
      $properties['gallery_carousel'] = $gallery_carousel;
    }
    return $properties;
  }
}
