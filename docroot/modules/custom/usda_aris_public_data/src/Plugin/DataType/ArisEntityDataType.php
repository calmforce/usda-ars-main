<?php

namespace Drupal\usda_aris_public_data\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\TypedData\TypedData;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api_solr\TypedData\SolrFieldDefinition;
use Drupal\usda_aris_public_data\TypedData\ArisEntityDefinition;

//  *   deriver = "\Drupal\search_api_solr\Plugin\DataType\Deriver\SolrDocumentDeriver",

/**
 * Defines the "Aris Entity" data type.
 *
 * Instances of this class wrap Search API Item objects and allow to deal with
 * items based upon the Typed Data API.
 *
 * @DataType(
 *   id = "aris_entity",
 *   label = @Translation("USDA ARIS Entity"),
 *   description = @Translation("Records from USDA ARIS/REE DBs."),
 *   definition_class = "\Drupal\usda_aris_public_data\TypedData\ArisEntityDefinition"
 * )
 */
class ArisEntityDataType extends TypedData implements \IteratorAggregate, ComplexDataInterface {

  /**
   * Field name.
   *
   * @var string
   */
  protected static $arisDataField = 'aris_entity_field';

  /**
   * The wrapped Search API Item.
   *
   * @var \Drupal\search_api\Item\ItemInterface|null
   */
  protected $item;

  /**
   * Creates an instance wrapping the given Item.
   *
   * @param \Drupal\search_api\Item\ItemInterface|null $item
   *   The Item object to wrap.
   *
   * @return static
   */

  /**
   * The ArisDataField DataType plugin.
   *
   * @var \Drupal\usda_aris_public_data\Plugin\DataType\ArisDataField
   */
  protected $plugin;

  /**
   * Static cache of field definitions per Solr server & ARIS Data Source.
   *
   * @var array
   */
  protected $fieldDefinitions;

  public static function createFromItem(ItemInterface $item) {

    $migration_id = $item->getExtraData('aris_migration_id');
    $index = $item->getIndex();
    $definition = ArisEntityDefinition::create($index->id() . ':' . $migration_id);
    $instance = new static($definition);
    $instance->setValue($item);

    /** @var \Drupal\usda_aris_public_data\Plugin\DataType\ArisDataField $plugin */
    $plugin = \Drupal::typedDataManager()->getDefinition(static::$arisDataField)['class'];
    $instance->setDataTypePlugin($plugin);

    $field_manager = \Drupal::getContainer()->get('aris_entity_field.manager');
    $migration_manager = \Drupal::getContainer()->get('plugin.manager.migration');
    /** @var  \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $migration_manager->createInstance($migration_id);
    $fields = $field_manager->getFieldDefinitions($index, $migration);

    $instance->setFieldDefinitions($fields);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->item;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($item, $notify = TRUE) {
    $this->item = $item;
  }

  public function setDataTypePlugin($plugin) {
    $this->plugin = $plugin;
  }

  public function getDataTypePlugin() {
    return $this->plugin;
  }

  /**
   * Sets field definitions.
   *
   * @param array $fields
   *   The fields.
   *
   * @return void
   */
  public function setFieldDefinitions($fields) {
    $this->fieldDefinitions = $fields;
  }

  /**
   * Gets field definitions.
   *
   * @return array
   *   The field definitions.
   */
  public function getFieldDefinitions() {
    return $this->fieldDefinitions;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function get($property_name) {

    if (!isset($this->item)) {
      throw new MissingDataException("Unable to get Aris Entity field $property_name as no item has been provided.");
    }
    // First, verify that this field actually exists. If we
    // can't get a definition for it, it doesn't exist.
    $field_definitionss = $this->getFieldDefinitions();
    if (empty($field_definitionss[$property_name])) {
      throw new \InvalidArgumentException("The Aris Entity field $property_name could not be found.");
    }
    // Create a new typed data object from the item's field data.
    $plugin = $this->getDataTypePlugin();
    $property = $plugin::createInstance($field_definitionss[$property_name], $property_name, $this);

    // Now that we have the property, try to find its values. We first look at
    // the field values contained in the result item.
    $found = FALSE;
    foreach ($this->item->getFields(FALSE) as $field) {
      if ($field->getDatasourceId() === $this->item->getDatasourceId() &&
          $field->getPropertyPath() === $property_name) {
        $property->setValue($field->getValues());
        $found = TRUE;
        break;
      }
    }

    if (!$found) {
      // If that didn't work, maybe we can get the field from the Aris Entity ?
      $document = $this->item->getExtraData('aris_data');
      if (
        /* $document instanceof DocumentInterface &&*/
        isset($document[$property_name])
      ) {
        $value = $document[$property_name];
        if (is_array($value)) {
          $properties_array = [];
          $field_definition =  new SolrFieldDefinition(['schema' => '']);
          $field_definition->setDataType($field_definitionss[$property_name]->getDataType());
          foreach ($value as $item) {
            $item_property = $plugin::createInstance($field_definition, $property_name, $this);
            $item_property->setValue($item);
            $properties_array[] = $item_property;
          }
          $property->setValue($properties_array);
        }
        else {
          $property->setValue($value);
        }
      }
    }

    return $property;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value, $notify = TRUE) {
    // Do nothing because we treat Solr documents as read-only.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties($include_computed = FALSE) {
    return $this->item->getExtraData('aris_data');
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    // @todo Implement this.
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return !isset($this->item);
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($name) {
    // Do nothing.  Unlike content entities, Items don't need to be notified of
    // changes.
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \Traversable {
    return isset($this->item) ? $this->item->getIterator() : new \ArrayIterator([]);
  }

}
