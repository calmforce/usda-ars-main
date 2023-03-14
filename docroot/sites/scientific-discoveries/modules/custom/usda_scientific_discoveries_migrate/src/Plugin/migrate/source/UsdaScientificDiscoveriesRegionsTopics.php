<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Defines UsdaScientificDiscoveriesRegionsTopics migrate source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_scientific_discoveries_regions_topics". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_scientific_discoveries_regions_topics",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesRegionsTopics extends UsdaArsSource {

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
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path']);
    $query->condition('ct.alias', $this->configuration['source_node_types'], 'IN');
    $query->condition('d.published', 1);
    $query->orderBy('n.sortOrder', 'ASC');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nodeId' => $this->t('Node ID'),
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
   */
  public function prepareRow(Row $row) {
    // The source properties can be added or modified in prepareRow().
    if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
      try {
        $datetime = strtotime($row->getSourceProperty('updateDate'));
        if ($datetime) {
          $row->setSourceProperty('datetime', $datetime);
        }
        $this->umbracoDbQueryService->getDatabase('scientific_discoveries');
        $properties = $this->umbracoDbQueryService->getSDNodeProperties($row->getSourceProperty('nodeId'));
        $body_summary = strip_tags($properties['abstract']->textValue);
        if (!empty($body_summary)) {
          $row->setSourceProperty('body_summary', $body_summary);
        }
        $publish_date = $properties['publishedDate']->dateValue;
        if (!empty($publish_date)) {
          $row->setSourceProperty('publish_date', $publish_date);
        }
        $featured_image = $properties['featuredImage']->textValue;
        if (!empty($featured_image)) {
          $row->setSourceProperty('featured_image', $featured_image);
        }
        if (!empty($hide_page_title = $properties['hidePageTitle']->intValue)) {
          $row->setSourceProperty('hidePageTitle', $hide_page_title);
        }
        // Get more properties from JSON content.
        $content = $properties['content']->textValue;
        $properties = $this->getRegionTopicContentComponents($content, $properties);
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
        $nodeId = $row->getSourceProperty('nodeId');
        if (in_array('topicPage', $this->configuration['source_node_types'])) {
          $topic = $properties['topic']->textValue;
          if (!empty($topic)) {
            $row->setSourceProperty('topic', $topic);
            if ($nodeId == 1187) {
              // Explore Our Discoveries topic.
              $row->setSourceProperty('layout', 'explore_our_discoveries_topic');
            }
            elseif ($nodeId == 6341) {
              // ARS National Program Areas topic.
              $row->setSourceProperty('layout', 'national_program_areas');
            }
          }
        }
        $image_cards = $this->getImageCards($content, $nodeId, $this->configuration['key']);
        if (!empty($image_cards)) {
          $row->setSourceProperty('imageCards', $image_cards['imageCards']);
          $row->setSourceProperty('imageCardsNodeIds', $image_cards['imageCardsNodeIds']);
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
          if (empty($body_summary)) {
            $row->setSourceProperty('body_summary', $page_description);
          }
        }
      }
      catch (\Exception $e) {
        return FALSE;
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * Gets Region Main Content Components.
   *
   * @param string $json_content
   *   The Json string.
   * @param array $properties
   *   Array of Region properties.
   *
   * @return array
   *   The decoded Region Main Content Components added to Properties.
   */
  private function getRegionTopicContentComponents(string $json_content, &$properties): array
  {
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
    if (!empty($areas = $content_raw["sections"][0]["rows"][0]["areas"][0]["controls"])) {
      foreach ($areas as $area) {
        if ($area['value']['dtgeContentTypeAlias'] == 'blHeroSlides') {
          $properties['heroSlideUmbMediaId'] = $area["value"]["value"]["slides"][0]["image"];
          $properties['heroSlideOverlayTitle'] = $area["value"]["value"]["slides"][0]["slideOverlayTitle"];
        }
        elseif ($area['value']['dtgeContentTypeAlias'] == 'blContentWithImage') {
          $content_with_image[] = [
            'title' => $area["value"]["value"]["title"],
            'hideTitle' => $area["value"]["value"]["hideTitle"],
            'richContent' => $area["value"]["value"]["richContent"],
            'contentImage' => $area["value"]["value"]["featuredImage"],
            'imageOnLeft' => $area["value"]["value"]["imageOnLeft"],
            'imageCaption' => $area["value"]["value"]["imageCaption"],
          ];
          if (!empty($area["value"]["value"]["featuredImage"])) {
            $main_content_images[] = $area["value"]["value"]["featuredImage"];
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
    return $properties;
  }

}
