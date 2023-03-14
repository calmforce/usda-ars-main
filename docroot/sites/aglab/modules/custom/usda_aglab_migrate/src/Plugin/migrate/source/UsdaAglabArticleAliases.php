<?php

namespace Drupal\usda_aglab_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for content aliases.
 *
 * @MigrateSource(
 *   id = "usda_aglab_article_aliases",
 *   source_module = "usda_aglab_migrate"
 * )
 */
class UsdaAglabArticleAliases extends UsdaArsSource {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('cmsContentNu', 'nu');
    $query->innerJoin('umbracoNode', 'n', 'n.id = nu.nodeId');
    $query->innerJoin('umbracoContent', 'c', 'c.nodeId = nu.nodeId');
    $query->innerJoin('cmsContentType', 'ct', 'c.contentTypeId = ct.nodeId');
    // Add the parent tables.
    $query->innerJoin('umbracoNode', 'pn', 'pn.id = n.parentID');
    $query->innerJoin('umbracoContent', 'pc', 'pn.id = pc.nodeId');
    $query->innerJoin('cmsContentType', 'pct', 'pct.nodeId = pc.contentTypeId');

    $query->fields('nu', ['nodeId', 'data']);
    $query->fields('n', ['parentID', 'level', 'path']);
    $query->addField('pct','alias', 'parentNodeType');
    $query->condition('nu.data', '%urlSegment%', 'LIKE');
    $query->condition('nu.published', 1);
    $query->condition('ct.alias', ['articlePage', 'primeDiscovery', 'generalContentPage'], 'IN');

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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array
  {
    $fields = [
      'nodeId' => $this->t('Node ID'),
      'nodeAlias' => $this->t('Node Alias'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function prepareRow(Row $row) {
    if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
      $data = $row->getSourceProperty('data');
      $parentNodeType = $row->getSourceProperty('parentNodeType');
      if (strpos($data, 'urlSegment') !== FALSE) {
        // The /s modifier in the pattern means ". matches new line".
        $node_alias = preg_replace('/^.*urlSegment":"([^"]+)".*$/uis', '$1', $data);
      }
      if (!empty($node_alias)) {
        if ($parentNodeType == 'topicPage') {
          $this->umbracoDbQueryService->getDatabase('aglab');
          $properties = $this->umbracoDbQueryService->getSDNodeProperties($row->getSourceProperty('parentID'));
          $topic = $properties['topic']->textValue;
          if (!empty($topic)) {
            $row->setSourceProperty('topic', $topic);
          }
        }
        $row->setSourceProperty('nodeAlias', '/' . $node_alias);
        return parent::prepareRow($row);
      }
      return FALSE;
    }
    return parent::prepareRow($row);
  }

}
