<?php

namespace Drupal\usda_aris_public_data\Plugin\search_api\datasource;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate_plus\Entity\Migration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives Aris datasource plugin definition for aris entity types.
 *
 * @see \Drupal\migrate_plus\Plugin\MigrationConfigDeriver
 */
class ArisEntityDeriver extends DeriverBase implements ContainerDeriverInterface{

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $derivatives = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $deriver = new static();

    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $container->get('string_translation');
    $deriver->setStringTranslation($translation);
    return $deriver;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $plugin_derivatives = [];
      $migrations = Migration::loadMultiple();
      /** @var \Drupal\migrate_plus\Entity\MigrationInterface $migration */
      foreach ($migrations as $id => $migration) {
        if ($migration->destination["plugin"] == 'table' && $migration->destination["table_name"] == 'search_api_item') {
          $plugin_derivatives[$id] = [
            'migration_name' => $id,
            'label' => $migration->label(),
            'description' => $this->t('Provides USDA ARIS Data items for indexing and searching.'),
          ] + $base_plugin_definition;
        }
      }

      $this->derivatives = $plugin_derivatives;
    }

    return $this->derivatives;
  }

}
