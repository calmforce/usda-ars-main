<?php

namespace Drupal\usda_tellus_migrate\Plugin\migrate\source;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\Plugin\migrate\source\UsdaArsSource;

/**
 * Source plugin for content aliases.
 *
 * @MigrateSource(
 *   id = "usda_tellus_article_aliases",
 *   source_module = "usda_tellus_migrate"
 * )
 */
class UsdaTellusArticleAliases extends UsdaArsSource {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('cmsContentXml', 'x');
    $query->fields('x', ['nodeId', 'xml']);
    $query->condition('x.xml', '%<article id=%', 'LIKE');

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
      $xml = $row->getSourceProperty('xml');
      if (strpos($xml, 'urlName="') !== FALSE) {
        // The /s modifier in the pattern means ". matches new line".
        $node_alias = preg_replace('/^.*urlName="([^"]+)".*$/uis', '/stories/articles/$1', $xml);
      }
      if (!empty($node_alias)) {
        $row->setSourceProperty('nodeAlias', $node_alias);
        return parent::prepareRow($row);
      }
      return FALSE;
    }
    return parent::prepareRow($row);
  }

}
