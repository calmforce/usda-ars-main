<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;

/**
 * Defines UsdaArsNewsArticles migrate source plugin.
 *
 * This annotation tells Drupal that the name of the MigrateSource plugin
 * implemented by this class is "usda_ars_content_pages". This is the name
 * that the migration configuration references with the source "plugin" key.
 *
 * @MigrateSource(
 *   id = "usda_ars_news_articles",
 *   source_module = "usda_ars_migrate"
 * )
 */
class UsdaArsNewsArticles extends UsdaArsSource {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('cmsContent', 'c');
    $query->innerJoin('cmsContentType', 'ct', 'c.contentType = ct.nodeId');
    $query->innerJoin('umbracoNode', 'n', 'n.id = c.nodeId');
    $query->innerJoin('cmsDocument', 'd', 'd.nodeId = c.nodeId');
    // Add the parent tables.
    $query->innerJoin('umbracoNode', 'pn', 'pn.id = n.parentID');
    $query->innerJoin('cmsContent', 'pc', 'pn.id = pc.nodeId');
    $query->innerJoin('cmsContentType', 'pct', 'pct.nodeId = pc.contentType');
    $query->innerJoin('cmsDocument', 'pd', 'pd.nodeId = pc.nodeId');

    $query->addField('c', 'nodeId', 'nodeId');
    $query->addField('n', 'text', 'nodeName');
    $query->addField('d', 'updateDate', 'updateDate');
    $query->addField('ct', 'alias', 'node_type');
    $query->addField('pct', 'alias', 'parent_type');
    $query->fields('n', ['nodeUser', 'sortOrder', 'createDate', 'parentID', 'level', 'path']);
    $query->condition('ct.alias', 'NewsArticle');
    $query->condition('d.published', 1);
    // Parent has to be published as well.
    $query->condition('pd.published', 1);
//    $query->condition('c.nodeId', [175103, 237716, 214014, 215142, 239696, 237924], 'IN');
    $query->orderBy('level', 'ASC');
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
      $created = $row->getSourceProperty('createDate');
      $created_timestamp = strtotime($created);
      $row->setSourceProperty('created', $created_timestamp);
      $changed = $row->getSourceProperty('updateDate');
      $changed_timestamp = strtotime($changed);
      $row->setSourceProperty('changed', $changed_timestamp);
      $properties = $this->umbracoDbQueryService->getNodeProperties($row->getSourceProperty('nodeId'));
      // Node URL, if set.
      $node_url = $properties['umbracoUrlName']->dataNvarchar;
      if (!empty($node_url)) {
        $row->setSourceProperty('node_url', $node_url);
      }
      $body = $properties['bodyText']->dataNtext;
      if (!empty($body)) {
        $row->setSourceProperty('body', $body);
      }
      $htmlCode = $properties['htmlCode']->dataNtext;
      if (!empty($htmlCode)) {
        $row->setSourceProperty('htmlCode', $htmlCode);
      }
      // SEO fields.
      $this->addSeoProperties($properties, $row);
      // News Article-specific fields.
      $body_summary = $properties['newsBlurb']->dataNtext;
      if (!empty($body_summary)) {
        $row->setSourceProperty('body_summary', $body_summary);
      }
      $article_date = $properties['articleDate']->dataDate;
      if (!empty($article_date)) {
        $row->setSourceProperty('article_date', $article_date);
      }
      $news_topics = $properties['newsTopicsList']->dataNtext;
      if (!empty($news_topics)) {
        $row->setSourceProperty('newsTopicsList', $news_topics);
      }
    }
    return parent::prepareRow($row);
  }

}
