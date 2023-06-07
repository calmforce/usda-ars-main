<?php

namespace Drupal\usda_ars_migrate\Plugin\migrate\source;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\usda_ars_migrate\ArisDbQueryService;
use Drupal\usda_ars_migrate\UmbracoDbQueryService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for USDA ARS migrate source plugins.
 */
abstract class UsdaArsSource extends SqlBase {

  /**
   * The UmbracoDbQueryService object.
   *
   * @var \Drupal\usda_ars_migrate\UmbracoDbQueryService
   */
  protected $umbracoDbQueryService;

  /**
   * The ArisDbQueryService object.
   *
   * @var \Drupal\usda_ars_migrate\ArisDbQueryService
   */
  protected $arisDbQueryService;

  /**
   * The theme manager.
   *
   * @var ThemeManagerInterface
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * ARS Main Site Theme.
   *
   * @var ActiveTheme
   */
  protected ActiveTheme $theme;

  /**
   * The renderer service.
   *
   * @var RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, UmbracoDbQueryService $umbracoDbQueryService, ArisDbQueryService $arisDbQueryService, ThemeManagerInterface $theme_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->umbracoDbQueryService = $umbracoDbQueryService;
    $this->arisDbQueryService = $arisDbQueryService;
    $this->themeManager = $theme_manager;
    $this->renderer = $renderer;
    $this->theme = \Drupal::service('theme.initialization')->initTheme('usda_ars_uswds');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('usda_ars_migrate.query.umbraco_db'),
      $container->get('usda_ars_migrate.query.aris_db'),
      $container->get('theme.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Adds SEO-specific properties plus 2 page params to the Row.
   *
   * @param array $properties
   *  The source node properties.
   * @param Row $row
   *  The migration Row object.
   * @throws \Exception
   */
  protected function addSeoProperties(array $properties, Row $row) {
    // SEO metatags.
    $keywords = $properties['keywords']->dataNvarchar;
    $html_title = $properties['htmlTitle']->dataNvarchar;
    $page_description = $properties['pageDescription']->dataNtext;
    // Three fields for the page presentation.
    $breadcrumb_label = $properties['breadcrumbLabel']->dataNvarchar;
    $hide_title = $properties['hidePageTitle']->dataInt;
    $archived = $properties['archiveOption']->dataInt;
    // Add non-empty to the Row.
    if (!empty($keywords)) {
      $row->setSourceProperty('keywords', $keywords);
    }
    if (!empty($html_title)) {
      $row->setSourceProperty('htmlTitle', $html_title . ' | [site:name]');
    }
    if (!empty($page_description)) {
      $row->setSourceProperty('pageDescription', $page_description);
    }
    if (!empty($breadcrumb_label)) {
      $row->setSourceProperty('breadcrumbLabel', $breadcrumb_label);
    }
    if (!empty($hide_title)) {
      $row->setSourceProperty('hidePageTitle', $hide_title);
    }
    if (!empty($archived)) {
      $row->setSourceProperty('archiveOption', $archived);
    }
  }

  /**
   * Adds hyphens to hyphen-less UUID.
   *
   * @param string $value
   *   The UUID to be formatted.
   *
   * @return string
   *   The formatted UUID.
   */
  protected function formatUuid($value) {
    $chunk_length = [8, 4, 4, 4, 12];
    $str_segments = [];
    $offset = 0;
    foreach ($chunk_length as $len) {
      $str_segments[] = substr($value, $offset, $len);
      $offset += $len;
    }
    $uuid = implode('-', $str_segments);
    return strtoupper($uuid);
  }

  /**
   * Gets SD Node ID for given UUID.
   *
   * @param string $uuid
   *   The UUID.
   * @param string $db
   *   The source DB name.
   *
   * @return int|mixed
   *   NULL if failed, Node ID is successful.
   */
  protected function getNodeIdByUuid($uuid, $db) {
    $this->umbracoDbQueryService->getDatabase($db);
    return $this->umbracoDbQueryService->getNodeIdForUUID($uuid);
  }

  /**
   * Gets Main HTML Content for Tellus Row.
   *
   * @param Row $row
   *   Migration Row.
   *
   * @return bool
   *   TRUE if no exceptions, FALSE otherwise.
   */
  protected function getTellusMainContent(Row $row) {
    try {
      $this->umbracoDbQueryService->getDatabase('tellus');
      $properties = $this->umbracoDbQueryService->getNodeProperties($row->getSourceProperty('nodeId'));
      $main_content = $properties['mainContent']->dataNtext;
      if (!empty($main_content)) {
        $row->setSourceProperty('main_content', $main_content);
      }
      return TRUE;
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Gets SD/Aglab Internal Image Cards Properties.
   *
   * @param string $json_content
   *   The Json string.
   * @param integer $node_id
   *   The Node ID.
   * @param string $db
   *   The source DB name.
   *
   * @return array|null
   *   The decoded imageCards Properties or NULL.
   */
  protected function getImageCards(string $json_content, $node_id, string $db) {
    $content_raw = json_decode($json_content, TRUE);
    // If json_decode() has returned NULL, it might be that the data isn't
    // valid utf8 - see http://php.net/manual/en/function.json-decode.php#86997.
    if (is_null($content_raw)) {
      $utf8response = utf8_encode($json_content);
      $content_raw = json_decode($utf8response, TRUE);
    };
    if (empty($content_raw)) {
      return NULL;
    }
    $properties = [];
    // Extract properties.
    if (!empty($areas = $content_raw["sections"][0]["rows"][0]["areas"][0]["controls"])) {
      foreach ($areas as $area) {
        if ($area["value"]["value"]["hidden"] == '1') {
          continue;
        }
        if ($area['value']['dtgeContentTypeAlias'] == 'blImageCards') {
          $image_cards = $area['value']['value']['imageCards'];
          foreach ($image_cards as $card) {
            $card_source_id = NULL;
            $action_link = $card['actionLink'][0];
            if (!empty($action_link['udi']) && $action_link['name'] == 'Learn more') {
              $uuid = substr($action_link['udi'], 15);
              if (!empty($uuid)) {
                $uuid = $this->formatUuid($uuid);
                $card_source_id = $this->getNodeIdByUuid($uuid, $db);
              }
            }
            elseif (!empty($action_link['url'])) {
              $card_source_id = [$node_id, $card['key']];
            }
            if ($card_source_id) {
              $properties['imageCards'][] = [
                'title' => $card['title'],
                'text' => $card['text'],
                'uuid' => $uuid,
                'source_id' => $card_source_id,
              ];
              $properties['imageCardsSourceIds'][] = $card_source_id;
            }
          }
          if (!empty($properties['imageCardsSourceIds'])) {
            $properties['imageCardsSectionTitle'] = $area["value"]["value"]["hideTitle"] !== '1' ? $area['value']['value']['title'] : '';
          }
          break;
        }
      }
    }
    return $properties;
  }

  /**
   * Gets SD/Aglab Image Cards with External Links Properties.
   *
   * @param string $json_content
   *   The Json string.
   * @param string $db
   *   The source DB name.
   *
   * @return array|null
   *   The decoded imageCards Properties or NULL.
   */
  protected function getExternalImageCards(string $json_content, string $db) {
    $content_raw = json_decode($json_content, TRUE);
    // If json_decode() has returned NULL, it might be that the data isn't
    // valid utf8 - see http://php.net/manual/en/function.json-decode.php#86997.
    if (is_null($content_raw)) {
      $utf8response = utf8_encode($json_content);
      $content_raw = json_decode($utf8response, TRUE);
    };
    if (empty($content_raw)) {
      return NULL;
    }
    $properties = [];
    // Extract properties.
    if (!empty($areas = $content_raw["sections"][0]["rows"][0]["areas"][0]["controls"])) {
      foreach ($areas as $area) {
        if ($area["value"]["value"]["hidden"] == '1') {
          continue;
        }
        if ($area['value']['dtgeContentTypeAlias'] == 'blImageCards') {
          $image_cards = $area['value']['value']['imageCards'];
          foreach ($image_cards as $card) {
            $action_link = $card['actionLink'][0];
            // Create Image Card if the link is external
            // or the link label is not "Learn more".
            if ((empty($action_link['udi']) && !empty($action_link['url']))
            || (!empty($action_link['udi']) && $action_link['name'] != 'Learn more')) {
              $link_url = $action_link['url'];
              if ($link_url[0] == '/') {
                $link_url = 'internal:' . $link_url;
              }
              $properties['imageCards'][] = [
                'cardUuid' => $card['key'],
                'title' => $card['title'],
                'body' => $card['text'],
                'image_uuid' => $card['image'],
                'actionLinkUrl' => $link_url,
                'actionLinkName' => $action_link['name'],
              ];
            }
          }
        }
      }
    }
    return $properties;
  }

  protected function decodeBodyColumnedText($json_text) {
    $bodyText = Json::decode($json_text);
    $not_empty = FALSE;
    $i = 0;
    foreach ($bodyText['sections'] as $bSectionsText) {
      $j = 0;
      foreach ($bSectionsText['rows'] as $bRowText) {
        $k = 0;
        foreach ($bRowText['areas'] as $area) {
          if (!empty($area['controls'])) {
            $output = $area['controls'][0]['value'];
            $output = str_replace(['{{PUBLICATIONS}}', '{{PROJECTS}}', '{{NEWS}}'], '', $output);
            $bodyText['sections'][$i]['rows'][$j]['areas'][$k]['controls'][0]['value'] = $output;
            $not_empty = TRUE;
          }
          $k++;
        }
        $j++;
      }
      $i++;
    }

    if ($not_empty) {
      $renderable = [
        '#theme' => 'body_columned_template',
        '#content' => $bodyText,
      ];
      $current_active_theme = $this->themeManager->getActiveTheme();
      $this->themeManager->setActiveTheme($this->theme);
      $html =  $this->renderer->renderPlain($renderable);
      $this->themeManager->setActiveTheme($current_active_theme);
      return $html->__toString();
    }
    return '';
  }

}
