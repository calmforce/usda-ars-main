<?php

namespace Drupal\usda_aris_public_data\Plugin\DataType;

use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api_solr\Plugin\DataType\SolrField;

/**
 * Defines the "Aris Data field" data type.
 *
 * Instances of this class wrap Search API Field objects and allow to deal with
 * fields based upon the Typed Data API.
 *
 * @DataType(
 *   id = "aris_entity_field",
 *   label = @Translation("Aris Data field"),
 *   description = @Translation("Fields from an Aris Entity.")
 * )
 */
class ArisDataField extends SolrField {

  /**
   * Field name.
   *
   * @var string
   */
  protected static $arisDataField = 'aris_entity_field';

  /**
   * The field value(s).
   *
   * @var mixed
   */
  protected $value;

  /**
   * Creates an instance wrapping the given Field.
   *
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The Field object to wrap.
   * @param string $name
   *   The name of the wrapped field.
   * @param \Drupal\Core\TypedData\TypedDataInterface $parent
   *   The parent object of the wrapped field, which should be a Solr document.
   *
   * @return static
   */
  public static function createFromField(FieldInterface $field, $name, TypedDataInterface $parent) {
    // Get the Solr field definition from the SolrFieldManager.
    /** @var \Drupal\search_api_solr\SolrFieldManagerInterface $field_manager */
    $field_manager = \Drupal::getContainer()->get(static::$arisDataField . '.manager');
    $field_id = $field->getPropertyPath();
    $definition = $field_manager->getFieldDefinitions($field->getIndex())[$field_id];
    $instance = new static($definition, $name, $parent);
    $instance->setValue($field->getValues());
    return $instance;
  }

}
