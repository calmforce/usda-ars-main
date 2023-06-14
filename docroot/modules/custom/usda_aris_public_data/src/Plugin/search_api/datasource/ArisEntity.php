<?php

namespace Drupal\usda_aris_public_data\Plugin\search_api\datasource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api\Annotation\SearchApiDatasource;
use Drupal\search_api\Datasource\DatasourcePluginBase;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\usda_aris_public_data\Plugin\DataType\ArisEntityDataType;
use Drupal\usda_aris_public_data\ArisEntityFieldManagerInterface;

//  *   deriver = "Drupal\usda_aris_public_data\Plugin\search_api\datasource\ArisEntityDeriver"

/**
 * Represents a datasource which exposes phantom media entities created for WD Picklist.
 *
 * @SearchApiDatasource(
 *   id = "aris",
 *   label = @Translation("USDA ARIS Data"),
 *   description = @Translation("Provides USDA ARIS Data items for indexing and searching."),
 *   deriver = "Drupal\usda_aris_public_data\Plugin\search_api\datasource\ArisEntityDeriver"
 * )
 */
class ArisEntity extends DatasourcePluginBase { //implements PluginFormInterface {

  use LoggerTrait;
  use PluginFormTrait;

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The Aris Data field manager.
   *
   * @var \Drupal\usda_aris_public_data\ArisEntityFieldManagerInterface
   */
  protected $arisEntityFieldManager;

  /**
   * Migration for the datasource data.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface $migration
   */
  protected $migration = NULL;
  /**
   * The fields helper.
   *
   * @var \Drupal\search_api\Utility\FieldsHelperInterface
   */
  protected $fieldsHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $datasource */
    $datasource = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $datasource->setArisEntityFieldManager($container->get('aris_entity_field.manager'));
    $datasource->setMigrationPluginManager($container->get('plugin.manager.migration'));
    $datasource->setSearchApiFieldsHelper($container->get('search_api.fields_helper'));
    $datasource->setMigration($plugin_id);

    return $datasource;
  }

  /**
   * Sets the migration plugin manager.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The new migration plugin manager.
   *
   * @return $this
   */
  public function setMigrationPluginManager(MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->migrationPluginManager = $migration_plugin_manager;
    return $this;
  }

  /**
   * Sets the Aris Data field manager.
   *
   * @param \Drupal\usda_aris_public_data\ArisEntityFieldManagerInterface $aris_data_field_manager
   *   The new entity field manager.
   *
   * @return $this
   */
  public function setArisEntityFieldManager(ArisEntityFieldManagerInterface $aris_data_field_manager) {
    $this->arisEntityFieldManager = $aris_data_field_manager;
    return $this;
  }

  /**
   * Returns the Aris Data field manager.
   *
   * @return \Drupal\usda_aris_public_data\ArisEntityFieldManagerInterface
   *   The Solr field manager.
   */
  public function getArisEntityFieldManager() {
    return $this->arisEntityFieldManager;
  }

  /**
   * Sets the migration.
   *
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return $this
   */
  public function setMigration(string $plugin_id) {
    $migration_id = explode(':', $plugin_id)[1];
    $this->migration = $this->migrationPluginManager->createInstance($migration_id);
    return $this;
  }

  /**
   * Sets the fields helper.
   *
   * @param \Drupal\search_api\Utility\FieldsHelperInterface $fields_helper
   *   The new fields helper.
   *
   * @return $this
   */
  public function setSearchApiFieldsHelper(FieldsHelperInterface $fields_helper) {
    $this->fieldsHelper = $fields_helper;
    return $this;
  }

    /**
   * {@inheritdoc}
   */
/*  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if ($bundles = $this->getEntityBundleOptions()) {
      $form['bundles'] = [
        '#type' => 'details',
        '#title' => $this->t('Bundles'),
        '#open' => TRUE,
      ];
      $form['bundles']['default'] = [
        '#type' => 'radios',
        '#title' => $this->t('Which bundles should be indexed?'),
        '#options' => [
          0 => $this->t('Only those selected'),
          1 => $this->t('All except those selected'),
        ],
        '#default_value' => (int) $this->configuration['bundles']['default'],
      ];
      $form['bundles']['selected'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Bundles'),
        '#options' => $bundles,
        '#default_value' => $this->configuration['bundles']['selected'],
        '#size' => min(4, count($bundles)),
        '#multiple' => TRUE,
      ];
    }
    return $form;
  }*/

  /**
   * {@inheritdoc}
   */
/*  protected function getEntityBundleOptions() {
    $bundles = ['people' => 'ARIS/REE People profiles',
      'pubs' => 'Publications (A115 Entities)',
      ];
    $options = [];
    foreach ($bundles as $bundle => $bundle_info) {
      $options[$bundle] = Utility::escapeHtml($bundle_info);
    }

    return $options;
  }*/

  /**
   * {@inheritdoc}
   *
   * This function used for the tracking purposes,
   * to load items from DB to tracker.
   */
  public function getItemIds($page = NULL) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   * @throws SearchApiException
   */
  public function loadMultiple(array $ids) {
    // Group the requested items by migration. This will also later be used to
    // determine whether all items were loaded successfully.
    $raw_item_ids = [];
    foreach ($ids as $item_id) {
      $raw_id = explode('/', $item_id)[1] ;
      $raw_item_ids[] = $raw_id;
    }

    // Load the items from the migrations and keep track of which were
    // successfully retrieved.
    $items = [];
      try {
        $source_plugin = $this->migration->getSourcePlugin();
        $query = $source_plugin->query();
        $id_field = $source_plugin->getIds();
        $fields = $source_plugin->fields();
        $multivalued_fields = $lookup_fields = [];
        foreach ($fields as $key => $field) {
          if ($field['multivalued']) {
            $multivalued_fields[] = $key;
          }
          elseif ($field['lookup']) {
            $lookup_fields[$key] = [$field['key_field'] =>  $source_plugin->getLookUpFieldMap($key)];
          }
        }
        if (is_array($id_field)) {
          $id_field_alias = array_keys($id_field)[0];
          $id_field_column_name = $id_field[$id_field_alias]['table_column'];
        }
        $migrate_last_imported_store = \Drupal::keyValue('migrate_last_imported');
        $last_imported = $migrate_last_imported_store->get($this->migration->id(), FALSE);

        $query = $query->condition($id_field_column_name, $raw_item_ids, 'IN');
        $raw_data = $query->execute()->fetchAll();
        $aris_source_plugin_id = $source_plugin->getPluginId();

        foreach ($raw_data as $row) {
          // Add source plugin ID to each row.
          $row['aris_source_plugin_id'] = $aris_source_plugin_id;
          $item_id = $id_field_alias . '/' . $row[$id_field_alias];
          if (!empty($multivalued_fields)) {
            foreach ($multivalued_fields as $multivalued_field) {
              $values = $source_plugin->getMultivaluedFieldValues($row[$id_field_alias], $multivalued_field);
              $row[$multivalued_field] = $values;
            }
          }
          if (!empty($lookup_fields)) {
            foreach ($lookup_fields as $lookup_field => $lookup_map) {
              $key_field = key($lookup_map);
              $row[$lookup_field] = $lookup_map[$key_field][$row[$key_field]];
            }
          }
          $search_api_item = $this->fieldsHelper->createItem($this->index, $item_id, $this);
          $search_api_item->setLanguage('en');
          $search_api_item->setExtraData('aris_migration_id',  $this->migration->id());
          $search_api_item->setExtraData('aris_data', $row);
          $items[$item_id] = ArisEntityDataType::createFromItem($search_api_item);
          // Remember that we successfully loaded this item.
          unset($raw_item_ids[$row[$id_field_alias]]);
        }
      }
      catch (SearchApiException $e) {
        $this->logException($e);
      }

    if (empty($items)) {
      // Nothing loaded because the tracker gave us the entity:media set.
      throw new SearchApiException("The datasource aris could not be used to load these items.");
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemId(ComplexDataInterface $item) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemLanguage(ComplexDataInterface $item) {

    $item = $item->getValue();
    if (!empty($item->getLanguage())) {
      return $item->getLanguage();
    }
    return Language::LANGCODE_NOT_SPECIFIED;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Filter out empty checkboxes.
    foreach (['bundles'] as $key) {
      if ($form_state->hasValue($key)) {
        $parents = [$key, 'selected'];
        $value = $form_state->getValue($parents, []);
        $value = array_keys(array_filter($value));
        $form_state->setValue($parents, $value);
      }
    }
    // Make sure not to overwrite any options not included in the form (like
    // "disable_db_tracking") by adding any existing configuration back to the
    // new values.
    $this->setConfiguration($form_state->getValues() + $this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return $this->getArisEntityFieldManager()->getFieldDefinitions($this->index, $this->migration);
  }

}
