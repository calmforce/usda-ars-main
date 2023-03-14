<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Defines UsdaTellusArticles migrate source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_tellus_articles". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_tellus_articles",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusArticles extends UsdaArsSource {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('cmsContent', 'c');
    $query->innerJoin('cmsContentType', 'ct', 'c.contentType = ct.nodeId');
    $query->innerJoin('umbracoNode', 'n', 'n.id = c.nodeId');
    $query->innerJoin('cmsDocument', 'd', 'd.nodeId = c.nodeId');

    $query->addField('c', 'nodeId', 'nodeId');
    $query->addField('n', 'text', 'nodeName');
    $query->addField('d', 'updateDate', 'updateDate');
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path']);
    $query->condition('ct.alias', 'article');
    $query->condition('d.published', 1);
    // Infographic: 5535, 5240, 5788.
    // data-udi: 5788, 6160.
    // imageColumns: 5652, 5995, 5948.
    // Embedded images with links: 6231, 5242, 5256, 5284, 5333...
    $query->orderBy('n.id', 'ASC');
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
      $this->umbracoDbQueryService->getDatabase('tellus');
      $properties = $this->umbracoDbQueryService->getNodeProperties($row->getSourceProperty('nodeId'));

      $main_content = $properties['mainContent']->dataNtext;
      if (!empty($main_content)) {
        $row->setSourceProperty('main_content', $main_content);
      }
      // SEO fields.
      $this->addSeoProperties($properties, $row);
      // Article-specific fields.
      $body_summary = strip_tags($properties['excerpt']->dataNtext);
      if (!empty($body_summary)) {
        $row->setSourceProperty('body_summary', $body_summary);
      }
      $publish_date = $properties['publishDate']->dataDate;
      if (!empty($publish_date)) {
        $row->setSourceProperty('publish_date', $publish_date);
      }
      $publish_year = $properties['publishYear']->dataNvarchar;
      if (!empty($publish_year)) {
        $row->setSourceProperty('publish_year', $publish_year);
      }
      $category = $properties['category']->dataNtext;
      if (!empty($category)) {
        $row->setSourceProperty('category', $category);
      }
      $featured_image = $properties['featuredImage']->dataNtext;
      if (!empty($featured_image)) {
        $row->setSourceProperty('featured_image', $featured_image);
      }
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  protected function addSeoProperties(array $properties, Row $row) {
    // SEO metatags.
    $keywords = $properties['metaKeywords']->dataNtext;
    $html_title = $properties['browserTitle']->dataNvarchar;
    $page_description = $properties['metaDescription']->dataNvarchar;
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

}
