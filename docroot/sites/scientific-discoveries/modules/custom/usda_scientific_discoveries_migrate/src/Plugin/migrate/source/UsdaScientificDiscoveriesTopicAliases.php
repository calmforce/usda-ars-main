<?php

namespace Drupal\usda_scientific_discoveries_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for content aliases.
 *
 * @MigrateSource(
 *   id = "usda_scientific_discoveries_topic_aliases",
 *   source_module = "usda_scientific_discoveries_migrate"
 * )
 */
class UsdaScientificDiscoveriesTopicAliases extends UsdaArsSource {

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
    $query->condition('ct.alias', 'topicPage');

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
      'topicAlias' => $this->t('Topic Alias'),
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
      if (strpos($data, 'urlSegment') !== FALSE) {
        // The /s modifier in the pattern means ". matches new line".
        $node_alias = preg_replace('/^.*urlSegment":"([^"]+)".*$/uis', '$1', $data);
      }
      if (!empty($node_alias)) {
        $row->setSourceProperty('topicAlias', '/' . $node_alias);
        $this->umbracoDbQueryService->getDatabase('scientific_discoveries');
        $properties = $this->umbracoDbQueryService->getSDNodeProperties($row->getSourceProperty('nodeId'));
        $topic = $properties['topic']->textValue;
        if (!empty($topic)) {
          $row->setSourceProperty('topic', $topic);
        }
        return parent::prepareRow($row);
      }
      return FALSE;
    }
    return parent::prepareRow($row);
  }

}
