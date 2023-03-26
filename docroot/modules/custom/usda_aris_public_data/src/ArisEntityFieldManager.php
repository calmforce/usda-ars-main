<?php

namespace Drupal\usda_aris_public_data;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api_solr\SearchApiSolrException;
use Drupal\search_api_solr\SolrBackendInterface;
use Drupal\search_api_solr\SolrCloudConnectorInterface;
use Drupal\search_api_solr\SolrFieldManagerInterface;
use Drupal\search_api_solr\TypedData\SolrFieldDefinition;
use Psr\Log\LoggerInterface;

/**
 * Manages the discovery of Solr fields.
 */
class ArisEntityFieldManager implements ArisEntityFieldManagerInterface {

  use UseCacheBackendTrait;
  use StringTranslationTrait;
  use LoggerTrait;

  /**
   * Static cache of field definitions per Solr server.
   *
   * @var array
   */
  protected $fieldDefinitions;

  /**
   * Constructs a new SorFieldManager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger for Search API.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(CacheBackendInterface $cache_backend, LoggerInterface $logger) {
    $this->cacheBackend = $cache_backend;
    $this->setLogger($logger);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function getFieldDefinitions(IndexInterface $index, MigrationInterface $migration): array {
    // We need to prevent the use of the field definition cache when we are
    // about to save changes, or the property check in Index::presave will work
    // with stale cached data and remove newly added property definitions.
    // We take the presence of $index->original as indicator that the config
    // entity is being saved.
    if (!empty($index->original)) {
      return $this->buildFieldDefinitions($index, $migration);
    }

    $migration_id = $migration->id();
    $index_id = $index->id();
    if (!isset($this->fieldDefinitions[$index_id . ':' . $migration_id])) {
      // Not prepared, try to load from cache.
      $cid = 'aris_field_definitions:' . $index_id . ':' . $migration_id;
      if ($cache = $this->cacheGet($cid)) {
        $field_definitions = $cache->data;
      }
      else {
        $field_definitions = $this->buildFieldDefinitions($index, $migration);
        $this->cacheSet($cid, $field_definitions, Cache::PERMANENT);
      }

      $this->fieldDefinitions[$index_id . ':' . $migration_id] = $field_definitions;
    }
    return $this->fieldDefinitions[$index_id . ':' . $migration_id];
  }

  /**
   * Builds the field definitions for a Solr server.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration from which we are retrieving field information.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   The array of field definitions for the server, keyed by field name.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\search_api\SearchApiException
   */
  protected function buildFieldDefinitions(IndexInterface $index, MigrationInterface $migration): array {
    $aris_data_fields = $this->buildFieldDefinitionsFromArisSource($migration->getSourcePlugin());
    //$config_fields = $this->buildFieldDefinitionsFromConfig($index);
    $fields = $aris_data_fields; // + $config_fields;

    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $field */
    foreach ($fields as $key => $field) {
      // Always use the type as already configured in Drupal previously.
      $fields[$key]->setDataType($field->getDataType());
    }
    return $fields;
  }

  /**
   * Builds the field definitions from Aris Data source.
   *
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $aris_data_source
   *   The index from which we are retrieving field information.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   The array of field definitions for the server, keyed by field name.
   */
  protected function buildFieldDefinitionsFromArisSource(MigrateSourceInterface $aris_data_source): array {
    $fields = [];
    // Add source ID first.
    $field = new SolrFieldDefinition(['schema' => '']);
    $field->setLabel($this->t('Data Source ID'));
    $field->setDataType('string');
    $fields['aris_source_plugin_id'] = $field;

    foreach ($aris_data_source->fields() as $aris_data_field_id => $source_field) {
      if (!empty($source_field['multivalued'])) {
        $field = new SolrFieldDefinition(['schema' => 'M']);
      }
      else {
        $field = new SolrFieldDefinition(['schema' => '']);
      }
      $field->setLabel($source_field['label']);
      $field->setDataType($source_field['type']);
      $fields[$aris_data_field_id] = $field;
    }
    return $fields;
  }


  /**
   * Builds the field definitions from exiting index config.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index from which we are retrieving field information.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   The array of field definitions for the server, keyed by field name.
   */
  protected function buildFieldDefinitionsFromConfig(IndexInterface $index): array {
    $fields = [];
    foreach ($index->getFields() as $index_field) {
      $solr_field = $index_field->getPropertyPath();
      $field = new SolrFieldDefinition(['schema' => '']);
      $field->setLabel($index_field->getLabel());
      $field->setDataType($index_field->getType());
      $fields[$solr_field] = $field;
    }
    return $fields;
  }

}
