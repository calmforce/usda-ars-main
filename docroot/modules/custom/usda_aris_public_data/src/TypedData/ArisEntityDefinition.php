<?php

namespace Drupal\usda_aris_public_data\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;

/**
 * A typed data definition class for describing USDA ARIS Entities.
 */
class ArisEntityDefinition extends ComplexDataDefinitionBase {

  /**
   * Creates a new ARIS Entity definition.
   *
   * @param string $index_migration_id
   *   The Index:Migration the ARIS Entity definition belongs to.
   *
   * @return static
   */
  public static function create($index_migration_id = NULL) {
    $definition['type'] = $index_migration_id ? 'aris_entity:' . $index_migration_id : 'aris_entity';
    $document_definition = new static($definition);
    list($index_id, $migration_id) = explode(':', $index_migration_id);
    if ($index_id) {
      $document_definition->setIndexId($index_id);
    }
    if ($migration_id) {
      $document_definition->setMigrationId($migration_id);
    }
    return $document_definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($data_type) {
    // The data type should be in the form of "aris_entity:$index_id:$migration_id".
    $parts = explode(':', $data_type, 3);
    if ($parts[0] !== 'aris_entity') {
      throw new \InvalidArgumentException('Data type must be in the form of "aris_entity:MIGRATION_ID".');
    }

    return self::create($parts[1] . $parts[2]);
  }

  /**
   * {@inheritdoc}
   */
  public function getMigrationId() {
    return $this->definition['constraints']['Migration'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setMigrationId(string $migration_id) {
    return $this->addConstraint('Migration', $migration_id);
  }

  public function setIndexId(string $index_id) {
    return $this->addConstraint('Index', $index_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexId() {
    return $this->definition['constraints']['Index'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $this->propertyDefinitions = [];
      if (!empty($this->getMigrationId()) && !empty($this->getIndexId())) {
        /** @var  \Drupal\migrate\Plugin\MigrationInterface $migration */
        $migration = Migration::load($this->getMigrationId());
        $index = Index::load($this->getIndexId());
        /** @var \Drupal\usda_aris_public_data\ArisEntityFieldManagerInterface $field_manager */
        $field_manager = \Drupal::getContainer()->get('aris_entity_field.manager');
        $this->propertyDefinitions = $field_manager->getFieldDefinitions($index, $migration);
      }
    }
    return $this->propertyDefinitions;
  }

}
