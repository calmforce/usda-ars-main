<?php
/**
 * @file
 * Tests DB connection to MSSQL DB.
 */

namespace Drupal\usda_ars_migrate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

/**
 * Class MssqlTestController.
 *
 * @package Drupal\usda_ars_migrate\Controller
 */
class MssqlTestController extends ControllerBase {

  /**
   * Establishes DB connection to MSSQL DB and performs SELECT query.
   * Displays the Most Recent Articles page.
   */
  public function testConnection() {
    try {
      $query = Database::getConnection('default', 'migrate')
        ->select('cmsDocument', 'doc')
        ->fields('doc', ['nodeId', 'text']);
      $query->join('cmsPropertyData', 'prop', 'doc.nodeId = prop.contentNodeId');
      $query->addField('prop', 'dataNtext');
      $query->condition('doc.newest', '1');
      $query->condition('prop.propertytypeid', '67');
      $query->range(0, 100);
      $result = $query->execute();

      // Extract the information from the query result.
      $items = array();
      foreach ($result as $row) {
        $nodeId = $row->nodeId;
        $text = $row->text;
        $body_text = $row->dataNtext;
        $items[] = [
          'nodeId' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Node ID: ' . $nodeId,
          ],
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h1',
            '#value' => $text,
          ],
          'body_text' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $body_text,
          ],
        ];
      }

      // Make the render array for a paged list.
      $build = array();
      $build['items'] = array(
        '#theme' => 'item_list',
        '#items' => $items,
      );
      // Add the pager.
      $build['item_pager'] = array('#type' => 'pager');

      return $build;
    }
    catch (\Exception $e) {
      \Drupal::logger('usda_ars_drupal')->error('Error connecting to MSSQL database. Error code: @code, @message.',
        array(
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ));
      dpm($e);
    }
    return [];
  }

}
